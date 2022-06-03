<?php

namespace FintechSystems\PayFast\Tests;

use FintechSystems\PayFast\Facades\PayFast;
use Tests\Feature\FeatureTestCase;

class LaravelTest extends FeatureTestCase
{
    /** @test */
    public function laravel_dependency_injection_works()
    {
        $result = PayFast::di();

        $this->assertTrue($result);
    }
}
