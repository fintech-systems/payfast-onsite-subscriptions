<?php

namespace FintechSystems\Payfast;

use Spatie\Url\Url;

class SubscriptionBuilder
{
    /**
     * The Billable model that is subscribing.
     *
     * @var Billable
     */
    protected Billable $billable;

    /**
     * The name of the subscription.
     *
     * @var string
     */
    protected string $name;

    /**
     * The plan of the subscription.
     *
     * @var int
     */
    protected int $plan;

    /**
     * The quantity of the subscription.
     *
     * @var int
     */
    protected int $quantity = 1;

    /**
     * The days until the trial will expire.
     *
     * @var int|null
     */
    protected ?int $trialDays;

    /**
     * Indicates that the trial should end immediately.
     *
     * @var bool
     */
    protected bool $skipTrial = false;

    /**
     * The coupon code being applied to the customer.
     *
     * @var string|null
     */
    protected ?string $coupon;

    /**
     * The metadata to apply to the subscription.
     *
     * @var array
     */
    protected array $metadata = [];

    /**
     * The return url which will be triggered upon starting the subscription.
     *
     * @var string|null
     */
    protected ?string $returnTo;

    /**
     * Create a new subscription builder instance.
     *
     * @param Billable $billable
     * @param string $name
     * @param int $plan
     * @return void
     */
    public function __construct(Billable $billable, string $name, int $plan)
    {
        $this->name = $name;
        $this->plan = $plan;
        $this->billable = $billable;
    }

    /**
     * Specify the quantity of the subscription.
     *
     * @param  int  $quantity
     * @return $this
     */
    public function quantity($quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Specify the number of days for the trial.
     *
     * @param int $trialDays
     * @return $this
     */
    public function trialDays(int $trialDays): static
    {
        $this->trialDays = $trialDays;

        return $this;
    }

    /**
     * Force the trial to end immediately.
     *
     * @return $this
     */
    public function skipTrial(): static
    {
        $this->skipTrial = true;

        return $this;
    }

    /**
     * The coupon to apply to a new subscription.
     *
     * @param  string  $coupon
     * @return $this
     */
    public function withCoupon($coupon): static
    {
        $this->coupon = $coupon;

        return $this;
    }

    /**
     * The metadata to apply to a new subscription.
     *
     * @param  array  $metadata
     * @return $this
     */
    public function withMetadata(array $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * The return url which will be triggered upon starting the subscription.
     *
     * @param string $returnTo
     * @param string $checkoutParameter
     * @return $this
     */
    public function returnTo(string $returnTo, string $checkoutParameter = 'checkout'): static
    {
        $this->returnTo = (string) Url::fromString($returnTo)
            ->withQueryParameter($checkoutParameter, '{checkout_hash}');

        return $this;
    }

    /**
     * Generate a pay link for a subscription.
     *
     * TODO This has very specific Paddle code and should be removed
     *
     * @param  array  $options
     * @return string
     */
    public function create(array $options = []): string
    {
        $payload = array_merge($this->buildPayload(), $options);

        if (! is_null($trialDays = $this->getTrialEndForPayload())) {
            $payload['trial_days'] = $trialDays;

            // Paddle will immediately charge the plan price for the trial days so we'll
            // need to explicitly set the prices to 0 for the first charge. If there's
            // no trial, we use the recurring_prices to charge the user immediately.
            $payload['prices'] = $payload['prices']
                ?? $this->getPlanPricesForPayload($trialDays !== 0);
        }

        $payload['passthrough'] = array_merge($this->metadata, [
            'subscription_name' => $this->name,
        ]);

        return $this->billable->chargeProduct($this->plan, $payload);
    }

    /**
     * Build the payload for subscription creation.
     *
     * TODO This looks like Paddle specific code and should be removed
     *
     * @return array
     */
    protected function buildPayload(): array
    {
        return [
            'coupon_code' => (string) $this->coupon,
            'quantity' => $this->quantity,
            'return_url' => $this->returnTo,
        ];
    }

    /**
     * Get the days until the trial will expire for the Paddle payload.
     *
     * @return int|null
     */
    protected function getTrialEndForPayload(): ?int
    {
        if ($this->skipTrial) {
            return 0;
        }

        return $this->trialDays;
    }

    /**
     * Get the plan prices for the Payfast payload.
     *
     * @param bool $trialing
     * @return array
     */
    protected function getPlanPricesForPayload(bool $trialing = true): array
    {
        $plan = Cashier::post(
            '/subscription/plans',
            $this->billable->payfastOptions(['plan' => $this->plan])
        )['response'][0];

        return collect($plan[$trialing ? 'initial_price' : 'recurring_price'])
            ->map(function ($price, $currency) use ($trialing) {
                $price = $trialing ? 0 : $price;

                return $currency.':'.$price;
            })
            ->values()
            ->all();
    }
}
