# Journey Tracker for Laravel

Server-side SDK for [journey-tracker.cloud](https://journey-tracker.cloud). It records page views
automatically via middleware, accepts custom events from your frontend, lets you tag a journey, and
queries the collected data back out. Everything it sends is dispatched to the queue, so nothing
blocks a response.

## Requirements

- PHP 8.4+
- Laravel 12.61+ or 13.23+

## Installation

```bash
composer require jpeters8889/journey-tracker-laravel
php artisan vendor:publish --tag=journey-tracker-laravel-config
```

Set your application token in `.env`:

```dotenv
JOURNEY_TRACKER_TOKEN=your-token
```

Register the page view middleware by appending it to the `web` group. It **must** run after
`StartSession`, because the visit key that ties page views into one journey lives in the session —
appending does this correctly:

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->web(append: [
        \Jpeters8889\JourneyTrackerLaravel\Http\Middleware\LogPageViewMiddleware::class,
    ]);
})
```

Add the tracker directive to your main layout, before `</body>`:

```blade
@journeyTracker
```

It renders an empty string when the current request is not being tracked, and does two things.

It catches the page views that never reach the server — back/forward cache restores, and history
traversal inside an SPA.

It also confirms, on load, that a real browser rendered the page. The first page view of a visit
is held unwritten until that confirmation arrives, so automated traffic that stores no cookies and
runs no JavaScript is never recorded and never counts toward your ingest credits. Every later page
view in the visit is recorded immediately, because the returning visit key is itself proof of a
real client.

Without the directive nothing breaks: page views are recorded immediately, exactly as they were
before, and you get no filtering.

## Configuration

| Key | Env | Default | Purpose |
| --- | --- | --- | --- |
| `enabled` | `JOURNEY_TRACKER_ENABLED` | `true` | Master switch. When false, nothing is recorded at all |
| `app-token` | `JOURNEY_TRACKER_TOKEN` | `null` | Authenticates against the API |
| `queue` | `JOURNEY_TRACKER_QUEUE` | `null` | Queue name for the ingest jobs |
| `dont-track` | — | `[]` | Patterns excluded from tracking |
| `internal-event-endpoint` | — | `journey-tracker-api/event` | Route the package registers in your app |
| `heartbeat-endpoint` | — | `journey-tracker-api/heartbeat` | Route the package registers in your app |
| `confirm-endpoint` | — | `journey-tracker-api/confirm` | Route the package registers in your app |
| `visit-threshold-minutes` | — | `15` | Minutes of silence that end a visit. The platform publishes its own value and that wins |

`dont-track` patterns are matched with `Str::is()` against the request path, the route name, and the
route URI. Prefer path patterns — SPA history navigation reports only a path, so name and URI
patterns are not applied to those page views.

```php
'dont-track' => [
    'horizon/*',
    'admin/*',
],
```

## Usage

Tag the current visitor's journey, for example after a conversion. Tags key off the visit key, so
this only works on a request the middleware is tracking — on an excluded route, or outside a web
request, it does nothing:

```php
use Jpeters8889\JourneyTrackerLaravel\Facades\JourneyTracker;

JourneyTracker::tag('Shop Purchase');
```

Expose a token to your frontend so it can post custom events to the event endpoint. The SDK ships no
JavaScript by design — wiring the browser half is yours:

```php
JourneyTracker::token();
```

Query the collected counts back out with the fluent builder:

```php
use Jpeters8889\JourneyTrackerLaravel\Query\PageFilter;

$response = JourneyTracker::query()
    ->between('2026-01-01', '2026-01-31')
    ->count('signups')
    ->withPage(fn (PageFilter $filter): PageFilter => $filter->path('/register'))
    ->get();

$response->get('signups');
```

## Testing

```bash
composer test
```

## License

MIT. See [LICENSE.md](LICENSE.md).
