<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DemoController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\SwapController;
use App\Http\Controllers\Api\TimeOffController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('auth/login', [AuthController::class, 'login']);
        Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('demo', [DemoController::class, 'store']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::get('dashboard', DashboardController::class);
        Route::get('employees', [EmployeeController::class, 'index']);
        Route::get('activity', [ActivityController::class, 'index']);

        Route::get('shifts', [ShiftController::class, 'index']);
        Route::post('shifts', [ShiftController::class, 'store']);
        Route::post('shifts/copy-week', [ShiftController::class, 'copyWeek']);
        Route::patch('shifts/{shift}', [ShiftController::class, 'update']);
        Route::delete('shifts/{shift}', [ShiftController::class, 'destroy']);

        Route::get('availability', [AvailabilityController::class, 'index']);
        Route::put('availability', [AvailabilityController::class, 'update']);

        Route::get('time-off', [TimeOffController::class, 'index']);
        Route::post('time-off', [TimeOffController::class, 'store']);
        Route::post('time-off/{timeOff}/approve', [TimeOffController::class, 'approve']);
        Route::post('time-off/{timeOff}/decline', [TimeOffController::class, 'decline']);
        Route::delete('time-off/{timeOff}', [TimeOffController::class, 'destroy']);

        Route::get('swaps', [SwapController::class, 'index']);
        Route::post('swaps', [SwapController::class, 'store']);
        Route::post('swaps/{swap}/pick-up', [SwapController::class, 'pickUp']);
        Route::post('swaps/{swap}/approve', [SwapController::class, 'approve']);
        Route::post('swaps/{swap}/decline', [SwapController::class, 'decline']);
        Route::delete('swaps/{swap}', [SwapController::class, 'destroy']);
    });
});
