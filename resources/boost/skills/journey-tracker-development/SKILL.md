---
name: journey-tracker-development
description: "Use when working with journey-tracker-laravel, the server side SDK for journey-tracker.cloud visitor analytics. Trigger whenever the query mentions journey tracker, journey tracking, the JourneyTracker facade, `Jpeters8889\\JourneyTrackerLaravel`, `config/journey-tracker-laravel.php`, LogPageViewMiddleware, or the journeyTracker Blade directive. Tasks include installing and configuring the SDK, excluding routes from tracking with dont-track, tagging a journey after a conversion or purchase, wiring up client side event tracking against the event endpoint, and querying collected page view and event counts with the fluent query builder for metrics jobs, backfill commands and dashboards. Do not trigger for Google Analytics, Laravel Nightwatch, Pulse, Telescope, or generic application logging."
license: MIT
metadata:
  author: jpeters8889
---

# Journey Tracker Development

Server side SDK for journey-tracker.cloud. It records page views automatically via middleware,
accepts custom events from your frontend, lets you tag a journey, and queries the collected data
back out. Everything it sends is dispatched to the queue, so nothing blocks a response.

## Setup

```bash
composer require jpeters8889/journey-tracker-laravel
php artisan vendor:publish --tag=journey-tracker-laravel-config
```

Register the page view middleware by appending it to the `web` group. It **must** run after
`StartSession`, because journeys are keyed on the session id — appending does this correctly:

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->web(append: [
        \Jpeters8889\JourneyTrackerLaravel\Http\Middleware\LogPageViewMiddleware::class,
    ]);
})
```

Add the heartbeat directive to the main layout, before `</body>`:

```blade
@journeyTracker
```

The directive is safe in every layout — it renders an empty string when the current request is not
being tracked. It exists to catch the page views that never reach the server: back/forward cache
restores, and history traversal inside an SPA where the framework restores from history rather than
re-requesting. Without it those views are lost silently.

### Configuration

| Key | Env | Default | Purpose |
| --- | --- | --- | --- |
| `enabled` | `JOURNEY_TRACKER_ENABLED` | `true` | Master switch. When false, nothing is recorded at all |
| `app-token` | `JOURNEY_TRACKER_TOKEN` | `null` | Authenticates against the API |
| `queue` | `JOURNEY_TRACKER_QUEUE` | `null` | Queue name for the ingest jobs |
| `dont-track` | — | `[]` | Patterns excluded from tracking |
| `internal-event-endpoint` | — | `journey-tracker-api/event` | Route the package registers in your app |
| `heartbeat-endpoint` | — | `journey-tracker-api/heartbeat` | Route the package registers in your app |

### Excluding routes

`dont-track` patterns are matched with `Str::is()` against the request path, the route name, **and**
the route URI. A leading slash is optional and normalised away.

```php
'dont-track' => [
    'horizon/*',            // path
    'admin/*',              // path
    'static/map/{latlng}',  // route URI
    'fallback',             // route name
],
```

**Prefer path patterns.** Back/forward navigation in an SPA reports only a path — there is no route
to resolve — so route name and route URI patterns are not applied to those page views. Anything that
genuinely must never be recorded should be expressed as a path pattern.

Requests are also skipped automatically when they are not `GET`, carry no session, are an Inertia
partial reload, or are a prefetch (`Purpose: prefetch` or `Sec-Purpose`). You do not need to exclude
those yourself.

## Tagging a journey

Attach a label to the current visitor's journey — useful for marking a conversion:

```php
use Jpeters8889\JourneyTrackerLaravel\Facades\JourneyTracker;

JourneyTracker::tag('Shop Purchase');
```

Tags key off the session id, so this only works **inside a web request**. Calling it from a queued
job, a console command or a scheduled task will not attach the tag to the visitor's journey.

## Event tracking

The SDK deliberately ships **no JavaScript**. It registers the event endpoint in your app and hands
you a token; wiring the browser half is yours, so the package never has to track Vue, React, Svelte,
Alpine or whatever comes next. Do not go looking for a companion JS package — there isn't one, and
that is a design decision rather than an omission.

Two steps.

**1. Expose the token to the frontend.** `JourneyTracker::token()` returns an encrypted token for the
current request, or `null` when the request is not being tracked. It is stable for the life of the
request — repeated calls return the identical string.

```php
use Jpeters8889\JourneyTrackerLaravel\Facades\JourneyTracker;

// Inertia — in your HandleInertiaRequests middleware or response builder
Inertia::share('journey.token', fn (): ?string => JourneyTracker::token());

