<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Jpeters8889\JourneyTrackerLaravel\Facades\JourneyTracker as JourneyTrackerFacade;
use Jpeters8889\JourneyTrackerLaravel\JourneyTracker;
use Jpeters8889\JourneyTrackerLaravel\Query\QueryBuilder;

it('exposes a token during a tracked request', function (): void {
    fakePageViewEndpoint();

    trackedRoute('/blog', fn (): string => app(JourneyTracker::class)->token() ?? 'null');

    expect($this->get('/blog')->getContent())->not->toBe('null');
});

it('returns the same token from two separately resolved instances', function (): void {
    fakePageViewEndpoint();

    trackedRoute('/blog', fn (): string => app(JourneyTracker::class)->token() === app(JourneyTracker::class)->token()
        ? 'same'
        : 'different');

    expect($this->get('/blog')->getContent())->toBe('same');
});

it('has no token on a route excluded by dont-track', function (): void {
    config(['journey-tracker-laravel.dont-track' => ['cs-adm/*']]);

    trackedRoute('/cs-adm/dashboard', fn (): string => app(JourneyTracker::class)->token() ?? 'null');

    expect($this->get('/cs-adm/dashboard')->getContent())->toBe('null');
});

it('does not leak a token into a later request that never ran the middleware', function (): void {
    fakePageViewEndpoint();

    trackedRoute('/blog', fn (): string => app(JourneyTracker::class)->token() ?? 'null');
    untrackedRoute('/standalone', fn (): string => app(JourneyTracker::class)->token() ?? 'null');

    expect($this->get('/blog')->getContent())->not->toBe('null')
        ->and($this->get('/standalone')->getContent())->toBe('null');
});

it('reports isTracking as true on a tracked route', function (): void {
    fakePageViewEndpoint();

    trackedRoute('/blog', fn (): string => app(JourneyTracker::class)->isTracking() ? 'yes' : 'no');

    expect($this->get('/blog')->getContent())->toBe('yes');
});

it('reports isTracking as false on an excluded route', function (): void {
    config(['journey-tracker-laravel.dont-track' => ['cs-adm/*']]);

    trackedRoute('/cs-adm/dashboard', fn (): string => app(JourneyTracker::class)->isTracking() ? 'yes' : 'no');

    expect($this->get('/cs-adm/dashboard')->getContent())->toBe('no');
});

it('exposes the visit id of a tracked request', function (): void {
    fakePageViewEndpoint();

    trackedRoute('/blog', fn (): string => app(JourneyTracker::class)->visitId() ?? 'null');

    expect($this->get('/blog')->getContent())->not->toBe('null');
});

it('has a null visit id when the request is not tracked', function (): void {
    config(['journey-tracker-laravel.dont-track' => ['cs-adm/*']]);

    trackedRoute('/cs-adm/dashboard', fn (): string => app(JourneyTracker::class)->visitId() ?? 'null');

    expect($this->get('/cs-adm/dashboard')->getContent())->toBe('null');
});

it('renders no heartbeat script when the request is not tracked', function (): void {
    config(['journey-tracker-laravel.dont-track' => ['cs-adm/*']]);

    trackedRoute('/cs-adm/dashboard', fn (): string => app(JourneyTracker::class)->heartbeatScript());

    expect($this->get('/cs-adm/dashboard')->getContent())->toBe('');
});

it('renders a heartbeat script listening for both popstate and pageshow', function (): void {
    fakePageViewEndpoint();

    trackedRoute('/blog', fn (): string => app(JourneyTracker::class)->heartbeatScript());

    expect($this->get('/blog')->getContent())
        ->toContain('popstate')
        ->toContain('pageshow')
        ->toContain('location.pathname');
});

it('embeds the current request token in the heartbeat script', function (): void {
    fakePageViewEndpoint();

    trackedRoute('/blog', fn (): string => app(JourneyTracker::class)->heartbeatScript());

    $response = $this->get('/blog');

    expect($response->getContent())->toContain((string) $response->headers->get('X-Journey-Token'));
});

