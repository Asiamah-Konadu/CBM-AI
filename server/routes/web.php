<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('api/v1')->group(function () {
    Route::get('/csrf-token', [\App\Http\Controllers\Api\AuthController::class, 'csrfToken']);
    Route::post('/auth/register', [\App\Http\Controllers\Api\AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('/auth/login', [\App\Http\Controllers\Api\AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'index']);
    Route::get('/businesses/{slug}', [\App\Http\Controllers\Api\PublicBusinessProfileController::class, 'show']);

    Route::middleware('auth')->group(function () {
        Route::get('/auth/me', [\App\Http\Controllers\Api\AuthController::class, 'me']);
        Route::post('/auth/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);

        Route::prefix('seller')->middleware('role:seller')->group(function () {
            Route::get('/businesses', [\App\Http\Controllers\Api\SellerBusinessController::class, 'index']);
            Route::post('/businesses', [\App\Http\Controllers\Api\SellerBusinessController::class, 'store']);
            Route::get('/businesses/{business}', [\App\Http\Controllers\Api\SellerBusinessController::class, 'show']);
            Route::patch('/businesses/{business}', [\App\Http\Controllers\Api\SellerBusinessController::class, 'update']);
            Route::post('/businesses/{business}/publish', [\App\Http\Controllers\Api\SellerBusinessController::class, 'publish']);
            Route::post('/businesses/{business}/unpublish', [\App\Http\Controllers\Api\SellerBusinessController::class, 'unpublish']);
            Route::get('/businesses/{business}/listings', [\App\Http\Controllers\Api\SellerListingController::class, 'index']);
            Route::post('/businesses/{business}/listings', [\App\Http\Controllers\Api\SellerListingController::class, 'store']);
            Route::patch('/listings/{listing}', [\App\Http\Controllers\Api\SellerListingController::class, 'update']);
            Route::delete('/listings/{listing}', [\App\Http\Controllers\Api\SellerListingController::class, 'destroy']);
        });
    });
});
