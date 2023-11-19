<?php

namespace FintechSystems\Payfast;

use FintechSystems\Payfast\Components\Banner;
use FintechSystems\Payfast\Components\Billing;
use FintechSystems\Payfast\Components\Receipts;
use FintechSystems\Payfast\Components\Subscriptions;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class PayfastServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/payfast.php' => config_path('payfast.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/payfast'),
        ], 'views');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'payfast');

        $this->publishes([
            __DIR__ . '/../Nova' => app_path('Nova'),
        ], 'nova-resources');

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        Livewire::component('subscriptions', Subscriptions::class);

        Livewire::component('receipts', Receipts::class);

        Livewire::component('banner', Banner::class);

        Livewire::component('billing', Billing::class);
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/payfast.php',
            'payfast'
        );

        $this->app->bind('payfast', function () {
            return new Payfast([
                'merchant_id' => config('payfast.merchant_id'),
                'merchant_key' => config('payfast.merchant_key'),
                'passphrase' => config('payfast.passphrase'),

                'test_mode' => config('payfast.test_mode'),

                'merchant_id_test' => config('payfast.merchant_id_test'),
                'merchant_key_test' => config('payfast.merchant_key_test'),
                'passphrase_test' => config('payfast.passphrase_test'),

                'return_url' => config('payfast.return_url'),
                'cancel_url' => config('payfast.cancel_url'),
                'notify_url' => config('payfast.notify_url'),
            ]);
        });
    }
}
