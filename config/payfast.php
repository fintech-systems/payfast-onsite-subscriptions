<?php

return [
    'merchant_id' => env('PAYFAST_MERCHANT_ID'),
    'merchant_key' => env('PAYFAST_MERCHANT_KEY'),    
    'passphrase' => env('PAYFAST_PASSPHRASE'),    
    'testmode' => env('PAYFAST_TESTMODE'),
    'return_url' => env('PAYFAST_RETURN_URL', config('app.url') . '/payfast/return'),
    'cancel_url' => env('PAYFAST_CANCEL_URL', config('app.url') . '/payfast/cancel'),
    'notify_url' => env('PAYFAST_NOTIFY_URL', config('app.url') . '/payfast/notify'),
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