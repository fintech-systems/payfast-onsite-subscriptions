<?php

namespace FintechSystems\Payfast\Http\Controllers;

use Exception;
use FintechSystems\Payfast\Cashier;
use FintechSystems\Payfast\Events\PaymentSucceeded;
use FintechSystems\Payfast\Events\SubscriptionCancelled;
use FintechSystems\Payfast\Events\SubscriptionCreated;
use FintechSystems\Payfast\Events\SubscriptionPaymentSucceeded;
use FintechSystems\Payfast\Events\WebhookHandled;
use FintechSystems\Payfast\Events\WebhookReceived;
use FintechSystems\Payfast\Exceptions\InvalidMorphModelInPayload;
use FintechSystems\Payfast\Exceptions\MissingSubscription;
use FintechSystems\Payfast\Facades\Payfast;
use FintechSystems\Payfast\Payment;
use FintechSystems\Payfast\Receipt;
use FintechSystems\Payfast\Subscription;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    /**
     * Handle a Payfast webhook call and determine what to do with it.
     *
     * @param Request $request
     * @return Response
     */
    public function __invoke(Request $request)
    {
        $message = 'Incoming Webhook from Payfast';
        Log::info($message);
        ray($message)->blue();

        $payload = $request->all();

        Log::debug($payload);
        ray($payload)->green();

        if (isset($payload['ping'])) {
            return new Response();
        }

        WebhookReceived::dispatch($payload);

        ray("Checking what kind of webhook received...");

        try {
            // Non subscription payment handling
            if (! isset($payload['token'])) {
                $this->nonSubscriptionPaymentReceived($payload);

                WebhookHandled::dispatch([
                    'action' => 'ad_hoc_payment_received',
                    'payload' => $payload,
                ]);

                return new Response('Webhook ad-hoc payment received (nonSubscriptionPaymentReceived) handled');
            }

            // New token received, so let's create a subscription
            if (! $this->findSubscription($payload['token'])) {
                $this->createSubscription($payload);

                WebhookHandled::dispatch([
                    'action' => 'subscription_created_payment_applied',
                    'payload' => $payload,
                ]);

                return new Response('Webhook createSubscription/applySubscriptionPayment handled');
            }

            if ($payload['payment_status'] == Subscription::STATUS_DELETED) {
                $this->cancelSubscription($payload);

                WebhookHandled::dispatch([
                    'action' => 'subscription_cancelled',
                    'payload' => $payload,
                ]);

                return new Response('Webhook cancelSubscription handled');
            }

            if ($payload['payment_status'] == Payment::COMPLETE) {
                $this->applySubscriptionPayment($payload);

                WebhookHandled::dispatch($payload);

                return new Response('Webhook applySubscriptionPayment handled');
            }
        } catch (Exception $e) {
            $message = $e->getMessage();

            Log::critical($message);

            ray($e)->red();

            return response('An exception occurred in the Payfast webhook controller', 500);
        }

        Log::error("Abnormal Webhook termination. No Webhook interpreter was found.");
    }

    /**
     * Handle one-time payment succeeded.
     *
     * @param  array  $payload
     * @return void
     */
    protected function nonSubscriptionPaymentReceived(array $payload)
    {
        $message = "Creating a non-subscription payment receipt...";

        Log::info($message);

        ray($message)->orange();

        $receipt = Receipt::create([
            'merchant_payment_id' => $payload['m_payment_id'],
            'payfast_payment_id' => $payload['pf_payment_id'],
            'payment_status' => $payload['payment_status'],
            'item_name' => $payload['item_name'],
            'item_description' => $payload['item_description'],
            'amount_gross' => $payload['amount_gross'],
            'amount_fee' => $payload['amount_fee'],
            'amount_net' => $payload['amount_net'],
            'billable_id' => $payload['custom_int1'],
            'billable_type' => $payload['custom_str1'],
            'received_at' => now(),
        ]);

        PaymentSucceeded::dispatch($receipt, $payload);

        $message = "Created the non-subscription payment receipt.";

        Log::notice($message);

        ray($message)->green();
    }

    protected function createSubscription(array $payload)
    {
        $customer = $this->findOrCreateCustomer($payload);

        $subscription = $customer->subscriptions()->create([
            'name' => 'default',
            'payfast_token' => $payload['token'],
            'plan' => $payload['custom_str2'],
            'merchant_payment_id' => $payload['m_payment_id'],
            'payfast_status' => $payload['payment_status'],
            'next_bill_at' => $payload['billing_date'] ?? null, // This happens when subscription was never created but then cancelled
        ]);

        SubscriptionCreated::dispatch($customer, $subscription, $payload);

        ray("Subscription created/reactivated for $customer->email and now applying payment...")->green();

        $this->applySubscriptionPayment($payload);
    }

    /**
     * Apply a subscription payment.
     *
     * Gets triggered after first payment, and every subsequent payment that has a token. If the
     * payload item_name is empty we're working with an existing subscription that has been
     * reactivated. Check status of subscription post payment to update next_bill_at.
     *
     * @param array $payload
     * @return \Illuminate\Http\Response
     * @throws Exception
     */
    protected function applySubscriptionPayment(array $payload)
    {
        $billable = $this->findSubscription($payload['token'])->billable;

        if (is_null($payload['item_name'])) {
            $payload['item_name'] = $this->getSubscriptionName($payload);

            $message = "Reactivating subscription for $billable->email";
        } else {
            $message = "Applying a subscription payment to " . $payload['token'] . "...";
        }

        Log::debug($message . ' applySubscriptionPayment()');

        if (! isset($payload['amount_gross'])) {
            throw new Exception("Unable to apply a payment to an existing subscription because amount_gross is not set. Probably cause the subscription was deleted.");
        }

        // Create a receipt
        $receipt = $billable->receipts()->create([
            'payfast_token' => $payload['token'],
            'order_id' => $payload['m_payment_id'],
            'merchant_payment_id' => $payload['m_payment_id'],
            'payfast_payment_id' => $payload['pf_payment_id'],
            'payment_status' => $payload['payment_status'],
            'item_name' => $payload['item_name'],
            'item_description' => $payload['item_description'] ?? null,
            'amount_gross' => $payload['amount_gross'],
            'amount_fee' => $payload['amount_fee'],
            'amount_net' => $payload['amount_net'],
            'billable_id' => $payload['custom_int1'],
            'billable_type' => $payload['custom_str1'],
            'billing_date' => $payload['billing_date'],
            'received_at' => now(),
        ]);

        // Obtain fresh subscription information from Payfast which includes "run_date"

        // First get first subscription attached to this token
        $subscription = Subscription::where('payfast_token', $payload['token'])->first();

        // Next get the current subscription data from Payfast
        $result = Payfast::fetchSubscription($payload['token']);

        // Update the subscription with the fresh data
        $subscription->updatePayfastSubscription($result);

        // Raise an event
        SubscriptionPaymentSucceeded::dispatch($billable, $receipt, $payload);

        // Payfast requires a 200 response after a successful payment application
        return response("Subscription payment applied or subscription reactivated for $billable->email", 200);
    }

    /**
     * Handle subscription cancelled.
     *
     * @param  array  $payload
     * @return void
     */
    protected function cancelSubscription(array $payload)
    {
        ray("Cancelling subscription " . $payload['token'] . "...")->orange();

        if (! $subscription = $this->findSubscription($payload['token'])) {
            throw new MissingSubscription();
        }

        if (is_null($subscription->ends_at)) {
            $subscription->ends_at = $subscription->onTrial()
                ? $subscription->trial_ends_at
                : $subscription->next_bill_at->subMinutes(1);
        }

        ray("The subscription will end at " . $subscription->ends_at->format('Y-m-d'));

        $subscription->cancelled_at = now();
        $subscription->payfast_status = $payload['payment_status'];
        $subscription->paused_from = null;
        $subscription->save();

        SubscriptionCancelled::dispatch($subscription, $payload);
    }

    private function findSubscription(string $subscriptionId)
    {
        return Cashier::$subscriptionModel::firstWhere('payfast_token', $subscriptionId);
    }

    /**
     * Get the subscription name by exploding the $plan to get the ID.
     * This is only invoked during the hook and not when creating a payment subscription for the first time.
     */
    private function getSubscriptionName($payload)
    {
        $customStr2 = $payload['custom_str2'];

        $planId = explode('|', $customStr2)[0];

        return config('payfast.billables.user.plans')[$planId]['name'] . ' ' . ucfirst(explode('|', $customStr2)[1]);
    }

    /**
     * Based on custom_str1 (e.g. App\Models\User) and custom_int1 which is the
     * model ID go and find the billable model and either create a new one
     * if it doesn't exist otherwise just retrieve the existing one.
     */
    private function findOrCreateCustomer(array $passthrough)
    {
        if (! isset($passthrough['custom_str1'], $passthrough['custom_int1'])) {
            throw new InvalidMorphModelInPayload($passthrough['custom_str1'] . "|" . $passthrough['custom_int1']);
        }

        $customer = Cashier::$customerModel::firstOrCreate([
            'billable_id' => $passthrough['custom_int1'],
            'billable_type' => $passthrough['custom_str1'],
        ])->billable;

        return $customer;
    }
}
