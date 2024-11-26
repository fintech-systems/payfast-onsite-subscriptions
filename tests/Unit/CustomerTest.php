<?php

uses(\Orchestra\Testbench\TestCase::class);
use Carbon\Carbon;
use FintechSystems\Payfast\Customer;
use Tests\Fixtures\User;

test('customer can be put on a generic trial', function () {
    $user = new User();
    $user->customer = $customer = new Customer();
    $customer->setDateFormat('Y-m-d H:i:s');

    expect($user->onGenericTrial())->toBeFalse();

    $customer->trial_ends_at = Carbon::tomorrow();

    expect($user->onTrial())->toBeTrue();
    expect($user->onGenericTrial())->toBeTrue();

    $customer->trial_ends_at = Carbon::today()->subDays(5);

    expect($user->onGenericTrial())->toBeFalse();
});

test('we can check if a generic trial has expired', function () {
    $user = new User();
    $user->customer = $customer = new Customer();
    $customer->setDateFormat('Y-m-d H:i:s');

    $customer->trial_ends_at = Carbon::yesterday();

    expect($user->hasExpiredTrial())->toBeTrue();
    expect($user->hasExpiredGenericTrial())->toBeTrue();
});
