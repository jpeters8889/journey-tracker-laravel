<?php

use Illuminate\Support\Facades\Route;
use Jpeters8889\JourneyTrackerLaravel\Http\Controllers\EventStoreController;
use Jpeters8889\JourneyTrackerLaravel\Http\Controllers\HeartbeatController;

Route::post(config('journey-tracker-laravel.internal-event-endpoint'), EventStoreController::class)->name('journey-tracker-laravel.event.store');
Route::post(config('journey-tracker-laravel.heartbeat-endpoint'), HeartbeatController::class)->name('journey-tracker-laravel.heartbeat.store');
