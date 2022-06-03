<?php

return [
    // 'merchant_id' => env('PAYFAST_MERCHANT_ID', '10004002'),
    'merchant_id' => env('PAYFAST_MERCHANT_ID', '13741656'),
    // 'merchant_key' => env('PAYFAST_MERCHANT_KEY', 'q1cd2rdny4a53'),
    'merchant_key' => env('PAYFAST_MERCHANT_KEY', 'gsps17qv8giri'),
    // 'passphrase' => env('PAYFAST_PASSPHRASE', 'payfast'),
    'passphrase' => env('PAYFAST_PASSPHRASE', 'Fintechsystems1'),
    'testmode' => env('PAYFAST_TESTMODE', true),
    'plans' => [
        3 => [
            'name' => 'Monthly R 99',
            'start_date' => \Carbon\Carbon::now()->format('Y-m-d'),
            'payfast_frequency' => 3, // 3 = monthly
            'initial_amount' => 5.99, // For card updates or reactivatitions, this should be zero
            'recurring_amount' => 5.99,
        ],
        6 => [
            'name' => 'Yearly R 1089',
            'start_date' => \Carbon\Carbon::now()->format('Y-m-d'),
            'payfast_frequency' => 6, // 6 = yearly
            'initial_amount' => 6.89, // For card updates or reactivatitions, this should be zero
            'recurring_amount' => 6.89,
        ]
    ],
];