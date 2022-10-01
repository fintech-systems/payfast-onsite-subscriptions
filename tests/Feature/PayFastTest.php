<?php

namespace FintechSystems\PayFast\Tests;

use Carbon\Carbon;
use Tests\Feature\FeatureTestCase;
use Illuminate\Support\Facades\Http;
use FintechSystems\PayFast\Subscription;
use FintechSystems\PayFast\Facades\PayFast;

class PayFastTest extends FeatureTestCase
{
    /**
     * @test
     *
     * This test fails when using test credentials. Instead of returning
     * a UUID it returns HTML to the payment processing page.
     * Set testmode in phpunit.xml to false to test.
     *
     * Additionally Http::fake doesn't work in test mode
    */
    public function it_can_fetch_a_unique_payment_identifier_for_a_new_subscription()
    {
        Http::fake([
            PayFast::url() => Http::response(
                [
                    "uuid" => "12345678-0123-5678-0123-567890123456",
                ]
            ),
        ]);

        $pfData = [
            'merchant_id' => PayFast::merchantId(),
            'merchant_key' => PayFast::merchantKey(),
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

        $signature = PayFast::generateApiSignature($pfData, PayFast::passphrase());

        $pfData = array_merge($pfData, ["signature" => $signature]);

        $identifier = PayFast::generatePaymentIdentifier($pfData);

        ray("generatePaymentIdentifier result: $identifier");

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
                            "token" => "667b8608-38bd-4513-8c49-250ce836876a",
                        ],
                    ],
                ]
            ),
        ]);

        $result = PayFast::fetchSubscription("667b8608-38bd-4513-8c49-250ce836876a");

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

        $result = PayFast::fetchSubscription("1294009b-3778-420f-8ddc-aac0f9c8b477");

        $this->assertEquals(Subscription::STATUS_PAUSED, $result['data']['response']['status_text']);
    }

    /** @test */
    public function it_can_create_a_subscription_and_then_cancel_it()
    {
        $token = "293eb64a-9c8b-497b-9421-d0d5b2554f3c";

        $billable = $this->createBillable('taylor');

        $subscription = $billable->subscriptions()->create([
            'name' => 'main',
            'payfast_token' => $token,
            'plan_id' => 2323,
            'payfast_status' => Subscription::STATUS_ACTIVE,            
        ]);
        
        $this->assertFalse($subscription->cancelled());

        // Fake fetch subscription
        Http::fake([
            "https://api.payfast.co.za/subscriptions/$token/fetch?*" => Http::response(
                [
                    'code' => 200,
                    'status' => "success",
                    'data' => [
                        'response' => [
                            'amount' => 9900,
                            'cycles' => 0,
                            'cycles_complete' => 1,
                            'frequency' => 3,
                            'run_date' => "2022-11-01T00:00:00+02:00",
                            'status' => 1,
                            "status_reason" => "",
                            "status_text" => "ACTIVE",
                            "token" => "293eb64a-9c8b-497b-9421-d0d5b2554f3c",
                        ],
                    ],
                ]
            ),
        ]);

        // Fake fetching an already cancelled subscription
        Http::fake([
            "https://api.payfast.co.za/subscriptions/$token/cancel?*" => Http::response(
                [
                    'code' => 400,
                    'status' => "failed",
                    'data' => [
                        'response' => false,
                        'message' => "Failure - The subscription status is cancelled",                                                    
                    ],
                ]
            ),
        ]);

        // Cancel launches both subscription fetch and a status fetch API calls
        $billable->subscription('main')->cancel2();
        
        $this->assertTrue($billable->subscription('main')->cancelled());
               
    }
}
