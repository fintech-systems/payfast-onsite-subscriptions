<?php

namespace FintechSystems\PayFast\Http\Controllers;

use Exception;
use FintechSystems\PayFast\Cashier;
use FintechSystems\PayFast\Events\PaymentSucceeded;
use FintechSystems\PayFast\Events\SubscriptionCancelled;
use FintechSystems\PayFast\Events\SubscriptionCreated;
use FintechSystems\PayFast\Events\SubscriptionFetched;
use FintechSystems\PayFast\Events\SubscriptionPaymentSucceeded;
use FintechSystems\PayFast\Events\WebhookHandled;
use FintechSystems\PayFast\Events\WebhookReceived;
use FintechSystems\PayFast\Exceptions\InvalidMorphModelInPayload;
use FintechSystems\PayFast\Exceptions\MissingSubscription;
use FintechSystems\PayFast\Facades\PayFast;
use FintechSystems\PayFast\Receipt;
use FintechSystems\PayFast\Subscription;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    /**
     * Handle a PayFast webhook call.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function __invoke(Request $request)
    {
        Log::info("Incoming Webhook from PayFast...");

        ray('Incoming Webhook from PayFast')->purple();

        $payload = $request->all();

        ray($payload)->blue();

        Log::debug($payload);

        if (isset($payload['event_time'])) {
            return new Response();
        }

        WebhookReceived::dispatch($payload);

        try {
            if (! isset($payload['token'])) {
                $this->nonSubscriptionPaymentReceived($payload);
                WebhookHandled::dispatch($payload);

                return new Response('Webhook nonSubscriptionPaymentReceived handled');
            }

            if (! $this->findSubscription($payload['token'])) {
                $this->createSubscription($payload);
                WebhookHandled::dispatch($payload);

                return new Response('Webhook createSubscription/applySubscriptionPayment handled');
            }

            if ($payload['payment_status'] == Subscription::STATUS_DELETED) {
                $this->cancelSubscription($payload);
                WebhookHandled::dispatch($payload);

                return new Response('Webhook cancelSubscription handled');
            }

            if ($payload['payment_status'] == Subscription::STATUS_ACTIVE) {
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
            'paid_at' => now(),
        ]);

        PaymentSucceeded::dispatch($receipt, $payload);

        $message = "Created the non-subscription payment receipt.";

        Log::notice($message);

        ray($message)->green();
    }

    protected function createSubscription(array $payload)
    {
        $message = "Creating a new subscription...";

        Log::info($message);

        ray($message)->orange();

        $message = "findOrCreateCustomer...";

        Log::info($message);

        ray($message)->orange();

        $customer = $this->findOrCreateCustomer($payload);

        $message = "Create a subscription for the new customer...";

        Log::info($message);

        ray($message)->orange();

        $subscription = $customer->subscriptions()->create([
            'name' => 'default',
            'payfast_token' => $payload['token'],
            'plan_id' => $payload['custom_int2'],
            'merchant_payment_id' => $payload['m_payment_id'],
            'payfast_status' => $payload['payment_status'],
            'next_bill_at' => $payload['billing_date'],
        ]);

        SubscriptionCreated::dispatch($customer, $subscription, $payload);

        $message = "Created a new subscription " . $payload['token'] . ".";

        Log::notice($message);

        ray($message)->green();

        $this->applySubscriptionPayment($payload);
    }

    /**
     * Apply a subscription payment succeeded.
     *
     * Gets triggered after first payment, and every subsequent payment that has a token
     *
     * @param  array  $payload
     * @return void
     */
    protected function applySubscriptionPayment(array $payload)
    {
        if (is_null($payload['item_name'])) {
            $payload['item_name'] = 'Card Information Updated';
            $message = "Updating card information for " . $payload['token'] . "...";
        } else {
            $message = "Applying a subscription payment to " . $payload['token'] . "...";
        }

        Log::info($message);
        ray($message)->orange();

        $billable = $this->findSubscription($payload['token'])->billable;

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
            'paid_at' => now(),
        ]);

        SubscriptionPaymentSucceeded::dispatch($billable, $receipt, $payload);

        if ($payload['item_name'] == 'Card Information Updated') {
            $message = "Updated the card information.";
        } else {
            $message = "Applied the subscription payment.";
        }
        Log::notice($message);
        ray($message)->green();

        $message = "Fetching and updating API status for token " . $payload['token'] . "...";
        Log::info($message);
        ray($message)->orange();

        // Dispatch a new API call to fetch the subscription information and update the status and next_bill_at
        $result = PayFast::fetchSubscription($payload['token']);

        Log::debug("Result of new API call to get current subscription status and next_bill_at");
        Log::debug($result);
        ray($result);

        $subscription = Subscription::where(
            'payfast_token',
            $payload['token']
        )->first();

        $subscription->updatePayFastSubscription($result);

        $message = "Fetched and updated API status for token " . $payload['token'] . ".";
        Log::notice($message);
        ray($message)->green();

        // PayFast requires a 200 response after a successful payment application
        return response('Subscription Payment Applied', 200);
    }

    protected function fetchSubscriptionInformation(array $payload)
    {
        $message = "Fetching subscription information for " . $payload['token'] . "...";
        Log::info($message);
        ray($message)->orange();

        $result = PayFast::fetchSubscription($payload['token']);

        // Update or Create Subscription
        $subscription = Subscription::find(1);

        ray($result);

        SubscriptionFetched::dispatch($subscription, $payload);
    }

    /**
     * Handle subscription cancelled.
     *
     * @param  array  $payload
     * @return void
     */
    protected function cancelSubscription(array $payload)
    {
        $message = "Cancelling subscription " . $payload['token'] . "...";
        Log::info($message);
        ray($message)->orange();

        if (! $subscription = $this->findSubscription($payload['token'])) {
            throw new MissingSubscription();
        }

        $message = "Looked for and found the subscription...";
        Log::debug($message);
        ray($message);

        // ray($subscription);

        // Cancellation date...
        if (is_null($subscription->ends_at)) {
            $subscription->ends_at = $subscription->onTrial()
                ? $subscription->trial_ends_at
                : $subscription->next_bill_at->subMinutes(1);
        }

        // $message = "We've interpreted and possibly saved the ends_at date...";
        // Log::debug($message);
        // ray($message);

        $subscription->cancelled_at = now();

        $subscription->payfast_status = $payload['payment_status'];

        $subscription->paused_from = null;

        // $message = "Now we're going to save information about the subscription...";
        // Log::debug($message);
        // ray($message);

        $subscription->save();

        SubscriptionCancelled::dispatch($subscription, $payload);

        $message = "Cancelled the subscription.";
        Log::notice($message);
        ray($message)->green();
    }

    private function findSubscription(string $subscriptionId)
    {
        return Cashier::$subscriptionModel::firstWhere('payfast_token', $subscriptionId);
    }

    private function findOrCreateCustomer(array $passthrough)
    {
        if (! isset($passthrough['custom_str1'], $passthrough['custom_int1'])) {
            throw new InvalidMorphModelInPayload($passthrough['custom_str1'] . "|" . $passthrough['custom_int1']);
        }

        return Cashier::$customerModel::firstOrCreate([
            'billable_id' => $passthrough['custom_int1'],
            'billable_type' => $passthrough['custom_str1'],
        ])->billable;
    }
}
