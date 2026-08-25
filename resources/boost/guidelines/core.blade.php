@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Journey Tracker

Records visitor journeys — page views, custom events and tags — and queues them to journey-tracker.cloud. All configuration lives in `config/journey-tracker-laravel.php`.

@scoped(['bootstrap/app.php', 'app/Http/Kernel.php', 'app/Http/Middleware/**'])
## Page View Tracking

- `LogPageViewMiddleware` in the `web` middleware group is what records page views. Removing it, or moving it out of `web`, silently stops all tracking with no error.
- It must run after `StartSession`, because journeys are keyed on the session id. Appending it to the `web` group does this correctly.
@endscoped

@scoped(['resources/views/**'])
## Heartbeat

- @verbatim`@journeyTracker`@endverbatim in the main layout records the page views the server never sees: back/forward cache restores and in-SPA history navigation. Do not remove it — those views are lost silently.
- It is safe in any layout. It renders nothing when the current request is not being tracked.
@endscoped

@scoped(['routes/**', 'config/**'])
## Excluding Routes

- Admin, internal, health-check and webhook routes belong in `dont-track` in `config/journey-tracker-laravel.php`.
- Patterns are matched against the request path, the route name and the route URI. **Prefer path patterns** — only those are honoured for back/forward navigation, where no route is resolved.
@endscoped

For event tracking, tagging and querying collected data, use the `journey-tracker-development` skill.
