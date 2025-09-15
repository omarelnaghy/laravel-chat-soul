<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Galaxy\ChatHub\Http\Controllers\Api\ConversationController;
use Galaxy\ChatHub\Http\Controllers\Api\MessageController;
use Galaxy\ChatHub\Http\Controllers\Api\TypingController;
use Galaxy\ChatHub\Http\Controllers\Api\DeviceTokenController;
use Galaxy\ChatHub\Http\Controllers\Api\PresenceController;

Route::group([
    'prefix' => 'api/chat',
    'middleware' => ['api', 'auth:sanctum', Galaxy\ChatHub\Http\Middleware\EnsureChatApiEnabled::class],
], function () {
    Route::post('/conversations', [ConversationController::class, 'store']);
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::get('/conversations/{id}', [ConversationController::class, 'show']);
    Route::patch('/conversations/{id}', [ConversationController::class, 'update']);
    Route::delete('/conversations/{id}', [ConversationController::class, 'destroy']);

    Route::get('/conversations/{id}/messages', [MessageController::class, 'index']);
    Route::post('/conversations/{id}/messages', [MessageController::class, 'store']);
    Route::patch('/messages/{id}/read', [MessageController::class, 'markRead']);

    Route::post('/conversations/{id}/typing', [TypingController::class, 'setTyping']);
    Route::get('/conversations/{id}/presence', [PresenceController::class, 'index']);

    Route::post('/device-tokens', [DeviceTokenController::class, 'store']);
    Route::delete('/device-tokens/{id}', [DeviceTokenController::class, 'destroy']);
});