// Blade — pass it to the view, or read it from the X-Journey-Token response header
```

**2. POST events to the endpoint.** The path comes from `internal-event-endpoint`.

| Field | Type | Notes |
| --- | --- | --- |
| `token` | string | Required. From `JourneyTracker::token()` |
| `event_type` | string | Required. One of `scrolled_into_view`, `typed`, `clicked`, `other` |
| `event_identifier` | string | Required. Your name for the thing, e.g. `BlogDetailCard` |
| `data` | object | Optional. Arbitrary parameters. Queryable later, unless `sensitive` is true |
| `sensitive` | bool | Optional. Encrypts `data` at rest and locks it down — see below |

The session and path are taken from the decrypted token, never from the request, so a client cannot
attribute events to another journey.

### The `title` key

`title` is the one conventional key in `data`. When present, the UI displays it on the event
occurrence, so a list of events reads as the things they happened to rather than a wall of repeated
identifiers. Pass whatever names the record:

```ts
useJourneyTracking().logEvent('scrolled_into_view', 'BlogDetailCard', {
  title: blog.title,
});
```

Prefer it over inventing your own naming key such as `name` or `label` — `title` is the only one the
UI renders.

### Marking an event sensitive

`sensitive: true` is not a label, it changes how the payload is handled:

- `data` is **encrypted at rest**.
- It is **not queryable** — sensitive events cannot be filtered by their parameters, so
  `withParameters()` will not match them.
- Viewing it in the UI requires **password confirmation**.

Use it for anything you would not want sitting in plain text or appearing in an ordinary query —
what someone typed into a field, for instance. The trade-off is real and one-way: anything marked
sensitive is permanently outside your metrics. If you need to count it, do not mark it sensitive;
send a separate non-sensitive event carrying only the parts that are safe to aggregate.

### Worked example — Inertia + Vue

```ts
// resources/js/composables/useJourneyTracking.ts
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

export default () => {
  const logEvent = (
    type: string,
    identifier: string,
    data: object = {},
    sensitive: boolean = false,
  ) => {
    if (import.meta.env.SSR) {
      return;
    }

    const token = usePage<{ journey?: { token: string } }>().props.journey?.token;

    if (!token) {
      return;
    }

    axios
      .post('/journey-tracker-api/event', {
        token,
        event_type: type,
        event_identifier: identifier,
        data,
        sensitive,
      })
      .catch(() => {
        //
      });
  };

  return { logEvent };
};
```

Guard against SSR, and swallow failures — analytics must never break a page.

## Querying collected data

`JourneyTracker::query()` returns a fluent builder. Each `count()` declares one aliased metric, and
one request fetches them all.

```php
use Jpeters8889\JourneyTrackerLaravel\Enums\EventType;
use Jpeters8889\JourneyTrackerLaravel\Facades\JourneyTracker;
use Jpeters8889\JourneyTrackerLaravel\Query\EventFilter;
use Jpeters8889\JourneyTrackerLaravel\Query\PageFilter;
use Jpeters8889\JourneyTrackerLaravel\Query\QueryDescriptor;

$metrics = JourneyTracker::query()
    ->today($date)
    ->count(
        'views',
        fn (QueryDescriptor $query) => $query
            ->withPage(fn (PageFilter $page) => $page->path('blog/my-post'))
    )
    ->count(
        'card_views',
        fn (QueryDescriptor $query) => $query
            ->withEvent(
                fn (EventFilter $event) => $event
                    ->type(EventType::SCROLLED_INTO_VIEW)
                    ->identifier('BlogDetailCard')
                    ->withParameters(['id' => $blog->id])
            )
    )
    ->get();

$metrics->get('views');   // int
$metrics->card_views;     // int, same thing via magic accessor
```

### Date range

| Method | Effect |
| --- | --- |
| `from($date)` / `to($date)` | Bounds, as `Y-m-d` strings or any `DateTimeInterface` |
| `between($from, $to)` | Both at once |
| `today($date = null)` | Both bounds to the same day; with no argument sends the literal `today`, resolved server side rather than against your app timezone |
| `daily()` | Break the result down per day instead of one total |

### Reading the response

Without `daily()` the response is a map of alias to integer, read with `get($alias)` or the magic
accessor. With `daily()` it is a list of rows, one per date, each carrying every alias — read it with
`all()`:

```php
$metrics = JourneyTracker::query()
    ->between($start, today())
    ->daily()
    ->count('views', fn (QueryDescriptor $query) => $query
        ->withPage(fn (PageFilter $page) => $page->path('blog/my-post')))
    ->get();

collect($metrics->all())->each(function (array $row): void {
    // ['date' => '2026-01-01', 'views' => 10]
});
```

### Filters

`PageFilter` supports `id()` and `path()`. **Paths are stored without a leading slash** — pass
`blog/my-post`, not `/blog/my-post`, or trim it with `ltrim($path, '/')`.

`EventFilter` supports `type()` (an `EventType` or its string value), `identifier()`, and
`withParameters()` for matching against the `data` you sent. `withParameters()` replaces rather than
merges, so pass the whole array at once. Events sent with `sensitive: true` have encrypted data and
**will never match** `withParameters()` — a count that comes back as zero when you expected results
is usually this.

Filters can also be chained straight onto the builder, in which case they attach to the **most
recent** `count()`. Prefer the closure form above — it makes the association explicit and cannot be
misordered.

`raw(array $payload)` posts a payload verbatim as an escape hatch. It ignores every other builder
method, including date ranges, so do not combine them.

## Testing

The SDK talks to the API through an `Http` macro, so `Http::fake()` covers everything without
reaching the network.

```php
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('records a page view', function (): void {
    Http::fake(['*/api/page-view' => Http::response()]);

    $this->get('/blog/my-post')->assertOk();

    Http::assertSent(fn (Request $request): bool => $request->data()['path'] === 'blog/my-post');
});
```

To assert a query without hitting the API, fake `*/api/query` and return a `data` key shaped like the
response you expect. Ingest jobs are queued, so use `Queue::fake()` if you would rather assert
dispatch than the outbound payload.
