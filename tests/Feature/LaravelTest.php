<?php

namespace FintechSystems\PayFast\Tests;

use Illuminate\Support\Facades\Http;
use FintechSystems\PayFast\Facades\PayFast;

class LaravelTest extends TestCase
{    
    /** @test */
    public function laravel_dependency_injection_works()
    {
        $result = PayFast::di();

        $this->assertTrue($result);
    }    
}