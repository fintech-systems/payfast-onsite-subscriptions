<?php

namespace FintechSystems\PayFast\Tests;

use FintechSystems\PayFast\Facades\PayFast;
use Illuminate\Support\Facades\Http;

class PayFastTest extends TestCase
{
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
}
