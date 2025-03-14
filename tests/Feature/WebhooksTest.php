<?php

uses(\Tests\Feature\FeatureTestCase::class);

test('gracefully handle webhook without alert name', function () {
    $this->postJson('payfast/webhook', [
        'ping' => now(),
    ])->assertOk();
});

test('payfast ITN returns on new subscriptions triggers the webhook without any errors', function () {
    $testData = [
        "m_payment_id" => "6-20250314174629",
  "pf_payment_id" => "2452165",
  "payment_status" => "COMPLETE",
  "item_name" => "Best Agent Local Monthly Subscription",
  "item_description" => "Best Agent Local Subscription",
  "amount_gross" => "690.00",
  "amount_fee" => "-15.87",
  "amount_net" => "674.13",
  "custom_str1" => "App\Models\User",
  "custom_str2" => "0|monthly",
  "custom_str3" => null,
  "custom_str4" => null,
  "custom_str5" => null,
  "custom_int1" => "2168",
  "custom_int2" => null,
  "custom_int3" => null,
  "custom_int4" => null,
  "custom_int5" => null,
  "name_first" => "Dúvan",
  "name_last" => "Botha",
  "email_address" => "duvan.botha@remax-unlimited.co.za",
  "merchant_id" => "10037315",
  "token" => "36a97ae2-2109-4213-bea6-fe912e0bfe60",
  "billing_date" => "2025-03-14",
  "signature" => "47f23827fe65e5bb88adfa8467f8d180",
    ];

    $this->postJson('payfast/webhook', [
        'ping' => now(),
    ])->assertOk();
});

