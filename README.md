# Journey Tracker for Laravel

Server-side SDK for [journey-tracker.cloud](https://journey-tracker.cloud). Register one piece of
middleware and every page view is recorded and stitched into a journey — the whole path a visitor
took through your application, in order, rather than a pile of unrelated page views.

It also accepts custom events from your frontend, lets you tag a journey from anywhere in your code,
and reads the collected counts back out with a fluent builder.

Nothing is sent from the request itself. Every send is a queued job, so your responses never wait on
us and never fail because of us.

## Requirements

- PHP 8.4+
- Laravel 12.61+ or 13.23+
- A session driver, a cache store, and a queue worker

The session is where the visit key lives, the cache holds the value we publish for how long a visit
lasts, and the worker is what actually sends anything. With no worker running, nothing arrives.

## Installation

```bash
composer require jpeters8889/journey-tracker-laravel
```

Put your app's key in `.env`. You'll find it in Journey Tracker under **Manage → API Keys**:

```dotenv
JOURNEY_TRACKER_TOKEN=your-key
```

Append the middleware to the `web` group. It has to run after `StartSession`, because the visit key
lives in the session, and appending does that:

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->web(append: [
        \Jpeters8889\JourneyTrackerLaravel\Http\Middleware\LogPageViewMiddleware::class,
    ]);
})
```

Add the directive to your layout, before `</body>`:

```blade
@journeyTracker
```

That's the install. [Installation](https://journey-tracker.cloud/docs/sending-data/laravel-sdk/installation)
covers what each step does, what the directive buys you, and how to check it worked.

Publishing the config file is optional — every setting already has a default:

```bash
php artisan vendor:publish --tag=journey-tracker-laravel-config
```

## What it adds to your application

Three `POST` routes, which the browser half of the package talks to:

```
journey-tracker-api/event
journey-tracker-api/heartbeat
journey-tracker-api/confirm
```

They're registered outside the `web` group, so they carry no CSRF and no session, and they're
authenticated by an encrypted token the package issues per request rather than by your app's auth.
All three paths are configurable if they clash with your own.

## What it sends

Per page view: the path, the matched route, a timestamp, and the visit key the package keeps in
your session. Alongside those it forwards request headers the browser already sent, which the
platform uses to tell a real navigation from an automated request:

```
User-Agent
Sec-Fetch-Mode
Sec-Fetch-Dest
Sec-Fetch-User
```

`User-Agent` is checked against known bots on arrival and then discarded: it is never stored, and it
is redacted from your API logs. The `Sec-Fetch-*` values say whether this was a real top-level
navigation. Heartbeats send none of them, because they come from a `fetch()` rather than a
navigation and would describe the request the script made rather than the page the visitor is on.

## When we can't send

Sending is fire and forget. The queued jobs swallow every failure, nothing is retried, and your
request path is never affected by what happens to us. What's new is that they tell you about it.

Two events, both in `Jpeters8889\JourneyTrackerLaravel\Events`:

`JourneyTrackerBlocked` is dispatched for one thing only — your team has used all of its credits for
the billing period and we've stopped accepting data until the period rolls over or you add more. It
carries the `IngestType` that was turned away.

`JourneyTrackerFailed` covers everything else: an error status from us, or the request never getting
there at all. It carries the `IngestType`, the HTTP status as `?int $status` (`null` when the request
didn't complete — connection refused, timeout, DNS), and the `Throwable`.

```php
use Illuminate\Support\Facades\Event;
use Jpeters8889\JourneyTrackerLaravel\Events\JourneyTrackerBlocked;

Event::listen(JourneyTrackerBlocked::class, function (JourneyTrackerBlocked $event): void {
    Log::warning('Journey Tracker has paused ingest', ['type' => $event->type->value]);
});
```

`IngestType` says which call it was:

| Case | Value | Sent when |
| --- | --- | --- |
| `PAGE_VIEW` | `page_view` | a page view or heartbeat is recorded |
| `PAGE_VIEW_CONFIRMATION` | `page_view_confirmation` | the browser confirms a page view |
| `EVENT` | `event` | a custom event is tracked |
| `TAG` | `tag` | a journey is tagged |

New kinds of data add a case here rather than another pair of events, so `match` on it with a
default arm.

A failure is usually transient, and a page view that fails is gone — nothing is held back or sent
again later. This covers sending only: `JourneyTracker::query()` is a synchronous read that reports
its errors from the call itself.

## Usage

```php
use Jpeters8889\JourneyTrackerLaravel\Facades\JourneyTracker;

// mark the journey this visitor is on
JourneyTracker::tag('shop-purchase');

// hand your frontend a token so it can post custom events
JourneyTracker::token();

// read the numbers back
JourneyTracker::query()
    ->between('2026-01-01', '2026-01-31')
    ->count('signups', fn (QueryDescriptor $query) => $query
        ->withPage(fn (PageFilter $page) => $page->path('register')))
    ->get()
    ->get('signups');
```

Queries are a synchronous call to us, unlike everything else here — run them on a schedule into your
own tables rather than in a request.

## Documentation

Everything lives at [journey-tracker.cloud/docs](https://journey-tracker.cloud/docs):

- [Installation](https://journey-tracker.cloud/docs/sending-data/laravel-sdk/installation)
- [Configuration](https://journey-tracker.cloud/docs/sending-data/laravel-sdk/configuration) — excluding routes, query strings, queues
- [Tagging a journey](https://journey-tracker.cloud/docs/sending-data/laravel-sdk/tagging-a-journey)
- [Tracking events](https://journey-tracker.cloud/docs/sending-data/laravel-sdk/tracking-events) — the payload, the token, and worked examples in Vue, React and Blade
- [Querying your data](https://journey-tracker.cloud/docs/sending-data/laravel-sdk/querying-your-data/building-a-query)
- [Testing](https://journey-tracker.cloud/docs/sending-data/laravel-sdk/testing) — how to test an app with this installed
- [Error handling](https://journey-tracker.cloud/docs/sending-data/laravel-sdk/error-handling) — the events we dispatch when we can't send

This package also ships [Laravel Boost](https://github.com/laravel/boost) guidelines and an agent
skill, so an AI assistant working in your codebase gets the same information.

## Testing

```bash
composer test
```

## License

MIT. See [LICENSE.md](LICENSE.md).
