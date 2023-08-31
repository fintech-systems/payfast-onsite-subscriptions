<?php

namespace FintechSystems\Payfast\Events;

use FintechSystems\Payfast\Receipt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionPaymentSucceeded
{
    use Dispatchable;
    use SerializesModels;

    /**
     * The billable entity.
     *
     * @var Model
     */
    public $billable;

    /**
     * The receipt instance.
     *
     * @var Receipt
     */
    public Receipt $receipt;

    /**
     * The webhook payload.
     *
     * @var array
     */
    public array $payload;

    /**
     * Create a new event instance.
     *
     * @param Model $billable
     * @param Receipt $receipt
     * @param  array  $payload
     * @return void
     */
    public function __construct(Model $billable, Receipt $receipt, array $payload)
    {
        $this->billable = $billable;
        $this->receipt = $receipt;
        $this->payload = $payload;
    }

    /**
     * Indicates whether it is the customer’s first payment for this subscription.
     *
     * @return bool
     */
    public function isInitialPayment(): bool
    {
        return $this->payload['initial_payment'] === 1;
    }
}
