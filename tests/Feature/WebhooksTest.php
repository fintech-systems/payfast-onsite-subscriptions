<?php

namespace Tests\Feature;

class WebhooksTest extends FeatureTestCase
{
    public function test_gracefully_handle_webhook_without_alert_name()
    {
        $this->postJson('payfast/webhook', [
            'ping' => now(),
        ])->assertOk();
    }    
}
