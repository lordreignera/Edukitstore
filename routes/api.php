<?php

use App\Http\Controllers\Website\FlutterwaveWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/payments/flutterwave/webhook', FlutterwaveWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('website.payments.flutterwave.webhook');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
