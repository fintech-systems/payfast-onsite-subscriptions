<?php

return [
    'merchant_id' => env('PAYFAST_MERCHANT_ID'),
    'merchant_key' => env('PAYFAST_MERCHANT_KEY'),
    'passphrase' => env('PAYFAST_PASSPHRASE'),

    'test_mode' => env('PAYFAST_TEST_MODE'),
    'test_mode_callback_url' => env('PAYFAST_TEST_MODE_CALLBACK_URL', ''),

//    'trial_days' => env('PAYFAST_TRIAL_DAYS', 30),

    'merchant_id_test' => env('PAYFAST_MERCHANT_ID_TEST'),
    'merchant_key_test' => env('PAYFAST_MERCHANT_KEY_TEST'),
    'passphrase_test' => env('PAYFAST_PASSPHRASE_TEST'),

    'debug' => env('PAYFAST_DEBUG', false),

//    'return_url' => env('PAYFAST_RETURN_URL', '/payfast/return'),
//    'cancel_url' => env('PAYFAST_CANCEL_URL', '/payfast/cancel'),
//    'notify_url' => env('PAYFAST_NOTIFY_URL', '/payfast/notify'),

//    'plans' => [
//        3 => [
//            'name' => 'Monthly R 99',
//            'start_date' => \Carbon\Carbon::now()->format('Y-m-d'),
//            'payfast_frequency' => 3, // 3 = monthly
//            'initial_amount' => 99, // For card updates or subscription reactivatitions, this should be zero
//            'recurring_amount' => 99,
//        ],
//        6 => [
//            'name' => 'Yearly R 1089',
//            'start_date' => \Carbon\Carbon::now()->format('Y-m-d'),
//            'payfast_frequency' => 6, // 6 = yearly
//            'initial_amount' => 1089, // For card updates or subscription reactivatitions, this should be zero
//            'recurring_amount' => 1089,
//        ]
//    ],

    'billables' => [
        'user' => [
            'model' => User::class,
            'trial_days' => 30,
            'type' => 'subscription', // or sponsorship. Will be appended to PayFast description
            'plans' => [
                [
                    'name' => 'Personal',
                    'short_description' => "Keep track of up to 3 accounts.",
                    'monthly' => 99,
                    'yearly' => 1089,
                    'features' => [
                        'Import up to 3 accounts',
                        'Export data and tags',
                    ],
                    'cta' => 'Start Free Trial',
                    'mostPopular' => false,
                ],
                [
                    'name' => 'Business',
                    'short_description' => "Keep track of up to 10 accounts.",
                    'monthly' => 199,
                    'yearly' => 2189,
                    'features' => [
                        'Import up to 10 accounts',
                        'Export data and tags',
                        'Access data using an API',
                    ],
                    'cta' => 'Start Free Trial',
                    'mostPopular' => true,
                ],
                [
                    'name' => 'Provider',
                    'short_description' => "Ideal when you're doing work for others.",
                    'monthly' => env('SPARK_STANDARD_MONTHLY_PLAN', 1000),
                    'yearly' => env('SPARK_STANDARD_YEARLY_PLAN', 1001),
                    'features' => [
                        'Import up to 100 accounts',
                        'Export data and tags',
                        'Access data using an API',
                        'Central console for tag sharing',
                    ],
                    'cta' => 'Start Free Trial',
                    'mostPopular' => false,
                ],
            ],
        ],
    ],
];
