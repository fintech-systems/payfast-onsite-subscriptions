<?php

namespace FintechSystems\Payfast;

// use FintechSystems\WhmcsApi\Payfast;
use Illuminate\Support\ServiceProvider;

class PayfastServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/payfast.php' => config_path('payfast.php'),
        ], 'payfast-config');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/payfast.php',
            'payfast'
        );

        $this->app->bind('payfast', function () {
            return new Payfast([
                'merchant_id' => config('payfast.merchant_id'),
                'merchant_key' => config('payfast.merchant_key'),
                'passphrase' => config('payfast.passphrase'),
                'testmode' => config('payfast.testmode'),
            ]);
        });
    }
}
