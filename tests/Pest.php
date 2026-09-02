<?php

declare(strict_types=1);

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Jpeters8889\JourneyTrackerLaravel\DataObjects\QueuedEventData;
use Jpeters8889\JourneyTrackerLaravel\DataObjects\QueuedPageViewData;
use Jpeters8889\JourneyTrackerLaravel\DataObjects\QueuedTagData;
use Jpeters8889\JourneyTrackerLaravel\Enums\EventType;
use Jpeters8889\JourneyTrackerLaravel\Http\Middleware\LogPageViewMiddleware;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Unit', 'Feature');

function heartbeatUrl(): string
{
    return '/' . config('journey-tracker-laravel.heartbeat-endpoint');
}

function eventUrl(): string
{
    return '/' . config('journey-tracker-laravel.internal-event-endpoint');
}

function confirmUrl(): string
{
    return '/' . config('journey-tracker-laravel.confirm-endpoint');
}

function journeyToken(string $visitId = 'session-abc', string $path = 'blog'): string
{
    return Crypt::encrypt(['visit_id' => $visitId, 'path' => $path]);
}

function legacyJourneyToken(string $visitId = 'session-abc', string $path = 'blog'): string
{
    return Crypt::encrypt(['session_id' => $visitId, 'path' => $path]);
}

function fakePageViewEndpoint(): void
{
    Http::fake([
        '*/api/v1/page-view' => Http::response(),
    ]);
}

function fakeConfirmEndpoint(): void
{
    Http::fake([
        '*/api/v1/page-view/confirm' => Http::response(),
    ]);
}

function fakeEventEndpoint(): void
{
    Http::fake([
        '*/api/event' => Http::response(),
    ]);
}

function fakeTagEndpoint(): void
{
    Http::fake([
        '*/api/tag' => Http::response(),
    ]);
}

function fakeAllEndpoints(): void
{
    Http::fake([
        '*/api/v1/page-view' => Http::response(),
        '*/api/event' => Http::response(),
        '*/api/tag' => Http::response(),
    ]);
}

/** @param array<string, mixed>|list<array<string, mixed>> $data */
function fakeQueryResponse(array $data = ['metric' => 1]): void
{
    Http::fake([
        '*/api/query' => Http::response(['data' => $data]),
    ]);
}

function trackedRoute(string $uri, Closure $handler): void
{
    Route::middleware(['web', LogPageViewMiddleware::class])->get($uri, $handler);
}

function untrackedRoute(string $uri, Closure $handler): void
{
    Route::middleware(['web'])->get($uri, $handler);
}

function policyRequest(string $uri = '/blog', string $method = 'GET'): Request
{
    $request = Request::create($uri, $method);

    $request->setLaravelSession(new Store('journey-tracker-test', new ArraySessionHandler(120)));

    return $request;
}

function queuedTagData(string $visitId = 'session-abc', string $tag = 'Shop Purchase'): QueuedTagData
{
    return new QueuedTagData($visitId, $tag);
}

function queuedPageViewData(
    string $visitId = 'session-abc',
    string $path = 'blog/my-post',
    ?string $route = 'blog.show',
    ?string $routePath = 'blog/{post}',
    int $timestamp = 1787577135,
    ?string $userAgent = 'JourneyBot/1.0',
): QueuedPageViewData {
    return new QueuedPageViewData($visitId, $path, $route, $routePath, $timestamp, $userAgent);
}

/** @param array<string, mixed> $data */
function queuedEventData(
    string $visitId = 'session-abc',
    string $path = 'blog/my-post',
    EventType $eventType = EventType::CLICKED,
    string $eventIdentifier = 'BlogDetailCard',
    array $data = ['id' => 7],
    bool $sensitive = false,
    int $timestamp = 1787577135,
): QueuedEventData {
    return new QueuedEventData($visitId, $path, $eventType, $eventIdentifier, $data, $sensitive, $timestamp);
}

/**
 * @return list<string>
 */
function validationFailures(ValidationRule $rule, mixed $value, string $attribute = "token"): array
{
    $failures = [];

    $rule->validate($attribute, $value, function (string $message) use (&$failures): void {
        $failures[] = $message;
    });

    return $failures;
}
