<?php

namespace FintechSystems\PayFast\Concerns;

use FintechSystems\PayFast\Cashier;
use FintechSystems\PayFast\Subscription;
use FintechSystems\PayFast\SubscriptionBuilder;

trait ManagesSubscriptions
{
    public function newSubscription($name, $plan)
    {
        return new SubscriptionBuilder($this, $name, $plan);
    }

    /**
     * Get all of the subscriptions for the Billable model.
     *
     * Important: Sorted by `created_at` meaning the latest subscription will always be returned.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function subscriptions()
    {
        return $this->morphMany(Cashier::$subscriptionModel, 'billable')->orderByDesc('created_at');
    }

    public function subscription($name = 'default')
    {
        return $this->subscriptions->where('name', $name)->first();
    }

    /**
     * Determine if the Billable model is on trial.
     *
     * @param  string  $name
     * @param  int|null  $plan
     * @return bool
     */
    public function onTrial($name = 'default', $plan = null)
    {
        if (func_num_args() === 0 && $this->onGenericTrial()) {
            return true;
        }

        $subscription = $this->subscription($name);

        if (! $subscription || ! $subscription->onTrial()) {
            return false;
        }

        return $plan ? $subscription->hasPlan($plan) : true;
    }

    /**
     * Determine if the Billable model is on a "generic" trial at the model level.
     *
     * @return bool
     */
    public function onGenericTrial()
    {
        if (is_null($this->customer)) {
            return false;
        }

        return $this->customer->onGenericTrial();
    }

    /**
     * Get the ending date of the trial.
     *
     * @param  string  $name
     * @return \Illuminate\Support\Carbon|null
     */
    public function trialEndsAt($name = 'default')
    {
        if ($subscription = $this->subscription($name)) {
            return $subscription->trial_ends_at;
        }

        return $this->customer->trial_ends_at;
    }

    /**
     * Determine if the Billable model has a given subscription.
     *
     * @param  string  $name
     * @param  int|null  $plan
     * @return bool
     */
    public function subscribed($name = 'default', $plan = null)
    {
        $subscription = $this->subscription($name);

        if (! $subscription || ! $subscription->valid()) {
            return false;
        }

        return $plan ? $subscription->hasPlan($plan) : true;
    }

    /**
     * Determine if the Billable model is actively subscribed to one of the given plans.
     *
     * @param  int  $plan
     * @param  string  $name
     * @return bool
     */
    public function subscribedToPlan($plan, $name = 'default')
    {
        $subscription = $this->subscription($name);

        if (! $subscription || ! $subscription->valid()) {
            return false;
        }

        return $subscription->hasPlan($plan);
    }

    /**
     * Determine if the entity has a valid subscription on the given plan.
     *
     * @param  int  $plan
     * @return bool
     */
    public function onPlan($plan)
    {
        return ! is_null($this->subscriptions()
            ->where('plan_id', $plan)
            ->get()
            ->first(fn (Subscription $subscription) => $subscription->valid()));
    }
}
