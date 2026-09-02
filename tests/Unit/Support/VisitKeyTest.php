<?php

declare(strict_types=1);

use Illuminate\Cache\Repository;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Cache;
use Jpeters8889\JourneyTrackerLaravel\Support\VisitKey;

it('mints a key for a session it has never seen', function (): void {
    $visit = visitKeyOn($session = visitSession())->resolve();

    expect($visit->wasNew)->toBeTrue()
        ->and($visit->id)->toBeString()
        ->and($session->get('journey-tracker.visit')['id'])->toBe($visit->id);
});

it('reuses the key on a later request in the same session', function (): void {
    $session = visitSession();

    $first = visitKeyOn($session)->resolve();
    $second = visitKeyOn($session)->resolve();

    expect($second->id)->toBe($first->id)
        ->and($second->wasNew)->toBeFalse();
});

it('keeps the key while the gap stays inside the visit threshold', function (): void {
    config(['journey-tracker-laravel.visit-threshold-minutes' => 15]);

    $session = visitSession();

    $first = visitKeyOn($session)->resolve();

    $this->travel(14)->minutes();

    $second = visitKeyOn($session)->resolve();

    expect($second->id)->toBe($first->id)
        ->and($second->wasNew)->toBeFalse();
});

it('mints a new key once the visit threshold has passed', function (): void {
    config(['journey-tracker-laravel.visit-threshold-minutes' => 15]);

    $session = visitSession();

    $first = visitKeyOn($session)->resolve();

    $this->travel(16)->minutes();

    $second = visitKeyOn($session)->resolve();

    expect($second->id)->not->toBe($first->id)
        ->and($second->wasNew)->toBeTrue();
});

it('survives a session id regeneration so logging in does not split a visit', function (): void {
    $session = visitSession();

    $first = visitKeyOn($session)->resolve();
    $originalSessionId = $session->getId();

    $session->regenerate();

    $second = visitKeyOn($session)->resolve();

    expect($session->getId())->not->toBe($originalSessionId)
        ->and($second->id)->toBe($first->id)
        ->and($second->wasNew)->toBeFalse();
});

it('can put the visit back after a session has been flushed', function (): void {
    $session = visitSession();

    $visit = visitKeyOn($session)->resolve();

    $session->flush();

    expect($session->get('journey-tracker.visit'))->toBeNull();

    visitKeyOn($session)->persist($visit);

    expect($session->get('journey-tracker.visit')['id'])->toBe($visit->id);
});

it('ignores a stored visit it cannot read', function (mixed $stored): void {
    $session = visitSession();
    $session->put('journey-tracker.visit', $stored);

    expect(visitKeyOn($session)->resolve()->wasNew)->toBeTrue();
})->with([
    'not an array' => ['nonsense'],
    'no id' => [['seen' => 1787577135]],
    'no seen' => [['id' => 'visit-abc']],
    'seen is not an epoch' => [['id' => 'visit-abc', 'seen' => 'yesterday']],
]);

it('prefers the threshold the platform published over the configured fallback', function (): void {
    config(['journey-tracker-laravel.visit-threshold-minutes' => 15]);

    $visitKey = visitKeyOn(visitSession());

    $visitKey->rememberThreshold(30);

    expect($visitKey->thresholdMinutes())->toBe(30);
});

it('falls back to the configured threshold when the platform has published nothing', function (): void {
    config(['journey-tracker-laravel.visit-threshold-minutes' => 20]);

    expect(visitKeyOn(visitSession())->thresholdMinutes())->toBe(20);
});

it('falls back to a sane threshold when the published config predates the key', function (): void {
    $published = config()->array('journey-tracker-laravel');

    unset($published['visit-threshold-minutes']);

    config(['journey-tracker-laravel' => $published]);

    expect(visitKeyOn(visitSession())->thresholdMinutes())->toBe(15);
});

it('never accepts a published threshold that would rotate the key on every request', function (int $minutes): void {
    config(['journey-tracker-laravel.visit-threshold-minutes' => 15]);

    $visitKey = visitKeyOn(visitSession());

    $visitKey->rememberThreshold($minutes);

    expect($visitKey->thresholdMinutes())->toBe(15);
})->with([
    'zero' => [0],
    'negative' => [-5],
]);

it('does not rewrite the cached threshold when it has not changed', function (): void {
    $visitKey = visitKeyOn(visitSession());

    $visitKey->rememberThreshold(30);

    Cache::shouldReceive('get')->andReturn(30);
    Cache::shouldReceive('forever')->never();

    $visitKey->rememberThreshold(30);

    expect($visitKey->thresholdMinutes())->toBe(30);
});

function visitSession(): Store
{
    return new Store('journey-tracker-test', new ArraySessionHandler(120));
}

function visitKeyOn(Store $session): VisitKey
{
    $request = Request::create('/blog');
    $request->setLaravelSession($session);

    return new VisitKey($request, app(Repository::class));
}
