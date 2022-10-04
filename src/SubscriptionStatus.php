<?php

namespace FintechSystems\PayFast;

class SubscriptionStatus
{
    /**
     * This is the master if then else if. It will check every possible iteration of
     * a subscription and then return a user-friendly variable of which the key
     * has the friendly status and the value contains optional usable data.
     */
    public static function for($user)
    {
        if (! $user->subscribed('default')) {
            if ($user->onGenericTrial()) {
                // On generic trial, which is similar to createCustomer on a fresh user
                $status = ["on_trial" => $user->trialEndsAt()];
            } else {
                // User has never been created as a customer
                $status = ["no_subscription" => ''];
            }
        } else {
            if ($user->subscription('default')->onGracePeriod()) {
                // Subscription is within its grace period after cancellation.
                $status = ["cancelled" => $user->subscription('default')->ends_at->format('Y-m-d')];
            } else {
                $status = ["subscribed" => $user->subscription('default')->plan_id];
            }
        }

        return $status;
    }
}
