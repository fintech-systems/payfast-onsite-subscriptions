<?php

namespace FintechSystems\Payfast\Tests;

use FintechSystems\Payfast\Facades\Payfast;
use Tests\Feature\FeatureTestCase;

class LaravelTest extends FeatureTestCase
{
    /** @test */
    public function laravel_dependency_injection_works()
    {
        $result = Payfast::di();

        $this->assertTrue($result);
    }
}
