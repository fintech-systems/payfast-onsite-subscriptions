<?php

namespace FintechSystems\PayFast\Facades;

use Illuminate\Support\Facades\Facade;

class PayFast extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'payfast';
    }
}
