<?php

use FintechSystems\Payfast\Components\Billing;
use FintechSystems\Payfast\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/payfast/return', function() {
    return view('vendor.payfast.return');
});

Route::get('/payfast/cancel', function() {
    return view('vendor.payfast.cancel');
});

Route::post('/payfast/notify', 'FintechSystems\Payfast\Http\Controllers\WebhookController');

Route::post('/payfast/webhook', 'FintechSystems\Payfast\Http\Controllers\WebhookController');

Route::middleware(['web', 'auth:sanctum', 'verified'])
    ->get('/user/billing', Billing::class)
    ->name('profile.billing');
