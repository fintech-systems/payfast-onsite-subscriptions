<?php

namespace FintechSystems\Payfast\Events;

use FintechSystems\Payfast\Subscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionCreated
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
     * The subscription instance.
     *
     * @var \FintechSystems\Payfast\Subscription
     */
    public $subscription;

    /**
     * The payload array.
     *
     * @var array
     */
    public $payload;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $billable
     * @param  \FintechSystems\Payfast\Subscription  $subscription
     * @param  array  $payload
     * @return void
     */
    public function __construct(Model $billable, Subscription $subscription, array $payload)
    {
        $this->billable = $billable;
        $this->subscription = $subscription;
        $this->payload = $payload;
    }
}
