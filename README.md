## About Payfast Onsite Subscriptions
![GitHub release (latest by date)](https://img.shields.io/github/v/release/fintech-systems/payfast-onsite-subscriptions) ![Tests](https://github.com/fintech-systems/payfast-onsite-subscriptions/actions/workflows/tests.yml/badge.svg)
 ![GitHub](https://img.shields.io/github/license/fintech-systems/payfast-onsite-subscriptions)

A [Payfast Onsite Payments](https://developers.payfast.co.za/docs#onsite_payments) implementation for Laravel designed to ease subscription billing. [Livewire](https://laravel-livewire.com/) views are included.

Requirements:

- PHP 8.3
- Laravel 11.x or higher
- A [Payfast Sandbox account](https://sandbox.payfast.co.za/)
- A [Payfast account](https://www.payfast.co.za/registration)

If you want to use Laravel Nova, version 4 is required for the `Subscription` and `Receipt` resources.

## Installation

Install the package via composer:

```bash
composer require fintech-systems/payfast-onsite-subscriptions
```

## Publish Configuration and Views

Publish the config file with:
```bash
php artisan vendor:publish --provider="FintechSystems\Payfast\PayfastServiceProvider" --tag="config"
```

Publish the Success and Cancelled views and the Livewire components for subscriptions and receipts.
```bash
php artisan vendor:publish --provider="FintechSystems\Payfast\PayfastServiceProvider" --tag="views"
```

These files are:
```bash
banner.blade.php
billing.blade.php
cancel.blade.php
pricing.blade.php
receipts.blade.php
subscriptions.blade.php
success.blade.php
```

To include the pricing component on a page, do this:

In your header:
```php
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

In your view:

```php
@include('payfast::components.pricing')
```

You'll end up with a page looking like this:
![Pricing Component](../../blob/main/screenshots/pricing.png)

### Nova Integration

Optionally publish Laravel Nova Subscription and Receipts Resources and Actions

```bash
php artisan vendor:publish --provider="FintechSystems\Payfast\PayfastServiceProvider" --tag="nova-resources"
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
    'merchant_id' => env('PAYFAST_MERCHANT_ID'),
    'merchant_key' => env('PAYFAST_MERCHANT_KEY'),
    'passphrase' => env('PAYFAST_PASSPHRASE'),    
    'test_mode' => env('PAYFAST_TEST_MODE'),
    'test_mode_callback_url' => env('PAYFAST_TEST_MODE_CALLBACK_URL',config('app.url')),
    'trial_days' => env('PAYFAST_TRIAL_DAYS', 30),
    'merchant_id_test' => env('PAYFAST_MERCHANT_ID_TEST'),
    'merchant_key_test' => env('PAYFAST_MERCHANT_KEY_TEST'),
    'passphrase_test' => env('PAYFAST_PASSPHRASE_TEST'),
    'debug' => env('PAYFAST_DEBUG', false),
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
                    'name' => 'Startup',
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
                        'Feature 3',
                    ],
                    'archived' => false,
                    'cta' => '30 DAY FREE TRIAL',
                    'mostPopular' => false,
                ],
                [
                    'name' => 'Business',
                    'short_description' => "",                    
                    'monthly' => [
                        'setup_amount' => 199000,
                        'recurring_amount' => 199000,
                    ],
                    'yearly' => [
                        'setup_amount' => 2189000,
                        'recurring_amount' => 2189000,
                    ],
                    'features' => [
                        'Feature 1',
                        'Feature 2',
                        'Feature 3',
                    ],
                    'archived' => false,
                    'cta' => '30 DAY FREE TRIAL',
                    'mostPopular' => true,
                ],                
            ],
        ],
    ],
];
```

## Livewire setup

### Views

The Livewire views are modelled to blend into a [Laravel Jetstream](https://jetstream.laravel.com) user profile page.

#### Adding a billing menu

In `app.blade.php` below in the Account Management sections (e.g., below profile):

```html
<x-dropdown-link href="/user/billing">
    Billing
</x-dropdown-link>
```

Also look for the responsive part and add this:

```html
<x-responsive-nav-link href="/user/billing" :active="request()->routeIs('profile.billing')">
    Billing
</x-responsive-nav-link>
```

#### Adding the subscriptions and receipts views

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

<x-section-border />
<!-- End Subscriptions -->

<!-- Receipts -->
    <div class="mt-10 sm:mt-0">
        @livewire('receipts')
    </div>

<x-section-border />
<!-- End Receipts -->
```

## Usage

### Examples

- Generate a payment link
- Create an adhoc token optionally specifying the amount
- Cancel a subscription
- Update a card

```php
use FintechSystems\PayFast\Facades\Payfast;

Route::get('/payment', function() {
    return Payfast::payment(5,'Order #1');
});

Route::get('/cancel-subscription', function() {
    return Payfast::cancelSubscription('73d2a218-695e-4bb5-9f62-383e53bef68f');
});

Route::get('/create-subscription', function() {
    return Payfast::createSubscription(
        Carbon::now()->addDay()->format('Y-m-d'),
        5, // Amount
        6 // Frequency (6 = annual, 3 = monthly)
    );
});

Route::get('/create-adhoc-token', function() {
    return Payfast::createAdhocToken(5);
});

Route::get('/fetch-subscription', function() {
    return Payfast::fetchSubscription('21189d52-12eb-4108-9c0e-53343c7ac692');
});

Route::get('/update-card', function() {
    return Payfast::updateCardLink('40ab3194-20f0-4814-8c89-4d2a6b5462ed');
});
```

## Testing

### How to determine when a user's subscription ends

$user->subscription('default')->ends_at = [date in the past]

```bash
vendor/bin/phpunit
```

In your main project, add this:

```
"repositories": [
        {
            "type": "path",
            "url": "../payfast-onsite-subscriptions"
        }
],
```

Then do this to symlink the library:

```
composer require fintechsystems/payfast-onsite-subscriptions:dev-main
```

If you want to test trials, use this one-liner to activate a billable user and a trial using Tinker:

```php
$user = User::find(x)->createAsCustomer(['trial_ends_at' => now()->addDays(30)]);
```

To see if a user is on trial as used in the subscriptions blade, do this:

```php

```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Screenshots

![Livewire Subscriptions and Receipts Components](../../blob/main/screenshots/subscription_and_receipts.png)

## Credits

- [Eugene van der Merwe](https://github.com/eugenevdm)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
