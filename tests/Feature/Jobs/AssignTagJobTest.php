<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Jpeters8889\JourneyTrackerLaravel\Jobs\AssignTagJob;

it('posts the tag payload to the api', function (): void {
    fakeTagEndpoint();

    new AssignTagJob(queuedTagData())->handle();

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/tag')
        && $request->method() === 'POST'
        && $request->data() === ['session_id' => 'session-abc', 'tag' => 'Shop Purchase']);
});

it('does not throw when the api is unreachable', function (): void {
    Http::fake(fn () => throw new ConnectionException('offline'));

    new AssignTagJob(queuedTagData())->handle();
})->throwsNoExceptions();

it('does not throw when the api returns a server error', function (): void {
    Http::fake(['*' => Http::response('boom', 500)]);

    new AssignTagJob(queuedTagData())->handle();
})->throwsNoExceptions();
