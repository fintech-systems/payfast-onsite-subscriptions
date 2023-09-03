<?php

namespace FintechSystems\PayFast\Components;

use FintechSystems\PayFast\Facades\Payfast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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

    public $afterTrialNextDueDate;

    protected $listeners = [
        'billingUpdated' => 'billingWasUpdated',
    ];

    /**
     * After billing is updated, that means when PayFast onsite subscription modal goes
     * away, the front-end must reflect the changes that could be a new subscription
     * or the receipt that was updated when a paying also came in.
     */
    public function billingWasUpdated()
    {
        $this->emitTo('receipts', 'refreshComponent');

        $this->displayingCreateSubscription = false;
    }

    public function confirmCancelSubscription()
    {
        $this->resetErrorBag();

        $this->password = '';

        $this->dispatchBrowserEvent('confirming-cancel-subscription');

        $this->confirmingCancelSubscription = true;
    }

    public function cancelSubscription(): void
    {
        Payfast::debug('Cancelling subscription for ' . $this->user->subscriptions()->active()->first()->payfast_token, 'warning');

        $this->user->subscription('default')->cancel2();

        $this->dispatch('billingUpdated');

        $this->confirmingCancelSubscription = false;
    }

    /**
     * Update card
     */
    public function updateCard(): RedirectResponse
    {
        $payfast_token = $this->user->subscription('default')->payfast_token;

        ray("updateCard has been called with this token: $payfast_token");

        $url = Payfast::url() . "/recurring/update/$payfast_token?return=" . Payfast::updateCardCallbackUrl() . "/user/profile?card_updated=true";

        $message = "updateCard is going to redirect()->to this URL: " . $url;

        Log::debug($message);

        Log::debug($url);

        ray($message);

        ray($url);

        return redirect()->to($url);
    }

    /**
     * When the selected plan changes, refresh the PayFast identifier's signature
     * and UI value which indicates when the plan will be payable next. The next
     * payable date depends on if the user has chosen a monthly or yearly sub.
     */
    public function updatedPlan($planId)
    {
        $this->plan = $planId;

        if ($this->user->onGenericTrial()) {
            if ((int) $this->plan == 3) {
                $this->afterTrialNextDueDate = $this->user->trialEndsAt()->addMonth()->addDay()->format('jS \o\f F Y');
            }

            if ((int) $this->plan == 6) {
                $this->afterTrialNextDueDate = $this->user->trialEndsAt()->addYear()->addDay()->format('jS \o\f F Y');
            }
        }
    }

    /**
     * Displays the Payfast modal with all the correct form values
     */
    public function displayCreateSubscription()
    {
        // User's trial has been activated but they have never been a subscriber
        if ($this->user->onGenericTrial() && ! $this->user->subscribed('default')) {
            $billingDate = $this->user->trialEndsAt()->addDay();

            if ((int) $this->plan == 3) {
                $billingDate = $billingDate->addMonth();
            }

            if ((int) $this->plan == 6) {
                $billingDate = $billingDate->addYear();
            }

            $billingDate = $billingDate->format('Y-m-d');
        }

        // User has or has had an active subscription but is still in a trial period
        if ($this->user->subscribed('default') && $this->user->subscription('default')->onGracePeriod()) {
            $billingDate = $this->user->subscription('default')->ends_at->addDay()->format('Y-m-d');
        }

        if (! isset($billingDate)) {
            $billingDate = \Carbon\Carbon::now()->format('Y-m-d');
        }

        if ($this->user->subscribed('default') && $this->user->subscription('default')->onGracePeriod()) {
            $this->mergeFields = array_merge($this->mergeFields, ['amount' => 0]);
        }

        $this->identifier = Payfast::createOnsitePayment(
            (int) $this->plan,
            $billingDate,
            $this->mergeFields
        );

        $this->displayingCreateSubscription = true;
    }

    public function mount()
    {
        $this->user = Auth::user();

        if ($this->user->onGenericTrial()) {
            $this->afterTrialNextDueDate = $this->user->trialEndsAt()->addMonth()->addDay()->format('jS \o\f F Y');
        }
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
