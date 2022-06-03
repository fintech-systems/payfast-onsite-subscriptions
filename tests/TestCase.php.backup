<?php

namespace FintechSystems\PayFast\Tests;

use Dotenv\Dotenv;
use FintechSystems\PayFast\PayFastServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        $this->loadEnvironmentVariables();

        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            PayFastServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'PayFast' => PayFast::class,
        ];
    }

    protected function loadEnvironmentVariables(): void
    {
        if (! file_exists(__DIR__ . '/../.env')) {
            return;
        }

        $dotEnv = Dotenv::createImmutable(__DIR__ . '/..');

        $dotEnv->load();
    }
}
