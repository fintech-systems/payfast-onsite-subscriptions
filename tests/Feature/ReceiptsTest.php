<?php

uses(\Tests\Feature\FeatureTestCase::class);
use FintechSystems\PayFast\Receipt;
use Money\Currency;

test('it can returns its amount and currency', function () {
    $receipt = new Receipt([
        'amount' => '12.45',
        'tax' => '4.36',
        'currency' => 'ZAR',
    ]);

    expect($receipt->amount)->toBe('12.45');
    expect($receipt->currency())->toBeInstanceOf(Currency::class);
    expect($receipt->currency()->getCode())->toBe('ZAR');
});
