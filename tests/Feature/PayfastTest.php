<?php

uses(\Tests\Feature\FeatureTestCase::class);
use FintechSystems\PayFast\Facades\Payfast;
use FintechSystems\PayFast\Subscription;
use Illuminate\Support\Facades\Http;

it('can fetch a unique payment identifier for a new subscription', function () {
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
        'custom_str2' => '0|monthly',
        'item_name' => config('app.name') . " Monthly Subscription",
        'email_address' => 'user@example.com',
    ];

    $signature = Payfast::generateApiSignature($pfData, Payfast::passphrase());

    $pfData = array_merge($pfData, ["signature" => $signature]);

    $identifier = Payfast::generatePaymentIdentifier($pfData);

    ray("generatePaymentIdentifier result: $identifier");

    expect(36)->toEqual(strlen($identifier));
});

test('the payfast api is responding to ping requests', function () {
    Http::fake([
        'https://api.payfast.co.za/ping' => Http::response(
            '"Payfast API"',
            200,
            ['Headers']
        ),
    ]);

    $result = Payfast::ping();

    expect($result)->toEqual('"Payfast API"');
});

it('can fetch an active subscription', function () {
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

    expect($result['data']['response']['status_text'])->toEqual(Subscription::STATUS_ACTIVE);
});

it('can fetch a paused subscription', function () {
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

    expect($result['data']['response']['status_text'])->toEqual(Subscription::STATUS_PAUSED);
});
