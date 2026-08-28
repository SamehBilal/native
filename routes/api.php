<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProviderLocationController;
use App\Http\Controllers\Api\ProviderRequestController;
use App\Http\Controllers\Api\ServiceMessageController;
use App\Http\Controllers\Api\ServiceOfferController;
use App\Http\Controllers\Api\ServiceRequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);

        // Customer: raise a request, browse nearby providers, watch offers roll in.
        Route::post('/service-requests', [ServiceRequestController::class, 'store']);
        Route::get('/service-requests/{serviceRequest}', [ServiceRequestController::class, 'show']);
        Route::get('/service-requests/{serviceRequest}/nearby-providers', [ServiceRequestController::class, 'nearbyProviders']);
        Route::post('/service-requests/{serviceRequest}/location', [ServiceRequestController::class, 'updateLocation']);
        Route::post('/offers/{offer}/accept', [ServiceOfferController::class, 'accept']);

        // Provider: see nearby requests, submit fee offers, push live location.
        Route::get('/provider/requests', [ProviderRequestController::class, 'index']);
        Route::get('/provider/offers', [ProviderRequestController::class, 'offers']);
        Route::post('/service-requests/{serviceRequest}/offers', [ServiceOfferController::class, 'store']);
        Route::post('/provider/location', [ProviderLocationController::class, 'update']);

        // Shared: live tracking + chat once a request is accepted.
        Route::get('/service-requests/{serviceRequest}/track', [ServiceRequestController::class, 'track']);
        Route::get('/service-requests/{serviceRequest}/messages', [ServiceMessageController::class, 'index']);
        Route::post('/service-requests/{serviceRequest}/messages', [ServiceMessageController::class, 'store']);
    });
});
