<?php

use FintechSystems\PayFast\Components\Billing;
use Illuminate\Support\Facades\Route;

Route::get('/payfast/return', function() {
    return view('vendor.payfast.return');
});

Route::get('/payfast/cancel', function() {
    return view('vendor.payfast.cancel');
});

Route::post('/payfast/notify', 'FintechSystems\PayFast\Http\Controllers\WebhookController');

Route::post('/payfast/webhook', 'FintechSystems\PayFast\Http\Controllers\WebhookController');

Route::middleware(['web', 'auth:sanctum', 'verified'])
    ->get('/user/billing', Billing::class)
    ->name('profile.billing');
