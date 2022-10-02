<?php

namespace FintechSystems\PayFast\Components;

use Carbon\Carbon;
use Livewire\Component;

class Banner extends Component
{
    public $message;

    public function hide()
    {
        ray('hiding');
    }

    public function mount()
    {
        $this->message = $this->getMessage();
    }

    /**
     * This is the God loop for subscription checks. It shows every possible iteration
     * of subscription combinations. It was copied from the Livewire subscriptions
     * component. Checks: Trial or not a plan, or, in grace or subscribed
     */
    public function getMessage()
    {
        $user = Auth()->user();

        $message = "";

        // <!-- Check if the current logged in user is subscribed to a plan -->
        if (! $user->subscribed('default')) {
            // {{-- Trial --}}
            if ($user->onGenericTrial()) {
                $message = "You are currently on trial till " . $user->trialEndsAt()->format('jS \o\f F Y');
            } else {
                $message = "You are not currently subscribed to a plan.";
            }
        } else {
            if ($user->subscription('default')->onGracePeriod()) {
                // This block means the user is in the grace period of their subscription
                if (Carbon::now()->diffInDays(
                    $user->subscriptions()->active()->first()->ends_at->format('Y-m-d'),
                ) != 0) {
                    $message = "There are "
                        . Carbon::now()->diffInDays($user->subscription('default')->ends_at)
                        . " days left of your subscription and the last day is the "
                        . $user->subscription('default')->ends_at->format('jS \o\f F Y');
                } else {
                    $message = "Today is the last day of your subscription.";
                }
            } else {
                $message = "You are subscribed to the "
                    . config('payfast.plans')[$user->subscription('default')->plan_id]['name']  . " plan.";
            }
        }

        return $message;
    }

    public function render()
    {
        return view('vendor.payfast.components.banner');
    }
}
