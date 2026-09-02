<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Jpeters8889\JourneyTrackerLaravel\Support\TrackedRequest;

it('does nothing when asked to persist a visit outside a tracked request', function (): void {
    fakePageViewEndpoint();

    trackedRoute('/cs-adm/dashboard', function (): string {
        app(TrackedRequest::class)->persistVisit();

        return 'ok';
    });

    config(['journey-tracker-laravel.dont-track' => ['cs-adm/*']]);

    $this->get('/cs-adm/dashboard')->assertOk();

    expect(session()->get('journey-tracker.visit'))->toBeNull();

    Http::assertNothingSent();
});

it('reports nothing tracked until the request has been started', function (): void {
    untrackedRoute('/blog', function (): string {
        $trackedRequest = app(TrackedRequest::class);

        expect($trackedRequest->isTracking())->toBeFalse()
            ->and($trackedRequest->visitId())->toBeNull()
            ->and($trackedRequest->token())->toBeNull()
            ->and($trackedRequest->visitKeyWasNew())->toBeFalse()
            ->and($trackedRequest->confirmationExpected())->toBeFalse();

        return 'ok';
    });

    $this->get('/blog')->assertOk();
});
