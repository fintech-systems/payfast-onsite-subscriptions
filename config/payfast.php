<?php

use App\Models\User;

return [
    'merchant_id' => env('PAYFAST_MERCHANT_ID'),
    'merchant_key' => env('PAYFAST_MERCHANT_KEY'),
    'passphrase' => env('PAYFAST_PASSPHRASE'),

    'test_mode' => env('PAYFAST_TEST_MODE'),
    'test_mode_callback_url' => env('PAYFAST_TEST_MODE_CALLBACK_URL',config('app.url')),

    'merchant_id_test' => env('PAYFAST_MERCHANT_ID_TEST'),
    'merchant_key_test' => env('PAYFAST_MERCHANT_KEY_TEST'),
    'passphrase_test' => env('PAYFAST_PASSPHRASE_TEST'),

    'debug' => env('PAYFAST_DEBUG', false),

    'return_url' => env('PAYFAST_RETURN_URL', '/payfast/return'),
    'cancel_url' => env('PAYFAST_CANCEL_URL', '/payfast/cancel'),
    'notify_url' => env('PAYFAST_NOTIFY_URL', '/payfast/notify'),

    'billables' => [
        'user' => [
            'model' => User::class,

            'trial_days' => 30,

            'default_interval' => 'monthly',

            'currency_prefix' => 'R ',

            'plans' => [
                [
                    'name' => 'Startup',
                    'short_description' => "Send 100 invoices per month",
                    'daily' => [
                        'setup_amount' => 5,
                        'recurring_amount' => 6,
                    ],
                    'monthly' => [
                        'setup_amount' => 0,
                        'recurring_amount' => 99,
                    ],
                    'yearly' => [
                        'setup_amount' => 0,
                        'recurring_amount' => 1089,
                    ],
                    'features' => [
                        'Send 100 invoices per month',
                        'Unlimited beautiful PDF quotes',
                    ],
                    'archived' => false,
                    'cta' => '30 DAY FREE TRIAL',
                    'mostPopular' => false,
                ],
                [
                    'name' => 'Business',
                    'short_description' => "Automatically reconcile invoices",
                    'daily' => [
                        'setup_amount' => 7,
                        'recurring_amount' => 8,
                    ],
                    'monthly' => [
                        'setup_amount' => 0,
                        'recurring_amount' => 199,
                    ],
                    'yearly' => [
                        'setup_amount' => 0,
                        'recurring_amount' => 2189,
                    ],
                    'features' => [
                        '1000 invoices per month',
                        'Unlimited beautiful PDF quotes',
                        'Payfast Payment gateway',
                        'Subscription Billing',
                        'Bank feeds*',
                    ],
                    'archived' => false,
                    'cta' => '30 DAY FREE TRIAL',
                    'mostPopular' => true,
                ],
                [
                    'name' => 'Enterprise',
                    'short_description' => "Ideal when you're doing work for others",
                    'daily' => [
                        'setup_amount' => 9,
                        'recurring_amount' => 10,
                    ],
                    'monthly' => [
                        'setup_amount' => 0,
                        'recurring_amount' => 299,
                    ],
                    'yearly' => [
                        'setup_amount' => 0,
                        'recurring_amount' => 3289,
                    ],
                    'features' => [
                        'Unlimited invoices',
                        'Unlimited beautiful PDF quotes',
                        'Payfast Payment gateway',
                        'Subscription Billing',
                        'Bank feeds*',
                        'Central console for tag sharing',
                        'API Access',
                    ],
                    'archived' => false,
                    'cta' => '30 DAY FREE TRIAL',
                    'mostPopular' => false,
                ],
            ],
        ],
    ],
];
