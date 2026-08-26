<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Jpeters8889\JourneyTrackerLaravel\Support\TrackingPolicy;

it('tracks a plain GET request', function (): void {
    expect((new TrackingPolicy())->shouldTrackRequest(policyRequest()))->toBeTrue();
});

it('rejects a request that is not a GET', function (string $method): void {
    expect((new TrackingPolicy())->shouldTrackRequest(policyRequest('/blog', $method)))->toBeFalse();
})->with(['POST', 'PUT', 'PATCH', 'DELETE', 'HEAD']);

it('rejects a request with no session, rather than throwing', function (): void {
    expect((new TrackingPolicy())->shouldTrackRequest(Request::create('/blog')))->toBeFalse();
});

it('rejects an inertia partial reload', function (): void {
    $request = policyRequest();
    $request->headers->set('X-Inertia-Partial-Component', 'Blog/Index');

    expect((new TrackingPolicy())->shouldTrackRequest($request))->toBeFalse();
});

it('still tracks a normal inertia visit', function (): void {
    $request = policyRequest();
    $request->headers->set('X-Inertia', 'true');

    expect((new TrackingPolicy())->shouldTrackRequest($request))->toBeTrue();
});

it('rejects a prefetch identified by the Purpose header', function (): void {
    $request = policyRequest();
    $request->headers->set('Purpose', 'prefetch');

    expect((new TrackingPolicy())->shouldTrackRequest($request))->toBeFalse();
});

it('rejects a speculation rules prefetch or prerender via Sec-Purpose', function (string $value): void {
    $request = policyRequest();
    $request->headers->set('Sec-Purpose', $value);

    expect((new TrackingPolicy())->shouldTrackRequest($request))->toBeFalse();
})->with(['prefetch', 'prefetch;prerender', 'prefetch;anonymous-client-ip']);

it('tracks nothing at all when disabled', function (): void {
    config(['journey-tracker-laravel.enabled' => false]);

    expect((new TrackingPolicy())->shouldTrackRequest(policyRequest()))->toBeFalse()
        ->and((new TrackingPolicy())->shouldTrackPath('blog'))->toBeFalse();
});

it('honours a dont-track path pattern', function (): void {
    config(['journey-tracker-laravel.dont-track' => ['cs-adm/*']]);

    expect((new TrackingPolicy())->shouldTrackRequest(policyRequest('/cs-adm/dashboard')))->toBeFalse()
        ->and((new TrackingPolicy())->shouldTrackRequest(policyRequest('/blog')))->toBeTrue();
});

it('normalises a leading slash on configured dont-track patterns', function (): void {
    config(['journey-tracker-laravel.dont-track' => ['/nova*']]);

    expect((new TrackingPolicy())->shouldTrackRequest(policyRequest('/nova/resources')))->toBeFalse();
});

it('normalises a leading slash on the path being checked', function (): void {
    config(['journey-tracker-laravel.dont-track' => ['horizon/*']]);

    expect((new TrackingPolicy())->shouldTrackPath('/horizon/dashboard'))->toBeFalse()
        ->and((new TrackingPolicy())->shouldTrackPath('horizon/dashboard'))->toBeFalse();
});

it('honours a dont-track route name', function (): void {
    config(['journey-tracker-laravel.dont-track' => ['fallback']]);

    expect((new TrackingPolicy())->shouldTrackPath('anything', 'fallback'))->toBeFalse()
        ->and((new TrackingPolicy())->shouldTrackPath('anything', 'blog.index'))->toBeTrue();
});

it('honours a dont-track route uri', function (): void {
    config(['journey-tracker-laravel.dont-track' => ['static/map/{latlng}']]);

    expect((new TrackingPolicy())->shouldTrackPath('static/map/1,2', null, 'static/map/{latlng}'))->toBeFalse();
});

it('tracks everything when dont-track is empty', function (): void {
    config(['journey-tracker-laravel.dont-track' => []]);

    expect((new TrackingPolicy())->shouldTrackPath('literally/anything'))->toBeTrue();
});

it('tracks when the published config predates the enabled key', function (): void {
    $published = config()->array('journey-tracker-laravel');

    unset($published['enabled']);

    config(['journey-tracker-laravel' => $published]);

    expect((new TrackingPolicy())->shouldTrackPath('blog'))->toBeTrue();
});

it('tracks when the published config predates the dont-track key', function (): void {
    $published = config()->array('journey-tracker-laravel');

    unset($published['dont-track']);

    config(['journey-tracker-laravel' => $published]);

    expect((new TrackingPolicy())->shouldTrackPath('blog'))->toBeTrue();
});

it('honours a wildcard in the middle of a dont-track pattern', function (): void {
    config(['journey-tracker-laravel.dont-track' => ['blog/*/edit']]);

    expect((new TrackingPolicy())->shouldTrackPath('blog/my-post/edit'))->toBeFalse()
        ->and((new TrackingPolicy())->shouldTrackPath('blog/my-post'))->toBeTrue();
});

it('does not treat a dont-track pattern as a partial match', function (): void {
    config(['journey-tracker-laravel.dont-track' => ['admin']]);

    expect((new TrackingPolicy())->shouldTrackPath('admin'))->toBeFalse()
        ->and((new TrackingPolicy())->shouldTrackPath('administrators'))->toBeTrue()
        ->and((new TrackingPolicy())->shouldTrackPath('admin/users'))->toBeTrue();
});
