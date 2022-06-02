<?php

use FintechSystems\WhmcsApi\WhmcsApi;

require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$server = [
    'url'            => $_ENV['WHMCS_URL'],
    'api_identifier' => $_ENV['WHMCS_API_IDENTIFIER'],
    'api_secret'     => $_ENV['WHMCS_API_SECRET'],
];

$mode = $_ENV['WHMCS_API_MODE'];

$api = new WhmcsApi($server, $mode);

$result = $api->getClients();

//ray($result);
