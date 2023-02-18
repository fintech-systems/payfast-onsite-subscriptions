<?php

namespace Tests\Feature;

use FintechSystems\PayFast\Receipt;
use Money\Currency;

class ReceiptsTest extends FeatureTestCase
{
    public function test_it_can_returns_its_amount_and_currency()
    {
        $receipt = new Receipt([
            'amount' => '12.45',
            'tax' => '4.36',
            'currency' => 'ZAR',
        ]);

        $this->assertSame('12.45', $receipt->amount);
        $this->assertInstanceOf(Currency::class, $receipt->currency());
        $this->assertSame('ZAR', $receipt->currency()->getCode());
    }
}
