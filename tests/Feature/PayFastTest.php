<?php

namespace FintechSystems\PayFast\Tests;

use FintechSystems\PayFast\Facades\PayFast;
use Illuminate\Support\Facades\Http;
use Tests\Feature\FeatureTestCase;

class PayFastTest extends FeatureTestCase
{
    /** @test */
    public function it_can_fetch_a_unique_payment_identifier_for_a_new_subscription()
    {
        Http::fake([
            'https://www.payfast.co.za/onsite/process' => Http::response(
                [
                    "uuid" => "12345678-1234-1234-1234-123456789012",
                ]
            ),
        ]);

        $pfData = [
            'merchant_id' => '13741656',
            'merchant_key' => 'gsps17qv8giri',
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

        $signature = PayFast::generateApiSignature($pfData, config('payfast.passphrase'));

        $pfData = array_merge($pfData, ["signature" => $signature]);

        $identifier = PayFast::generatePaymentIdentifier($pfData);

        $this->assertEquals(strlen($identifier), 36);
    }

    /** @test */
    public function the_payfast_api_is_responding_to_ping_requests()
    {
        Http::fake([
            'https://api.payfast.co.za/ping' => Http::response(
                '"PayFast API"',
                200,
                ['Headers']
            ),
        ]);

        $result = PayFast::ping();

        $this->assertEquals('"PayFast API"', $result);
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
                            "token" => "f89aac35-a817-48b6-9c7a-6d18cb7958d4",
                        ],
                    ],
                ]
            ),
        ]);

        $result = PayFast::fetchSubscription("f89aac35-a817-48b6-9c7a-6d18cb7958d4");

        $this->assertEquals("ACTIVE", $result['data']['response']['status_text']);
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

        $result = PayFast::fetchSubscription("1294009b-3778-420f-8ddc-aac0f9c8b477");

        $this->assertEquals("PAUSED", $result['data']['response']['status_text']);
    }
}
