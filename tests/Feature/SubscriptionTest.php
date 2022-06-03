<?php

namespace FintechSystems\PayFast\Tests;

use Illuminate\Support\Facades\Http;
use FintechSystems\Payfast\Facades\PayFast;

class SubscriptionTest extends TestCase
{
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
                    ]
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
                    ]
                ]
            ),
        ]);

        $result = PayFast::fetchSubscription("1294009b-3778-420f-8ddc-aac0f9c8b477");

        ray($result);

        $this->assertEquals("PAUSED", $result['data']['response']['status_text']);
    }
}
