# Technology API
![GitHub release (latest by date)](https://img.shields.io/github/v/release/fintech-systems/packagist-boilerplate) [![Build Status](https://app.travis-ci.com/fintech-systems/packagist-boilerplate.svg?branch=main)](https://app.travis-ci.com/fintech-systems/packagist-boilerplate) ![GitHub](https://img.shields.io/github/license/fintech-systems/packagist-boilerplate)

A Technology API designed to run standalone or part of a Laravel Application

Requirements:

- PHP 8.0
- Technology

## Installation

You can install the package via composer:

```bash
composer require fintechsystems/payfast-onsite-subscriptions
```

# Usage

## Framework Agnostic PHP

```php
<?php

use FintechSystems\Api\Technology;

require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$server = [
    'api_url'        => $_ENV['TECHNOLOGY_API_URL'],
    'api_key'        => $_ENV['TECHNOLOGY_API_KEY'],
    'api_secret'     => $_ENV['TECHNOLOGY_API_SECRET'],
];

$api = new Technology($server);

$result = $api->getInformation();
```

## Laravel Installation

You can publish the config file with:
```bash
php artisan vendor:publish --provider="FintechSystems\Payfast\PayfastServiceProvider" --tag="payfast-onsite-subscriptions-config"
```

# Features

## Feature 1

Framework Agnostic PHP:

```php
$newRecord = ['test1', 'test2'];

$api = new Technology;
$api->post($test);
```

Laravel App:


```php
$newRecord = ['test1', 'test2'];

Technology::post($newRecord);
```

Expected result:

A new record is added.

## Testing

```bash
vendor/bin/phpunit
```

Use the command below to run tests that excludes touching the API:

`vendor/bin/phpunit --exclude-group=live`

The `storage` folder has examples API responses, also used for caching during tests.

### Coverage reports

To regenerate coverage reports:

`XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-html=tests/coverage-report`

See also `.travis.yml`

### Local Editing

For local editing, add this to `composer.json`:

```json
"repositories" : [
        {
            "type": "path",
            "url": "../technology-api"
        }
    ]
```

Then in `require` section:

```json
"fintech-systems/technology-api": "dev-main",
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Eugene van der Merwe](https://github.com/fintech-systems)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
