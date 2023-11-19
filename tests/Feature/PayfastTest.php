<?php

namespace FintechSystems\PayFast\Tests;

use FintechSystems\PayFast\Facades\Payfast;
use FintechSystems\PayFast\Subscription;
use Illuminate\Support\Facades\Http;
use Tests\Feature\FeatureTestCase;

class PayFastTest extends FeatureTestCase
{
    /** @test */
    public function it_can_fetch_a_unique_payment_identifier_for_a_new_subscription()
    {
        Http::fake([
            Payfast::url() => Http::response(
                [
                    "uuid" => "12345678-0123-5678-0123-567890123456",
                ]
            ),
        ]);

        $pfData = [
            'merchant_id' => Payfast::merchantId(),
            'merchant_key' => Payfast::merchantKey(),
            'subscription_type' => 1,
            'm_payment_id' => 2,
            'amount' => 300,
            'recurring_amount' => 400,
            'billing_date' => now()->format('Y-m-d'),
            'frequency' => 3,
            'cycles' => 0,
            'custom_str1' => 'App\User',
            'custom_int1' => 1,
            'custom_int2' => 3,
            'custom_str2' => 'Monthly R 99',
            'item_name' => config('app.name') . " Monthly Subscription",
            'email_address' => 'user@example.com',
        ];

        $signature = Payfast::generateApiSignature($pfData, Payfast::passphrase());

        $pfData = array_merge($pfData, ["signature" => $signature]);

        $identifier = Payfast::generatePaymentIdentifier($pfData);

        ray("generatePaymentIdentifier result: $identifier");

        $this->assertEquals(strlen($identifier), 36);
    }

    /** @test */
    public function the_payfast_api_is_responding_to_ping_requests()
    {
        Http::fake([
            'https://api.payfast.co.za/ping' => Http::response(
                '"Payfast API"',
                200,
                ['Headers']
            ),
        ]);

        $result = Payfast::ping();

        $this->assertEquals('"Payfast API"', $result);
    }

    /** @test */
    public function it_can_fetch_an_active_subscription()
    {
        Http::fake([
            'https://api.payfast.co.za/subscriptions/*' => Http::response(
                [
                    'code' => 200,
                    'status' => "success",
                    'data' => [
                        'response' => [
                            'amount' => 599,
                            'cycles' => 0,
                            'cycles_complete' => 1,
                            'frequency' => 3,
                            'run_date' => "2022-06-30T00:00:00+02:00",
                            'status' => 1,
                            "status_reason" => "",
                            "status_text" => "ACTIVE",
                            "token" => "667b8608-38bd-4513-8c49-250ce836876a",
                        ],
                    ],
                ]
            ),
        ]);

        $result = Payfast::fetchSubscription("667b8608-38bd-4513-8c49-250ce836876a");

        $this->assertEquals(Subscription::STATUS_ACTIVE, $result['data']['response']['status_text']);
    }

    /** @test */
    public function it_can_fetch_a_paused_subscription()
    {
        Http::fake([
            'https://api.payfast.co.za/subscriptions/*' => Http::response(
                [
                    'code' => 200,
                    'status' => "success",
                    'data' => [
                        'response' => [
                            'amount' => 689,
                            'cycles' => 0,
                            'cycles_complete' => 1,
                            'frequency' => 6,
                            'run_date' => "2024-05-30T00:00:00+02:00",
                            'status' => 1,
                            "status_reason" => "",
                            "status_text" => "PAUSED",
                            "token" => "1294009b-3778-420f-8ddc-aac0f9c8b477",
                        ],
                    ],
                ]
            ),
        ]);

        $result = Payfast::fetchSubscription("1294009b-3778-420f-8ddc-aac0f9c8b477");

        $this->assertEquals(Subscription::STATUS_PAUSED, $result['data']['response']['status_text']);
    }
}
