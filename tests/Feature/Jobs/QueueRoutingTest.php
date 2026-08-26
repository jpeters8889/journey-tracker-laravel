<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;
use Jpeters8889\JourneyTrackerLaravel\Jobs\AssignTagJob;
use Jpeters8889\JourneyTrackerLaravel\Jobs\LogPageEventJob;
use Jpeters8889\JourneyTrackerLaravel\Jobs\LogPageViewJob;
use Jpeters8889\JourneyTrackerLaravel\JourneyTracker;

it('routes a page view onto the configured queue', function (): void {
    config(['journey-tracker-laravel.queue' => 'analytics']);

    Queue::fake();

    trackedRoute('/blog', fn (): string => 'ok');

    $this->get('/blog');

    Queue::assertPushed(LogPageViewJob::class, fn (LogPageViewJob $job): bool => $job->queue === 'analytics');
});

it('routes an event onto the configured queue', function (): void {
    config(['journey-tracker-laravel.queue' => 'analytics']);

    Queue::fake();

    $this->postJson(eventUrl(), [
        'token' => journeyToken(),
        'event_type' => 'clicked',
        'event_identifier' => 'cta',
    ])->assertNoContent();

    Queue::assertPushed(LogPageEventJob::class, fn (LogPageEventJob $job): bool => $job->queue === 'analytics');
});

it('routes a tag onto the configured queue', function (): void {
    config(['journey-tracker-laravel.queue' => 'analytics']);

    Queue::fake();

    untrackedRoute('/checkout', function (): string {
        app(JourneyTracker::class)->tag('Shop Purchase');

        return 'ok';
    });

    $this->get('/checkout');

    Queue::assertPushed(AssignTagJob::class, fn (AssignTagJob $job): bool => $job->queue === 'analytics');
});

it('leaves the queue unset when none is configured', function (): void {
    config(['journey-tracker-laravel.queue' => null]);

    Queue::fake();

    trackedRoute('/blog', fn (): string => 'ok');

    $this->get('/blog');

    Queue::assertPushed(LogPageViewJob::class, fn (LogPageViewJob $job): bool => $job->queue === null);
});

it('leaves the event queue unset when none is configured', function (): void {
    config(['journey-tracker-laravel.queue' => null]);

    Queue::fake();

    $this->postJson(eventUrl(), [
        'token' => journeyToken(),
        'event_type' => 'clicked',
        'event_identifier' => 'cta',
    ])->assertNoContent();

    Queue::assertPushed(LogPageEventJob::class, fn (LogPageEventJob $job): bool => $job->queue === null);
});

it('leaves the tag queue unset when none is configured', function (): void {
    config(['journey-tracker-laravel.queue' => null]);

    Queue::fake();

    untrackedRoute('/checkout', function (): string {
        app(JourneyTracker::class)->tag('Shop Purchase');

        return 'ok';
    });

    $this->get('/checkout');

    Queue::assertPushed(AssignTagJob::class, fn (AssignTagJob $job): bool => $job->queue === null);
});
