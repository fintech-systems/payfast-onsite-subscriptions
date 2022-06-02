<?php

namespace FintechSystems\WhmcsApi\Tests;

use Symfony\Component\Dotenv\Dotenv;

class ApiTest extends Setup
{
    /** @test */
    public function it_can_read_the_api_url_from_an_env_testing_file_and_assign_it_to_an_array()
    {
        $dotenv = new Dotenv();
        $dotenv->load(__DIR__.'/../.env.testing');

        $server = [
            'url' => $_ENV['WHMCS_URL'],
        ];

        $this->assertEquals('whmcs.example.com', $server['url']);
    }
}
