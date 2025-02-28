<?php

use App\Models\User;

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

    'billables' => [
        'user' => [
            'model' => User::class,

            'trial_days' => 30,

            'default_interval' => 'monthly',

            'currency_prefix' => 'R ',

            'plans' => [
                [
                    'name' => 'Best Agent',
                    'short_description' => "",                    
                    'monthly' => [
                        'setup_amount' => 69000,
                        'recurring_amount' => 69000,
                    ],
                    'yearly' => [
                        'setup_amount' => 700000,
                        'recurring_amount' => 700000,
                    ],
                    'features' => [
                        'Feature 1',
                        'Feature 2',
                    ],
                    'archived' => false,
                    'cta' => 'Sign up now',
                    'mostPopular' => false,
                ],
                [
                    'name' => 'Vander Host Subscription',
                    'short_description' => "",                    
                    'monthly' => [
                        'setup_amount' => 0,
                        'recurring_amount' => 69000,
                    ],
                    'yearly' => [
                        'setup_amount' => 0,
                        'recurring_amount' => 700000,
                    ],
                    'features' => [
                        'Feature 1',
                        'Feature 2',
                    ],
                    'archived' => false,
                    'cta' => 'Sign up now',
                    'mostPopular' => false,
                ],              
            ],
        ],
    ],
];
