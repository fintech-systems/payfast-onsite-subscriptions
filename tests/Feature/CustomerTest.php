<?php


uses(\Tests\Feature\FeatureTestCase::class);

test('billable models can create a customer record', function () {
    $user = $this->createUser();

    $customer = $user->createAsCustomer(['trial_ends_at' => $trialEndsAt = now()->addDays(15)]);

    expect($customer->trial_ends_at->timestamp)->toBe($trialEndsAt->timestamp)
        ->and($user->trialEndsAt()->timestamp)->toBe($trialEndsAt->timestamp)
        ->and($user->onGenericTrial())->toBeTrue();
});

test('billable models without having a customer record can still use some methods', function () {
    $user = $this->createUser();

    expect($user->onTrial())->toBeFalse();
    expect($user->onGenericTrial())->toBeFalse();
    expect($user->onPlan(123))->toBeFalse();
    expect($user->subscribed())->toBeFalse();
    expect($user->subscribedToPlan(123))->toBeFalse();
    expect($user->subscriptions)->toBeEmpty();
    expect($user->receipts)->toBeEmpty();
    expect($user->subscription())->toBeNull();
});

test('trial ends at works if generic trial is expired', function () {
    $user = $this->createUser();
    $user->createAsCustomer(['trial_ends_at' => $trialEndsAt = now()->subDays(15)]);

    expect($user->trialEndsAt()->timestamp)->toBe($trialEndsAt->timestamp);
});
