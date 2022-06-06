<?php

namespace FintechSystems\PayFast\Components;

use FintechSystems\PayFast\Facades\PayFast;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Subscriptions extends Component
{
    public $user;

    public $confirmingCancelSubscription = false;

    public $displayingCreateSubscription = false;

    public $plan = 3;

    public $identifier;

    public $updateCardLink;

    public $mergeFields;

    protected $listeners = [
        // 'billingUpdated' => '$refresh',
        'billingUpdated' => 'billingWasUpdated',
    ];

    public function billingWasUpdated()
    {
        ray("billingWasUpdated event fired, resetting displayingCreateSubscription");

        $this->displayingCreateSubscription = false;
    }

    public function confirmCancelSubscription()
    {
        $this->resetErrorBag();

        $this->password = '';

        $this->dispatchBrowserEvent('confirming-cancel-subscription');

        $this->confirmingCancelSubscription = true;
    }

    public function cancelSubscription()
    {
        ray($this->user->subscriptions()->active()->first()->payfast_token);

        $result = PayFast::cancelSubscription(Auth::user()->subscriptions()->active()->first()->payfast_token);

        ray("Result of cancel subscription", $result);

        $this->emit('billingUpdated');

        $this->confirmingCancelSubscription = false;
    }

    /**
     * Update card
     */
    public function updateCard()
    {
        $payfast_token = $this->user->subscription('default')->payfast_token;

        $url = "https://www.payfast.co.za/eng/recurring/update/$payfast_token?return=" . config('app.url') . "/user/profile?card_updated=true";

        ray($url);

        return redirect()->to($url);
    }

    /**
     * When the selected plan changes, refresh the PayFast identifier's signature
     */
    public function updatedPlan($planId)
    {
        $this->plan = $planId;
    }

    public function displayCreateSubscription()
    {
        if ($this->user->onGenericTrial()) {
            $subscriptionStartsAt = $this->user->trialEndsAt()->addDay()->format('Y-m-d');
            $this->mergeFields = array_merge($this->mergeFields, ['amount' => 0]);
        }

        if ($this->user->subscribed('default') && $this->user->subscription('default')->onGracePeriod()) {
            $subscriptionStartsAt = $this->user->subscription('default')->ends_at->addDay()->format('Y-m-d');
        }

        if (! isset($subscriptionStartsAt)) {
            $subscriptionStartsAt = \Carbon\Carbon::now()->format('Y-m-d');
        }

        if ($this->user->subscribed('default') && $this->user->subscription('default')->onGracePeriod()) {
            $this->mergeFields = array_merge($this->mergeFields, ['amount' => 0]);
        }

        $this->identifier = PayFast::createOnsitePayment(
            (int) $this->plan,
            $subscriptionStartsAt,
            $this->mergeFields
        );

        $this->displayingCreateSubscription = true;
    }

    public function mount()
    {
        $this->user = Auth::user();
    }

    /**
     * Render the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('vendor.payfast.components.subscriptions');
    }
}
