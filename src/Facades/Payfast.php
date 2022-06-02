<?php

namespace FintechSystems\Payfast\Facades;

use Illuminate\Support\Facades\Facade;

class Payfast extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'payfast';
    }
}
