<?php

namespace FintechSystems\PayFast;

use Carbon\Carbon;
use DateTimeInterface;
use Exception;
use FintechSystems\PayFast\Concerns\Prorates;
use FintechSystems\PayFast\Facades\PayFast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use LogicException;

/**
 * @property \FintechSystems\PayFast\Billable $billable
 */
class Subscription extends Model
{
    use Prorates;

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_TRIALING = 'trialing';
    public const STATUS_PAST_DUE = 'past_due';
    public const STATUS_PAUSED = 'PAUSED';
    public const STATUS_DELETED = 'CANCELLED';

    public static function uiOptions()
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_TRIALING => 'Trialing',
            self::STATUS_PAST_DUE => 'Past Due',
            self::STATUS_PAUSED => 'Paused',
            self::STATUS_DELETED => 'Cancelled',
        ];
    }

    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'token' => 'string',
        'plan_id' => 'integer',
        'next_bill_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'paused_from' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * The cached Paddle info for the subscription.
     *
     * @var array
     */
    protected $payfastInfo;

    /**
     * Get the billable model related to the subscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function billable()
    {
        return $this->morphTo();
    }

    /**
     * Get all of the receipts for the Billable model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function receipts()
    {
        return $this->hasMany(Cashier::$receiptModel, 'payfast_token', 'token')->orderByDesc('created_at');
    }

    /**
     * Determine if the subscription has a specific plan.
     *
     * @param  int  $plan
     * @return bool
     */
    public function hasPlan($plan)
    {
        return $this->plan_id == $plan;
    }

    /**
     * Determine if the subscription is active, on trial, or within its grace period.
     *
     * @return bool
     */
    public function valid()
    {
        return $this->active() || $this->onTrial() || $this->onPausedGracePeriod() || $this->onGracePeriod();
    }

    /**
     * Determine if the subscription is active.
     *
     * @return bool
     */
    public function active()
    {
        return (is_null($this->ends_at) || $this->onGracePeriod() || $this->onPausedGracePeriod()) &&
            (! Cashier::$deactivatePastDue || $this->payfast_status !== self::STATUS_PAST_DUE) &&
            $this->payfast_status !== self::STATUS_PAUSED;
    }

    /**
     * Filter query by active.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeActive($query)
    {
        $query->where(function ($query) {
            $query->whereNull('ends_at')
                ->orWhere(function ($query) {
                    $query->onGracePeriod();
                })
                ->orWhere(function ($query) {
                    $query->onPausedGracePeriod();
                });
        })->where('payfast_status', '!=', self::STATUS_PAUSED);

        if (Cashier::$deactivatePastDue) {
            $query->where('payfast_status', '!=', self::STATUS_PAST_DUE);
        }
    }

    /**
     * Determine if the subscription is past due.
     *
     * @return bool
     */
    public function pastDue()
    {
        return $this->payfast_status === self::STATUS_PAST_DUE;
    }

    /**
     * Filter query by past due.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopePastDue($query)
    {
        $query->where('payfast_status', self::STATUS_PAST_DUE);
    }

    /**
     * Determine if the subscription is recurring and not on trial.
     *
     * @return bool
     */
    public function recurring()
    {
        return ! $this->onTrial() && ! $this->paused() && ! $this->onPausedGracePeriod() && ! $this->cancelled();
    }

    /**
     * Filter query by recurring.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeRecurring($query)
    {
        $query->notOnTrial()->notCancelled();
    }

    /**
     * Determine if the subscription is paused.
     *
     * @return bool
     */
    public function paused()
    {
        return $this->payfast_status === self::STATUS_PAUSED;
    }

    /**
     * Filter query by paused.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopePaused($query)
    {
        $query->where('payfast_status', self::STATUS_PAUSED);
    }

    /**
     * Filter query by not paused.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeNotPaused($query)
    {
        $query->where('payfast_status', '!=', self::STATUS_PAUSED);
    }

    /**
     * Determine if the subscription is within its grace period after being paused.
     *
     * @return bool
     */
    public function onPausedGracePeriod()
    {
        return $this->paused_from && $this->paused_from->isFuture();
    }

    /**
     * Filter query by on trial grace period.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeOnPausedGracePeriod($query)
    {
        $query->whereNotNull('paused_from')->where('paused_from', '>', Carbon::now());
    }

    /**
     * Filter query by not on trial grace period.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeNotOnPausedGracePeriod($query)
    {
        $query->whereNull('paused_from')->orWhere('paused_from', '<=', Carbon::now());
    }

    /**
     * Determine if the subscription is no longer active.
     *
     * @return bool
     */
    public function cancelled()
    {
        return ! is_null($this->ends_at);
    }

    /**
     * Filter query by cancelled.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeCancelled($query)
    {
        $query->whereNotNull('ends_at');
    }

    /**
     * Filter query by not cancelled.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeNotCancelled($query)
    {
        $query->whereNull('ends_at');
    }

    /**
     * Determine if the subscription has ended and the grace period has expired.
     *
     * @return bool
     */
    public function ended()
    {
        return $this->cancelled() && ! $this->onGracePeriod();
    }

    /**
     * Filter query by ended.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeEnded($query)
    {
        $query->cancelled()->notOnGracePeriod();
    }

    /**
     * Determine if the subscription is within its trial period.
     *
     * @return bool
     */
    public function onTrial()
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Filter query by on trial.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeOnTrial($query)
    {
        $query->whereNotNull('trial_ends_at')->where('trial_ends_at', '>', Carbon::now());
    }

    /**
     * Filter query by not on trial.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeNotOnTrial($query)
    {
        $query->whereNull('trial_ends_at')->orWhere('trial_ends_at', '<=', Carbon::now());
    }

    /**
     * Determine if the subscription is within its grace period after cancellation.
     *
     * @return bool
     */
    public function onGracePeriod()
    {
        return $this->ends_at && $this->ends_at->isFuture();
    }

    /**
     * Filter query by on grace period.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeOnGracePeriod($query)
    {
        $query->whereNotNull('ends_at')->where('ends_at', '>', Carbon::now());
    }

    /**
     * Filter query by not on grace period.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeNotOnGracePeriod($query)
    {
        $query->whereNull('ends_at')->orWhere('ends_at', '<=', Carbon::now());
    }

    /**
     * Perform a "one off" charge on top of the subscription for the given amount.
     *
     * @param  float  $amount
     * @param  string  $name
     * @return array
     *
     * @throws \Exception
     */
    public function charge($amount, $name)
    {
        if (strlen($name) > 50) {
            throw new Exception('Charge name has a maximum length of 50 characters.');
        }

        $payload = $this->billable->payfastOptions([
            'amount' => $amount,
            'charge_name' => $name,
        ]);

        $this->payfastInfo = null;

        return Cashier::post("/subscription/{$this->paddle_id}/charge", $payload)['response'];
    }

    /**
     * Increment the quantity of the subscription.
     *
     * @param  int  $count
     * @return $this
     */
    public function incrementQuantity($count = 1)
    {
        $this->updateQuantity($this->quantity + $count);

        return $this;
    }

    /**
     *  Increment the quantity of the subscription, and invoice immediately.
     *
     * @param  int  $count
     * @return $this
     */
    public function incrementAndInvoice($count = 1)
    {
        $this->updateQuantity($this->quantity + $count, [
            'bill_immediately' => true,
        ]);

        return $this;
    }

    /**
     * Decrement the quantity of the subscription.
     *
     * @param  int  $count
     * @return $this
     */
    public function decrementQuantity($count = 1)
    {
        return $this->updateQuantity(max(1, $this->quantity - $count));
    }

    /**
     * Update the quantity of the subscription.
     *
     * @param  int  $quantity
     * @param  array  $options
     * @return $this
     */
    public function updateQuantity($quantity, array $options = [])
    {
        $this->guardAgainstUpdates('update quantities');

        $this->updatePaddleSubscription(array_merge($options, [
            'quantity' => $quantity,
            'prorate' => $this->prorate,
        ]));

        $this->forceFill([
            'quantity' => $quantity,
        ])->save();

        $this->payfastInfo = null;

        return $this;
    }

    /**
     * Swap the subscription to a new Paddle plan.
     *
     * @param  int  $plan
     * @param  array  $options
     * @return $this
     */
    public function swap($plan, array $options = [])
    {
        $this->guardAgainstUpdates('swap plans');

        $this->updatePaddleSubscription(array_merge($options, [
            'plan_id' => $plan,
            'prorate' => $this->prorate,
        ]));

        $this->forceFill([
            'paddle_plan' => $plan,
        ])->save();

        $this->payfastInfo = null;

        return $this;
    }

    /**
     * Swap the subscription to a new Paddle plan, and invoice immediately.
     *
     * @param  int  $plan
     * @param  array  $options
     * @return $this
     */
    public function swapAndInvoice($plan, array $options = [])
    {
        return $this->swap($plan, array_merge($options, [
            'bill_immediately' => true,
        ]));
    }

    /**
     * Pause the subscription.
     *
     * @return $this
     */
    public function pause()
    {
        $this->updatePayFastSubscription([
            'pause' => true,
        ]);

        $info = $this->payfastInfo();

        $this->forceFill([
            'payfast_status' => $info['state'],
            'paused_from' => Carbon::createFromFormat('Y-m-d H:i:s', $info['paused_from'], 'UTC'),
        ])->save();

        $this->payfastInfo = null;

        return $this;
    }

    /**
     * Resume a paused subscription.
     *
     * @return $this
     */
    public function unpause()
    {
        $this->updatePaddleSubscription([
            'pause' => false,
        ]);

        $this->forceFill([
            'payfast_status' => self::STATUS_ACTIVE,
            'ends_at' => null,
            'paused_from' => null,
        ])->save();

        $this->payfastInfo = null;

        return $this;
    }

    // /**
    //  * Update the underlying Paddle subscription information for the model.
    //  *
    //  * @param  array  $options
    //  * @return array
    //  */
    // public function updatePaddleSubscription(array $options)
    // {
    //     $payload = $this->billable->paddleOptions(array_merge([
    //         'subscription_id' => $this->paddle_id,
    //     ], $options));

    //     $response = Cashier::post('/subscription/users/update', $payload)['response'];

    //     $this->payfastInfo = null;

    //     return $response;
    // }

    /**
     * Update the underlying PayFast subscription information for the model.
     */
    public function updatePayFastSubscription(array $result)
    {
        if ($result['status'] !== 'success') {
            $message = 'Unable to update PayFast subscription because API result !== success';

            Log::error($message);

            ray($message);

            $message = 'Result will follow';

            Log::error($message);

            ray($message);

            Log::debug($result);

            ray($result);
        }

        $subscription = Subscription::where(
            'payfast_token',
            $result['data']['response']['token']
        )->firstOrFail();

        $subscription->payfast_status = $result['data']['response']['status_text'];

        $subscription->next_bill_at = $result['data']['response']['run_date'];

        if ($subscription->payfast_status == self::STATUS_DELETED && ! $subscription->cancelled_at) {
            $message = ("Subscription status at PayFast is cancelled but no cancelled_at exists. Adding now() as cancellation date.");

            Log::warning($message);

            ray($message)->orange();

            $subscription->cancelled_at = now();

            // $subscription->ended_at = now();
        }

        $subscription->save();
    }

    /**
     * Get the PayFast update url. Not in use, copied from Laravel Cashier Paddle.
     *
     * @return array
     */
    public function updateUrl()
    {
        return $this->payfastInfo()['update_url'];
    }

    /**
     * Begin creating a new modifier.
     *
     * @param  float  $amount
     * @return \Laravel\Paddle\ModifierBuilder
     */
    public function newModifier($amount)
    {
        return new ModifierBuilder($this, $amount);
    }

    /**
     * Get all of the modifiers for this subscription.
     *
     * @return \Illuminate\Support\Collection
     */
    public function modifiers()
    {
        $result = Cashier::post('/subscription/modifiers', array_merge([
            'subscription_id' => $this->paddle_id,
        ], $this->billable->payfastOptions()));

        return collect($result['response'])->map(fn (array $modifier) => new Modifier($this, $modifier));
    }

    /**
     * Get a modifier instance by ID.
     *
     * @param  int  $id
     * @return \Laravel\Paddle\Modifier|null
     */
    public function modifier($id)
    {
        return $this->modifiers()->first(fn (Modifier $modifier) => $modifier->id() === $id);
    }

    /**
     * Cancel the subscription at the end of the current billing period.
     *
     * @return $this
     */
    public function cancel()
    {
        if ($this->onGracePeriod()) {
            return $this;
        }

        if ($this->onPausedGracePeriod() || $this->paused()) {
            $endsAt = $this->paused_from->isFuture()
                ? $this->paused_from
                : Carbon::now();
        } else {
            $endsAt = $this->onTrial()
                ? $this->trial_ends_at
                : $this->nextPayment()->date();
        }

        return $this->cancelAt($endsAt);
    }

    /**
     * Cancel the subscription at the end of the current billing period.
     *
     * @return $this
     */
    public function cancel2()
    {
        if ($this->onGracePeriod()) {
            return $this;
        }

        if ($this->onPausedGracePeriod() || $this->paused()) {
            $endsAt = $this->paused_from->isFuture()
                ? $this->paused_from
                : Carbon::now();
        } else {
            $endsAt = $this->onTrial()
                ? $this->trial_ends_at
                // : $this->nextPayment()->date();
                : $this->runDate()->date()->subDay(1);
        }

        return $this->cancelAt2($endsAt);
    }

    /**
     * Cancel the subscription immediately.
     *
     * @return $this
     */
    public function cancelNow()
    {
        return $this->cancelAt(Carbon::now());
    }

    /**
     * Cancel the subscription at a specific moment in time.
     *
     * Paddle version but shouldn't be in use anymore in lieu of cancelAt2
     *
     * @param  \DateTimeInterface  $endsAt
     * @return $this
     */
    public function cancelAt(DateTimeInterface $endsAt)
    {
        $payload = $this->billable->payfastOptions([
            'subscription_id' => $this->paddle_id,
        ]);

        Cashier::post('/subscription/users_cancel', $payload);

        $this->forceFill([
            'payfast_status' => self::STATUS_DELETED,
            'ends_at' => $endsAt,
        ])->save();

        $this->payfastInfo = null;

        return $this;
    }

    /**
     * Cancel the subscription at a specific moment in time.
     *
     * This is the PayFast version. It calls the PayFast API instead of the Cashier::post method
     * and it also adds a cancelled_at field which is non-default to the standard Cashier
     * fields. This fields is useful for UI output to reminder user when they cancelled.
     *
     * @param  \DateTimeInterface  $endsAt
     * @return $this
     */
    public function cancelAt2(DateTimeInterface $endsAt)
    {
        PayFast::cancelSubscription($this->payfast_token);

        $this->forceFill([
            'payfast_status' => self::STATUS_DELETED,
            'ends_at' => $endsAt,
            'cancelled_at' => now(),
        ])->save();

        $this->payfastInfo = null;

        return $this;
    }

    /**
     * Get the PayFast cancellation url. Not in use, copied from Laravel Cashier Paddle.
     *
     * @return array
     */
    public function cancelUrl()
    {
        return $this->payfastInfo()['cancel_url'];
    }

    /**
     * Get the last payment for the subscription.
     *
     * @return \Laravel\Paddle\Payment
     */
    public function lastPayment()
    {
        $payment = $this->payfastInfo()['last_payment'];

        return new Payment($payment['amount'], $payment['currency'], $payment['date']);
    }

    /**
     * Get the next payment for the subscription.
     *
     * This is the paddle version. Do not use.
     *
     * We're now using the PayFast version called 'runDate()'
     *
     * @return \Laravel\Paddle\Payment|null
     *
     */
    public function nextPayment()
    {
        if (! isset($this->payfastInfo()['next_payment'])) {
            return;
        }

        $payment = $this->payfastInfo()['next_payment'];

        return new Payment($payment['amount'], $payment['currency'], $payment['date']);
    }

    /**
     * Get the next payment for the subscription.
     *
     * This is the PayFast version. In fixes the currency to ZAR and strips
     * the date of the time portion which in normally returned like
     * this: 2022-11-01T00:00:00+02:00 for use in Payment
     *
     *
     * @return \FintechSystems\PayFast\Payment|null
     */
    public function runDate()
    {
        if (! isset($this->payfastInfo()['run_date'])) {
            return;
        }

        $dateOnly = substr($this->payfastInfo()['run_date'], 0, 10);

        $payment['date'] = Carbon::createFromFormat('Y-m-d', $dateOnly)->toDateString();
        $payment['currency'] = 'ZAR';
        $payment['amount'] = $this->payfastInfo()['amount'];

        return new Payment($payment['amount'], $payment['currency'], $payment['date']);
    }

    /**
     * Get the email address of the customer associated to this subscription.
     *
     * @return string
     */
    public function payfastEmail()
    {
        return (string) $this->payfastInfo()['user_email'];
    }

    /**
     * Get the payment method type from the subscription.
     *
     * @return string
     */
    public function paymentMethod()
    {
        return (string) ($this->payfastInfo()['payment_information']['payment_method'] ?? '');
    }

    /**
     * Get the card brand from the subscription.
     *
     * @return string
     */
    public function cardBrand()
    {
        return (string) ($this->payfastInfo()['payment_information']['card_type'] ?? '');
    }

    /**
     * Get the last four digits from the subscription if it's a credit card.
     *
     * @return string
     */
    public function cardLastFour()
    {
        return (string) ($this->payfastInfo()['payment_information']['last_four_digits'] ?? '');
    }

    /**
     * Get the card expiration date.
     *
     * @return string
     */
    public function cardExpirationDate()
    {
        return (string) ($this->payfastInfo()['payment_information']['expiry_date'] ?? '');
    }

    /**
     * Get raw information about the subscription from PayFast.
     *
     * This is based on paddleInfo() from the original Laravel Cashier code for Paddle. It calls the
     * PayFast API and then returns the 'response' array in the 'data' array of the response object
     * This will contain pertinent information about the subscription on record at PayFast
     *
     * @return array
     */
    public function payfastInfo()
    {
        if ($this->payfastInfo) {
            return $this->payfastInfo;
        }

        return $this->payfastInfo = PayFast::fetchSubscription($this->payfast_token)['data']['response'];

        // return $this->payfastInfo = Cashier::post('/subscription/users', array_merge([
        //     'subscription_id' => $this->paddle_id,
        // ], $this->billable->payfastOptions()))['response'][0];
    }

    /**
     * Perform a guard check to prevent change for a specific action.
     *
     * @param  string  $action
     * @return void
     *
     * @throws \LogicException
     */
    public function guardAgainstUpdates($action): void
    {
        if ($this->onTrial()) {
            throw new LogicException("Cannot $action while on trial.");
        }

        if ($this->paused() || $this->onPausedGracePeriod()) {
            throw new LogicException("Cannot $action for paused subscriptions.");
        }

        if ($this->cancelled() || $this->onGracePeriod()) {
            throw new LogicException("Cannot $action for cancelled subscriptions.");
        }

        if ($this->pastDue()) {
            throw new LogicException("Cannot $action for past due subscriptions.");
        }
    }

    /**
     * PayFast frequencies
     */
    public static function frequencies($frequency)
    {
        return match ($frequency) {
            3 => 'Monthly',
            4 => 'Quarterly',
            5 => 'Biannually',
            6 => 'Annual'
        };
    }
}
