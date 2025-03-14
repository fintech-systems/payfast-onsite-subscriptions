<?php

namespace FintechSystems\Payfast;

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
                $status = ["on_generic_trial" => $user->trialDaysLeft()];
            } else {
                // User has never been created as a customer
                $status = ["no_subscription" => ''];
            }
        } else {
            if ($user->subscription('default')->onGracePeriod()) {
                // Subscription is within its grace period after cancellation.
                $status = ["cancelled" => $user->subscription('default')->ends_at->format('Y-m-d')];
            } else {
                $status = [
                    "subscribed" => config('payfast.billables.user.plans')
                    [explode('|', $user->subscription('default')->plan)[0]]
                    ['name'] . ' ' . explode('|', $user->subscription('default')->plan)[1] . " plan.",
                ];
            }
        }

        return $status;
    }
}
