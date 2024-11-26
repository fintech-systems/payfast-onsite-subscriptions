<?php

uses(\Tests\Feature\FeatureTestCase::class);
test('gracefully handle webhook without alert name', function () {
    $this->postJson('payfast/webhook', [
        'ping' => now(),
    ])->assertOk();
});
