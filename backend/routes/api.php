<?php

use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\DashboardStatsController;
use App\Http\Controllers\Api\Admin\RefundRequestController as AdminRefundRequestController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\RefundPolicyController;
use App\Http\Controllers\Api\RefundRequestController;
use Illuminate\Support\Facades\Route;

Route::post('/refund-requests', [RefundRequestController::class, 'store'])
    ->middleware('throttle:refund-submissions');

Route::get('/refund-policy', RefundPolicyController::class);

Route::post('/conversations', [ConversationController::class, 'store'])->middleware('throttle:conversations');
Route::get('/conversations/{conversation}', [ConversationController::class, 'show']);
Route::post('/conversations/{conversation}/messages', [ConversationController::class, 'sendMessage'])
    ->middleware('throttle:conversation-messages');

Route::prefix('admin')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:admin-login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/stats', DashboardStatsController::class);
        Route::get('/refund-requests', [AdminRefundRequestController::class, 'index']);
        Route::get('/refund-requests/{refundRequest}', [AdminRefundRequestController::class, 'show']);
        Route::post('/refund-requests/{refundRequest}/review', [AdminRefundRequestController::class, 'review']);
    });
});
