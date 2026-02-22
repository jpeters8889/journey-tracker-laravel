<?php

use Illuminate\Support\Facades\Route;
use Jpeters8889\JourneyTrackerLaravel\Http\Controllers\EventStoreController;

Route::post(config('journey-tracker-laravel.internal-event-endpoint'), EventStoreController::class)->name('journey-tracker-laravel.event.store');
