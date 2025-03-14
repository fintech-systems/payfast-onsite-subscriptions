<?php

namespace FintechSystems\Payfast\Traits;

use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * A set of helper methods for the Payfast Billable trait, to be added along with Billable to the user/billable model.
 */
trait Payfast
{
    /**
     * Get the subscription status of the user in key value pair format
     *
     * Results:
     *  - on_generic_trial: The number of trial days left
     *  - no_subscription: ''
     *  - cancelled: The date the subscription ends
     *  - subscribed: The name of the subscribed plan
     *
     * @return array
     * @throws BindingResolutionException
     */
    protected function subscriptionStatus(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function () {
                if (! $this->subscribed('default')) {
                    if ($this->onGenericTrial()) {
                        $status = ["on_generic_trial" => $this->trialDaysLeft()];
                    } else {
                        // User has never been created as a customer
                        $status = ["no_subscription" => ''];
                    }
                } else {
                    if ($this->subscription('default')->onGracePeriod()) {
                        // Subscription is within its grace period after cancellation.
                        $status = ["cancelled" => $this->subscription('default')->ends_at->format('Y-m-d')];
                    } else {
                        $status = [
                            "subscribed" => $this->planName(),
                        ];
                    }
                }

                ray($status);

                return $status;
            }
        );
    }

    /**
     * Get the plan name for the current subscription
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function planName(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function () {
                if ($this->subscriptions?->isEmpty()) {
                    return null;
                }

                $subscription = $this->subscriptions->first();
                $planParts = explode('|', $subscription->plan);
                
                if (count($planParts) < 1) {
                    return null;
                }

                $planId = $planParts[0];

                return config('payfast.billables.user.plans')[$planId]['name'] ?? null;
            }
        );
    }

    /**
     * Get the plan frequency for the current subscription
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function planFrequency(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function () {
                if ($this->subscriptions?->isEmpty()) {
                    return null;
                }

                $subscription = $this->subscriptions->first();
                $planParts = explode('|', $subscription->plan);
                
                if (count($planParts) < 2) {
                    return null;
                }

                return $planParts[1];
            }
        );
    }

    /**
     * Get the formatted plan amount for the current subscription
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function planAmount(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function () {
                if ($this->subscriptions?->isEmpty()) {
                    return null;
                }

                $subscription = $this->subscriptions->first();
                $planParts = explode('|', $subscription->plan);
                
                if (count($planParts) < 1) {
                    return null;
                }

                $planId = $planParts[0];
                $frequency = $planParts[1] ?? 'monthly';

                $amount = config('payfast.billables.user.plans')[$planId][$frequency]['recurring_amount'] ?? null;

                return $amount ? 'R' . number_format($amount / 100) : null;
            }
        );
    }
}
