<?php

return [
    'merchant_id' => env('PAYFAST_MERCHANT_ID'),
    'merchant_key' => env('PAYFAST_MERCHANT_KEY'),        
    'passphrase' => env('PAYFAST_PASSPHRASE'),  

    'testmode' => env('PAYFAST_TESTMODE'),

    'debug' => env('PAYFAST_DEBUG', false),

    'trial_days' => env('PAYFAST_TRIAL_DAYS', 30),

    'merchant_id_test' => env('PAYFAST_MERCHANT_ID_TEST'),
    'merchant_key_test' => env('PAYFAST_MERCHANT_KEY_TEST'),        
    'passphrase_test' => env('PAYFAST_PASSPHRASE_TEST'),
    
    'return_url' => env('PAYFAST_RETURN_URL', '/payfast/return'),
    'cancel_url' => env('PAYFAST_CANCEL_URL', '/payfast/cancel'),
    'notify_url' => env('PAYFAST_NOTIFY_URL', '/payfast/notify'),

    'callback_url' => env('PAYFAST_CALLBACK_URL', config('app.url')),
    'callback_url_test' => env('PAYFAST_CALLBACK_URL_TEST', ''),

    'plans' => [
        3 => [
            'name' => 'Monthly R 99',
            'start_date' => \Carbon\Carbon::now()->format('Y-m-d'),
            'payfast_frequency' => 3, // 3 = monthly
            'initial_amount' => 99, // For card updates or subscription reactivatitions, this should be zero
            'recurring_amount' => 99,
        ],
        6 => [
            'name' => 'Yearly R 1089',
            'start_date' => \Carbon\Carbon::now()->format('Y-m-d'),
            'payfast_frequency' => 6, // 6 = yearly
            'initial_amount' => 1089, // For card updates or subscription reactivatitions, this should be zero
            'recurring_amount' => 1089,
        ]
    ],
];