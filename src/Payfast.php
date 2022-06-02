<?php

namespace FintechSystems\Payfast;

use FintechSystems\Payfast\Contracts\BillingProvider;

class Payfast implements BillingProvider
{
    private string $url;
    private string $api_identifier;
    private string $api_secret;

    public function __construct($server)
    {
        $this->url = $server['url'];
        $this->api_identifier = $server['api_identifier'];
        $this->api_secret = $server['api_secret'];
    }

    public function ping()
    {
        // TODO: Implement ping() method.
    }
}
