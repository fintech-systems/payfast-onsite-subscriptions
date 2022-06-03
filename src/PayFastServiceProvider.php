<?php

namespace FintechSystems\PayFast;

use Illuminate\Support\ServiceProvider;

class PayFastServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/payfast.php' => config_path('payfast.php'),
        ], 'payfast-config');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/payfast.php',
            'payfast'
        );

        $this->app->bind('payfast', function () {
            return new PayFast([
                'merchant_id' => config('payfast.merchant_id'),
                'merchant_key' => config('payfast.merchant_key'),
                'passphrase' => config('payfast.passphrase'),
                'testmode' => config('payfast.testmode'),
            ]);
        });
    }
}
