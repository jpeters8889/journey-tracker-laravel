<?php

declare(strict_types=1);

use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Jpeters8889\JourneyTrackerLaravel\Enums\EventType;

it('accepts an event and forwards the whole payload', function (): void {
    fakeEventEndpoint();

    $before = time();

    $this->postJson(eventUrl(), [
        'token' => journeyToken(sessionId: 'session-abc', path: 'blog/my-post'),
        'event_type' => 'clicked',
        'event_identifier' => 'BlogDetailCard',
        'data' => ['id' => 7, 'plan' => 'pro'],
        'sensitive' => true,
    ])->assertNoContent();

    Http::assertSent(function (Request $request) use ($before): bool {
        $data = $request->data();

        return str_ends_with($request->url(), '/api/event')
            && $data['session_id'] === 'session-abc'
            && $data['path'] === 'blog/my-post'
            && $data['event_type'] === 'clicked'
            && $data['event_identifier'] === 'BlogDetailCard'
            && $data['data'] === ['id' => 7, 'plan' => 'pro']
            && $data['sensitive'] === true
            && $data['timestamp'] >= $before
            && $data['timestamp'] <= time();
    });
});

it('takes the session and path from the token, never from the request body', function (): void {
    fakeEventEndpoint();

    $this->postJson(eventUrl(), [
        'token' => journeyToken(sessionId: 'session-abc', path: 'blog'),
        'event_type' => 'clicked',
        'event_identifier' => 'cta',
        'session_id' => 'somebody-elses-session',
        'path' => 'somewhere/else',
    ])->assertNoContent();

    Http::assertSent(fn (Request $request): bool => $request->data()['session_id'] === 'session-abc'
        && $request->data()['path'] === 'blog');
});

it('accepts every event type', function (EventType $eventType): void {
    fakeEventEndpoint();

    $this->postJson(eventUrl(), [
        'token' => journeyToken(),
        'event_type' => $eventType->value,
        'event_identifier' => 'thing',
    ])->assertNoContent();

    Http::assertSent(fn (Request $request): bool => $request->data()['event_type'] === $eventType->value);
})->with(EventType::cases());

it('defaults data to an empty array and sensitive to false when both are omitted', function (): void {
    fakeEventEndpoint();

    $this->postJson(eventUrl(), [
        'token' => journeyToken(),
        'event_type' => 'other',
        'event_identifier' => 'thing',
    ])->assertNoContent();

    Http::assertSent(fn (Request $request): bool => $request->data()['data'] === []
        && $request->data()['sensitive'] === false);
});

it('keeps a nested data payload intact', function (): void {
    fakeEventEndpoint();

    $data = ['order' => ['id' => 1, 'lines' => [['sku' => 'A'], ['sku' => 'B']]], 'coupon' => null];

    $this->postJson(eventUrl(), [
        'token' => journeyToken(),
        'event_type' => 'other',
        'event_identifier' => 'purchase',
        'data' => $data,
    ])->assertNoContent();

    Http::assertSent(fn (Request $request): bool => $request->data()['data'] === $data);
});

it('rejects an event with no token', function (): void {
    $this->postJson(eventUrl(), [
        'event_type' => 'clicked',
        'event_identifier' => 'cta',
    ])->assertJsonValidationErrorFor('token');
});

it('rejects an event with an unknown event type', function (): void {
    $this->postJson(eventUrl(), [
        'token' => journeyToken(),
        'event_type' => 'exploded',
        'event_identifier' => 'cta',
    ])->assertJsonValidationErrorFor('event_type');
});

it('rejects an event with no event identifier', function (): void {
    $this->postJson(eventUrl(), [
        'token' => journeyToken(),
        'event_type' => 'clicked',
    ])->assertJsonValidationErrorFor('event_identifier');
});

it('rejects an event whose data is not an array', function (): void {
    $this->postJson(eventUrl(), [
        'token' => journeyToken(),
        'event_type' => 'clicked',
        'event_identifier' => 'cta',
        'data' => 'not-an-array',
    ])->assertJsonValidationErrorFor('data');
});

it('rejects a tampered token with a validation error rather than a server error', function (): void {
    fakeEventEndpoint();

    $this->postJson(eventUrl(), [
        'token' => 'not-a-real-token',
        'event_type' => 'clicked',
        'event_identifier' => 'cta',
    ])->assertJsonValidationErrorFor('token');

    Http::assertNothingSent();
});

it('rejects a token encrypted under a different app key', function (): void {
    fakeEventEndpoint();

    $foreign = new Encrypter(Encrypter::generateKey('aes-256-cbc'), 'aes-256-cbc');

    $this->postJson(eventUrl(), [
        'token' => $foreign->encrypt(['session_id' => 'session-abc', 'path' => 'blog']),
        'event_type' => 'clicked',
        'event_identifier' => 'cta',
    ])->assertJsonValidationErrorFor('token');

    Http::assertNothingSent();
});

it('records events even for a path excluded by dont-track', function (): void {
    config(['journey-tracker-laravel.dont-track' => ['cs-adm/*']]);

    fakeEventEndpoint();

    $this->postJson(eventUrl(), [
        'token' => journeyToken(path: 'cs-adm/dashboard'),
        'event_type' => 'clicked',
        'event_identifier' => 'cta',
    ])->assertNoContent();

    Http::assertSent(fn (Request $request): bool => $request->data()['path'] === 'cs-adm/dashboard');
});
