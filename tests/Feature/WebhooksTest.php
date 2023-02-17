<?php

namespace Tests\Feature;

use FintechSystems\PayFast\Cashier;
use FintechSystems\PayFast\Events\PaymentSucceeded;
use FintechSystems\PayFast\Events\SubscriptionCancelled;
use FintechSystems\PayFast\Events\SubscriptionCreated;
use FintechSystems\PayFast\Events\SubscriptionPaymentSucceeded;
use FintechSystems\PayFast\Events\SubscriptionUpdated;
use FintechSystems\PayFast\Subscription;
use Illuminate\Support\Facades\Http;

class WebhooksTest extends FeatureTestCase
{
    public function test_gracefully_handle_webhook_without_alert_name()
    {
        $this->postJson('payfast/webhook', [
            'ping' => now(),
        ])->assertOk();
    }    
}
