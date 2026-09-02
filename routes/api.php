<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Jpeters8889\JourneyTrackerLaravel\Http\Controllers\ConfirmController;
use Jpeters8889\JourneyTrackerLaravel\Http\Controllers\EventStoreController;
use Jpeters8889\JourneyTrackerLaravel\Http\Controllers\HeartbeatController;

Route::post(config('journey-tracker-laravel.internal-event-endpoint'), EventStoreController::class)->name('journey-tracker-laravel.event.store');
Route::post(config('journey-tracker-laravel.heartbeat-endpoint'), HeartbeatController::class)->name('journey-tracker-laravel.heartbeat.store');
Route::post(config('journey-tracker-laravel.confirm-endpoint'), ConfirmController::class)->name('journey-tracker-laravel.confirm.store');
