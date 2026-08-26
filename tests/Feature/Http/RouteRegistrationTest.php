<?php

declare(strict_types=1);

use Illuminate\Routing\Router;
use Jpeters8889\JourneyTrackerLaravel\Http\Controllers\EventStoreController;
use Jpeters8889\JourneyTrackerLaravel\Http\Controllers\HeartbeatController;

it('registers the named endpoint routes at the configured uris', function (string $name, string $configKey, string $controller): void {
    $route = app(Router::class)->getRoutes()->getByName($name);

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe(config("journey-tracker-laravel.{$configKey}"))
        ->and($route->getActionName())->toBe($controller);
})->with([
    ['journey-tracker-laravel.event.store', 'internal-event-endpoint', EventStoreController::class],
    ['journey-tracker-laravel.heartbeat.store', 'heartbeat-endpoint', HeartbeatController::class],
]);

it('accepts posts only', function (string $name): void {
    $route = app(Router::class)->getRoutes()->getByName($name);

    expect($route?->methods())->toBe(['POST']);
})->with([
    'journey-tracker-laravel.event.store',
    'journey-tracker-laravel.heartbeat.store',
]);

it('rejects a get to the endpoints', function (string $configKey): void {
    $this->get('/' . config("journey-tracker-laravel.{$configKey}"))->assertMethodNotAllowed();
})->with([
    'internal-event-endpoint',
    'heartbeat-endpoint',
]);
