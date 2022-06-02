<?php

namespace FintechSystems\WhmcsApi\Tests;

use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Dotenv\Dotenv;

class Setup extends TestCase
{
    private function dotEnvExists()
    {
        $dotenv = new Dotenv();

        try {
            $dotenv->load(__DIR__.'/../.env');
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    protected function getServerInformation()
    {
        if (! $this->dotEnvExists()) {
            $this->assertTrue(true);

            return false;
        }

        $server = [
            'url'            => env('WHMCS_URL'),
            'api_identifier' => env('WHMCS_API_IDENTIFIER'),
            'api_secret'     => env('WHMCS_API_SECRET'),
        ];

        if (! $server['url']) {
            return false;
        }

        return $server;
    }
}
