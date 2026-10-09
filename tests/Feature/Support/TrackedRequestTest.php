<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Jpeters8889\JourneyTrackerLaravel\Support\JourneyToken;
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

describe('a request we saw but did not count', function (): void {
    it('mints a token from the visit already in the session', function (): void {
        fakePageViewEndpoint();

        trackedRoute('/blog', fn (): string => 'ok');
        trackedRoute('/recipes', fn (): string => app(TrackedRequest::class)->visitToken() ?? 'null');

        $this->get('/blog')->assertOk();

        $stored = session()->get('journey-tracker.visit');

        $token = $this->withHeader('Purpose', 'prefetch')->get('/recipes')->getContent();

        expect($token)->not->toBe('null');

        $decrypted = JourneyToken::decrypt((string) $token);

        expect($decrypted->visitId)->toBe($stored['id'])
            ->and($decrypted->path)->toBe('recipes');
    });

    it('leaves the stored visit exactly as it found it', function (): void {
        fakePageViewEndpoint();

        trackedRoute('/blog', fn (): string => 'ok');
        trackedRoute('/recipes', fn (): string => app(TrackedRequest::class)->visitToken() ?? 'null');

        $this->get('/blog')->assertOk();

        $before = session()->get('journey-tracker.visit');

        $this->travel(2)->minutes();

        $this->withHeader('Purpose', 'prefetch')->get('/recipes')->assertOk();

        expect(session()->get('journey-tracker.visit'))->toBe($before);
    });

    it('mints nothing when no visit has been stored yet', function (): void {
        trackedRoute('/recipes', fn (): string => app(TrackedRequest::class)->visitToken() ?? 'null');

        expect($this->withHeader('Purpose', 'prefetch')->get('/recipes')->getContent())->toBe('null');
    });

    it('mints nothing for a path the app asked us not to track', function (): void {
        fakePageViewEndpoint();

        config(['journey-tracker-laravel.dont-track' => ['cs-adm/*']]);

        trackedRoute('/blog', fn (): string => 'ok');
        trackedRoute('/cs-adm/dashboard', fn (): string => app(TrackedRequest::class)->visitToken() ?? 'null');

        $this->get('/blog')->assertOk();

        expect($this->get('/cs-adm/dashboard')->getContent())->toBe('null');
    });

    it('mints nothing on a route the middleware never ran on', function (): void {
        fakePageViewEndpoint();

        trackedRoute('/blog', fn (): string => 'ok');
        untrackedRoute('/standalone', fn (): string => app(TrackedRequest::class)->visitToken() ?? 'null');

        $this->get('/blog')->assertOk();

        expect($this->get('/standalone')->getContent())->toBe('null');
    });

    it('hands back the same token however many times it is asked', function (): void {
        fakePageViewEndpoint();

        trackedRoute('/blog', fn (): string => 'ok');
        trackedRoute('/recipes', function (): string {
            $trackedRequest = app(TrackedRequest::class);

            return $trackedRequest->visitToken() === $trackedRequest->visitToken() ? 'same' : 'different';
        });

        $this->get('/blog')->assertOk();

        expect($this->withHeader('Purpose', 'prefetch')->get('/recipes')->getContent())->toBe('same');
    });
});
