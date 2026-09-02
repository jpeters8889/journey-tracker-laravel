<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Jpeters8889\JourneyTrackerLaravel\DataObjects\QueuedConfirmationData;
use Jpeters8889\JourneyTrackerLaravel\Jobs\ConfirmPageViewJob;

it('posts the confirmation to the api', function (): void {
    fakeConfirmEndpoint();

    new ConfirmPageViewJob(new QueuedConfirmationData('visit-abc'))->handle();

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/v1/page-view/confirm')
        && $request->method() === 'POST'
        && $request->data() === ['visit_id' => 'visit-abc']);
});

it('does not throw when the api is unreachable', function (): void {
    Http::fake(fn () => throw new ConnectionException('offline'));

    new ConfirmPageViewJob(new QueuedConfirmationData('visit-abc'))->handle();
})->throwsNoExceptions();

it('does not throw when the api returns a server error', function (): void {
    Http::fake(['*' => Http::response('boom', 500)]);

    new ConfirmPageViewJob(new QueuedConfirmationData('visit-abc'))->handle();
})->throwsNoExceptions();
