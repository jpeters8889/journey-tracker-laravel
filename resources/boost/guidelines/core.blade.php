@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Journey Tracker

Records visitor journeys — page views, custom events and tags — and queues them to journey-tracker.cloud. Configuration lives in `config/journey-tracker-laravel.php`, and everything is sent by queued jobs, so a worker has to be running for any of it to arrive.

Full documentation is at https://journey-tracker.cloud/docs.

@scoped(['bootstrap/app.php', 'app/Http/Kernel.php', 'app/Http/Middleware/**'])
## Page View Tracking

- `LogPageViewMiddleware` in the `web` middleware group is what records page views. Removing it, or moving it out of `web`, silently stops all tracking with no error.
- It must run after `StartSession`, because the visit key that ties page views into one journey is stored in the session. Appending it to the `web` group does this correctly.
@endscoped

@scoped(['resources/views/**'])
## Heartbeat and confirmation

- @verbatim`@journeyTracker`@endverbatim in the main layout reports the page views the server never sees: back/forward cache restores and in-SPA history navigation. Do not remove it — those views are lost silently.
- It also confirms on load that a real browser rendered the page, which is one of several things that release a held first page view: a second page view, an event, a tag or a heartbeat do the same. Removing the directive does not turn the filtering off, it just removes the fastest proof.
- It is safe in any layout. It renders nothing when the current request is not being tracked.
@endscoped

@scoped(['routes/**', 'config/**'])
## Excluding Routes

- Admin, internal, health-check and webhook routes belong in `dont-track` in `config/journey-tracker-laravel.php`.
- Patterns are matched against the request path, the route name and the route URI. **Prefer path patterns** — only those are honoured for back/forward navigation, where no route is resolved.
@endscoped

@scoped(['config/**'])
## Query Strings

- Only query string parameters listed in `track-query-strings` are sent with a page view, matched with `Str::is()`. The default is Laravel's pagination parameters, `page` and `cursor`.
- Add a filter or sort parameter when a change to it should count as a new page view. Never add one that carries a secret or personal data.
@endscoped

For event tracking, tagging and querying collected data, use the `journey-tracker-development` skill.
