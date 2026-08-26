<?php

declare(strict_types=1);

use Jpeters8889\JourneyTrackerLaravel\DataObjects\QueuedPageViewData;

it('serialises to the wire shape the api expects', function (): void {
    $data = new QueuedPageViewData('session-abc', 'blog/my-post', 'blog.show', 1787577135, 'JourneyBot/1.0');

    expect($data->toArray())->toBe([
        'session_id' => 'session-abc',
        'path' => 'blog/my-post',
        'route' => 'blog.show',
        'timestamp' => 1787577135,
        'user_agent' => 'JourneyBot/1.0',
    ]);
});

it('passes the timestamp through as an unmodified epoch', function (): void {
    $data = new QueuedPageViewData('session', '/', null, 1787577135);

    expect($data->toArray()['timestamp'])->toBe(1787577135);
});

it('sends a null route and user agent rather than omitting them', function (): void {
    $data = new QueuedPageViewData('session', '/', null, 1787577135);

    expect($data->toArray())->toHaveKeys(['route', 'user_agent'])
        ->and($data->toArray()['route'])->toBeNull()
        ->and($data->toArray()['user_agent'])->toBeNull();
});
