<?php

namespace FintechSystems\Payfast\Events;

use FintechSystems\Payfast\Receipt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentSucceeded
{
    use Dispatchable;
    use SerializesModels;

    /**
     * The billable entity.
     *
     * @var Model
     */
    public Model $billable;

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
     * @param Receipt $receipt
     * @param array $payload
     */
    public function __construct(Receipt $receipt, array $payload)
    {
        $this->receipt = $receipt;
        $this->payload = $payload;
    }
}
