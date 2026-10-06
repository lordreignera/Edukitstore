<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Website\FlutterwaveWebhookController;

Route::post('/payments/flutterwave/webhook', FlutterwaveWebhookController::class)
    ->name('website.payments.flutterwave.webhook');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
