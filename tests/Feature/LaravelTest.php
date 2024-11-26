<?php

uses(\Tests\Feature\FeatureTestCase::class);
use FintechSystems\Payfast\Facades\Payfast;

test('laravel dependency injection works', function () {
    $result = Payfast::di();

    expect($result)->toBeTrue();
});
