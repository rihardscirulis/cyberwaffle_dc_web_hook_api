<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FourthwallWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

// Fourthwall webhook endpoint
Route::post('/webhooks/fourthwall', [FourthwallWebhookController::class, 'handleWebhook'])
    ->name('webhooks.fourthwall');