it('points the heartbeat script at the configured endpoint', function (): void {
    config(['journey-tracker-laravel.heartbeat-endpoint' => 'custom/beat']);

    fakePageViewEndpoint();

    trackedRoute('/blog', fn (): string => app(JourneyTracker::class)->heartbeatScript());

    expect($this->get('/blog')->getContent())->toContain("u='/custom/beat'");
});

it('falls back to the packaged heartbeat endpoint when the published config predates the key', function (): void {
    $config = config()->array('journey-tracker-laravel');

    unset($config['heartbeat-endpoint']);

    config(['journey-tracker-laravel' => $config]);

    fakePageViewEndpoint();

    trackedRoute('/blog', fn (): string => app(JourneyTracker::class)->heartbeatScript());

    expect($this->get('/blog')->getContent())->toContain("u='/journey-tracker-api/heartbeat'");
});

it('wraps the heartbeat script in a script tag', function (): void {
    fakePageViewEndpoint();

    trackedRoute('/blog', fn (): string => app(JourneyTracker::class)->heartbeatScript());

    expect($this->get('/blog')->getContent())
        ->toStartWith('<script>')
        ->toEndWith('</script>');
});

it('returns a query builder from query()', function (): void {
    expect(app(JourneyTracker::class)->query())->toBeInstanceOf(QueryBuilder::class);
});

it('tags the journey of a tracked request with the tracked visit id', function (): void {
    fakeAllEndpoints();

    trackedRoute('/checkout/complete', function (): string {
        JourneyTrackerFacade::tag('Shop Purchase');

        return app(JourneyTracker::class)->visitId() ?? 'null';
    });

    $visitId = $this->get('/checkout/complete')->getContent();

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/tag')
        && $request->data() === ['session_id' => $visitId, 'tag' => 'Shop Purchase']);
});

it('tags nothing when the request is not being tracked', function (): void {
    fakeTagEndpoint();

    untrackedRoute('/checkout/complete', function (): string {
        app(JourneyTracker::class)->tag('Shop Purchase');

        return 'ok';
    });

    $this->get('/checkout/complete')->assertOk();

    Http::assertNothingSent();
});

it('tags identically whether called on the facade or the resolved instance', function (string $caller): void {
    fakeAllEndpoints();

    trackedRoute('/checkout/complete', function () use ($caller): string {
        $caller === 'facade'
            ? JourneyTrackerFacade::tag('Shop Purchase')
            : app(JourneyTracker::class)->tag('Shop Purchase');

        return 'ok';
    });

    $this->get('/checkout/complete');

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/tag')
        && $request->data()['tag'] === 'Shop Purchase');
})->with(['facade', 'instance']);

it('passes the tag through verbatim', function (string $tag): void {
    fakeAllEndpoints();

    trackedRoute('/checkout/complete', function () use ($tag): string {
        app(JourneyTracker::class)->tag($tag);

        return 'ok';
    });

    $this->get('/checkout/complete');

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/tag')
        && $request->data()['tag'] === $tag);
})->with([
    'Shop Purchase',
    'trial-started',
    'Café ☕ signup',
    'plan: "pro" & annual',
    '0',
]);

it('calls the confirmation endpoint as soon as the script runs', function (): void {
    fakePageViewEndpoint();

    trackedRoute('/blog', fn (): string => app(JourneyTracker::class)->heartbeatScript());

    expect($this->get('/blog')->getContent())
        ->toContain("c='/journey-tracker-api/confirm'")
        ->toContain('k();');
});

it('points the confirmation call at the configured endpoint', function (): void {
    config(['journey-tracker-laravel.confirm-endpoint' => 'custom/confirm']);

    fakePageViewEndpoint();

    trackedRoute('/blog', fn (): string => app(JourneyTracker::class)->heartbeatScript());

    expect($this->get('/blog')->getContent())->toContain("c='/custom/confirm'");
});

it('falls back to the packaged confirmation endpoint when the published config predates the key', function (): void {
    $config = config()->array('journey-tracker-laravel');

    unset($config['confirm-endpoint']);

    config(['journey-tracker-laravel' => $config]);

    fakePageViewEndpoint();

    trackedRoute('/blog', fn (): string => app(JourneyTracker::class)->heartbeatScript());

    expect($this->get('/blog')->getContent())->toContain("c='/journey-tracker-api/confirm'");
});
