<?php

use App\Http\Controllers\Api\NanoClawWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('webhooks/nanoclaw', [NanoClawWebhookController::class, 'store'])->name('webhooks.nanoclaw');
