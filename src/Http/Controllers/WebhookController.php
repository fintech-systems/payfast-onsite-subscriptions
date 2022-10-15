<?php

namespace FintechSystems\PayFast\Http\Controllers;

use Exception;
use FintechSystems\PayFast\Cashier;
use FintechSystems\PayFast\Events\PaymentSucceeded;
use FintechSystems\PayFast\Events\SubscriptionCancelled;
use FintechSystems\PayFast\Events\SubscriptionCreated;
use FintechSystems\PayFast\Events\SubscriptionPaymentSucceeded;
use FintechSystems\PayFast\Events\WebhookHandled;
use FintechSystems\PayFast\Events\WebhookReceived;
use FintechSystems\PayFast\Exceptions\InvalidMorphModelInPayload;
use FintechSystems\PayFast\Exceptions\MissingSubscription;
use FintechSystems\PayFast\Facades\PayFast;
use FintechSystems\PayFast\Payment;
use FintechSystems\PayFast\Receipt;
use FintechSystems\PayFast\Subscription;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    /**
     * Handle a PayFast webhook call and determine what to do with it.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function __invoke(Request $request)
    {
        $message = 'Incoming Webhook from PayFast';
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
            if (! isset($payload['token'])) {
                $this->nonSubscriptionPaymentReceived($payload);

                WebhookHandled::dispatch([
                    'action' => 'ad_hoc_payment_received',
                    'payload' => $payload,
                ]);

                return new Response('Webhook ad-hoc payment received (nonSubscriptionPaymentReceived) handled');
            }

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

            return response('An exception occurred in the PayFast webhook controller', 500);
        }

        Log::error("Abnormal Webhook termination. No Webhook intepreter was found.");
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
            'plan_id' => $payload['custom_int2'],
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
     * @param  array  $payload
     * @return void
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
        PayFast::debug($message, 'applySubscriptionPayment()');

        if (! isset($payload['amount_gross'])) {
            throw new Exception("Unable to apply a payment to an existing subscription because amount_gross is not set. Probably cause the subscription was deleted.");
        }

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

        SubscriptionPaymentSucceeded::dispatch($billable, $receipt, $payload);

        // Get the user's latest subscription using first()
        $subscription = Subscription::where('payfast_token', $payload['token'])->first();

        $subscription->updatePayFastSubscription(PayFast::fetchSubscription($payload['token']));

        // PayFast requires a 200 response after a successful payment application
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
     * Get plan frequency based on $payload custom_int2 converted to an integer
     */
    private function getSubscriptionName($payload)
    {
        $recurringType = Subscription::frequencies((int) $payload['custom_int2']);

        return config('app.name') . " $recurringType Subscription";
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
