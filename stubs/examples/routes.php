<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StreamingController;

Route::aiAssistant(['prefix' => 'ai', 'middleware' => ['web','auth:sanctum']]);
Route::get('/ai/stream', StreamingController::class);
