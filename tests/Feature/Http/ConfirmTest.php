<?php

declare(strict_types=1);

use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Jpeters8889\JourneyTrackerLaravel\Jobs\ConfirmPageViewJob;

it('forwards the visit id from the token so the platform can release the page view', function (): void {
    fakeConfirmEndpoint();

    $this->postJson(confirmUrl(), ['token' => journeyToken(visitId: 'visit-abc')])
        ->assertNoContent();

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/v1/page-view/confirm')
        && $request->data() === ['visit_id' => 'visit-abc']);
});

it('takes the visit id from the token and never from the request body', function (): void {
    fakeConfirmEndpoint();

    $this->postJson(confirmUrl(), [
        'token' => journeyToken(visitId: 'visit-abc'),
        'visit_id' => 'somebody-elses-visit',
    ])->assertNoContent();

    Http::assertSent(fn (Request $request): bool => $request->data() === ['visit_id' => 'visit-abc']);
});

it('errors without a token', function (): void {
    $this->postJson(confirmUrl(), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['token']);
});

it('errors with a token it cannot decrypt', function (): void {
    $this->postJson(confirmUrl(), ['token' => 'not-a-token'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['token']);
});

it('errors with a token encrypted by another application', function (): void {
    $foreign = new Encrypter(random_bytes(32), 'aes-256-cbc');

    $this->postJson(confirmUrl(), ['token' => $foreign->encrypt(['session_id' => 'visit-abc', 'path' => 'blog'])])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['token']);
});

it('routes the confirmation onto the configured queue', function (): void {
    config(['journey-tracker-laravel.queue' => 'analytics']);

    Queue::fake();

    $this->postJson(confirmUrl(), ['token' => journeyToken()])->assertNoContent();

    Queue::assertPushed(ConfirmPageViewJob::class, fn (ConfirmPageViewJob $job): bool => $job->queue === 'analytics');
});

it('sends nothing to the platform when the token is rejected', function (): void {
    fakeConfirmEndpoint();

    $this->postJson(confirmUrl(), ['token' => Crypt::encrypt('not-a-journey')])
        ->assertUnprocessable();

    Http::assertNothingSent();
});

it('still forwards a confirmation from a token minted before the visit id rename', function (): void {
    fakeConfirmEndpoint();

    $this->postJson(confirmUrl(), ['token' => legacyJourneyToken(visitId: 'session-abc')])
        ->assertNoContent();

    Http::assertSent(fn (Request $request): bool => $request->data() === ['visit_id' => 'session-abc']);
});
