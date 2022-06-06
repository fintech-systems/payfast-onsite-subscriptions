<?php

namespace FintechSystems\PayFast\Events;

use FintechSystems\PayFast\Receipt;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentSucceeded
{
    use Dispatchable;
    use SerializesModels;

    /**
     * The billable entity.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $billable;

    /**
     * The receipt instance.
     *
     * @var \FintechSystems\PayFast\Receipt
     */
    public $receipt;

    /**
     * The webhook payload.
     *
     * @var array
     */
    public $payload;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $billable
     * @param  \FintechSystems\PayFast\Receipt  $receipt
     * @param  array  $payload
     * @return void
     */
    public function __construct(Receipt $receipt, array $payload)
    {
        $this->receipt = $receipt;
        $this->payload = $payload;
    }
}
