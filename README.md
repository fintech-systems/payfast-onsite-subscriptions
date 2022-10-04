## About PayFast Onsite Subscriptions
![GitHub release (latest by date)](https://img.shields.io/github/v/release/fintech-systems/payfast-onsite-subscriptions) ![Tests](https://github.com/fintech-systems/payfast-onsite-subscriptions/actions/workflows/tests.yml/badge.svg)
 ![GitHub](https://img.shields.io/github/license/fintech-systems/payfast-onsite-subscriptions)

A [PayFast Onsite Payments](https://developers.payfast.co.za/docs#onsite_payments) implementation for Laravel designed to ease subscription billing. [Livewire](https://laravel-livewire.com/) views are included.

**THIS IS BETA SOFTWARE**

- There may be some bugs but the core functionality should work.

Requirements:

- PHP 8.1
- Laravel 8.x
- A [PayFast Sandbox account](https://sandbox.payfast.co.za/)
- A [PayFast account](https://www.payfast.co.za/registration)

## Installation

Install the package via composer:

```bash
composer require fintech-systems/payfast-onsite-subscriptions
```

## Publish Configuration and Views

Publish the config file with:
```bash
php artisan vendor:publish --provider="FintechSystems\PayFast\PayFastServiceProvider" --tag="config"
```

Publish the Success and Cancelled views and the Livewire components for subscriptions and receipts.

```bash
php artisan vendor:publish --provider="FintechSystems\PayFast\PayFastServiceProvider" --tag="views"
```

### Nova Integration

Optionally publish Laravel Nova Subscription and Receipts Resources and Actions

```bash
php artisan vendor:publish --provider="FintechSystems\PayFast\PayFastServiceProvider" --tag="nova-resources"
```

## Migrations

A migration is needed to create Customers, Orders, Receipts and Subscriptions tables:

```bash
php artisan migrate
```

## Example Configuration

`config/payfast.php`:

```php
<?php

return [
    'merchant_id' => env('PAYFAST_MERCHANT_ID', '10004002'),
    'merchant_key' => env('PAYFAST_MERCHANT_KEY', 'q1cd2rdny4a53'),
    'passphrase' => env('PAYFAST_PASSPHRASE', 'payfast'),
    'testmode' => env('PAYFAST_TESTMODE', true),        
    'return_url' => env('PAYFAST_RETURN_URL', config('app.url') . '/payfast/return'),
    'cancel_url' => env('PAYFAST_CANCEL_URL', config('app.url') . '/payfast/cancel'),
    'notify_url' => env('PAYFAST_NOTIFY_URL', config('app.url') . '/payfast/notify'),
    'card_update_link_css' => env('CARD_UPDATE_LINK_CSS', 'inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition'),
    'card_updated_return_url' => env('CARD_UPDATED_RETURN_URL', config('app.url') . '/user/profile'),
    'plans' => [
        3 => [
            'name' => 'Monthly R 99',
            'start_date' => \Carbon\Carbon::now()->addDay()->format('Y-m-d'),
            'payfast_frequency' => 3,
            'initial_amount' => 5.99,
            'recurring_amount' => 5.99,
        ],
        6 => [
            'name' => 'Yearly R 1089',
            'start_date' => \Carbon\Carbon::now()->format('Y-m-d'),
            'payfast_frequency' => 6,
            'initial_amount' => 6.89,
            'recurring_amount' => 6.89,
        ]
    ],
    'cancelation_reasons' => [
        'Too expensive',
        'Lacks features',
        'Not what I expected',
    ],
];
```

## Livewire Setup

### Views

I have modelled some Livewire views to fit into a [Laravel Jetstream](https://jetstream.laravel.com) user profile page.

When calling the Livewire component, you can override any [PayFast form field](https://developers.payfast.co.za/docs#step_1_form_fields) by specifying a `mergeFields` array.

Example modification Jetstream Livewire's `resources/views/profiles/show.php`:

Replace `$user->name` with your first name and last name fields.

```php
<!-- Subscriptions -->
<div class="mt-10 sm:mt-0">    
    @livewire('subscriptions', ['mergeFields' => [
            'name_first' => $user->name,
            'name_last' => $user->name,
            'item_description' => 'Subscription to Online Service'
        ]] )        
</div>

<x-jet-section-border />
<!-- End Subscriptions -->

<!-- Receipts -->
    <div class="mt-10 sm:mt-0">
        @livewire('receipts')
    </div>

<x-jet-section-border />
<!-- End Receipts -->
```

## Usage

### Examples

- Generate a payment link
- Create an ad-hoc token optionally specifying the amount
- Cancel a subscription
- Update a card

```php
use FintechSystems\PayFast\Facades\PayFast;

Route::get('/payment', function() {
    return PayFast::payment(5,'Order #1');
});

Route::get('/cancel-subscription', function() {
    return PayFast::cancelSubscription('73d2a218-695e-4bb5-9f62-383e53bef68f');
});

Route::get('/create-subscription', function() {
    return PayFast::createSubscription(
        Carbon::now()->addDay()->format('Y-m-d'),
        5, // Amount
        6 // Frequency (6 = annual, 3 = monthly)
    );
});

Route::get('/create-adhoc-token', function() {
    return PayFast::createAdhocToken(5);
});

Route::get('/fetch-subscription', function() {
    return PayFast::fetchSubscription('21189d52-12eb-4108-9c0e-53343c7ac692');
});

Route::get('/update-card', function() {
    return PayFast::updateCardLink('40ab3194-20f0-4814-8c89-4d2a6b5462ed');
});
```

## Testing

```bash
vendor/bin/phpunit
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Screenshots

![Livewire Subscriptions and Receipts Components](../../blob/main/screenshots/subscription_and_receipts.png)

## Credits

- [Eugene van der Merwe](https://github.com/eugenevdm)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
