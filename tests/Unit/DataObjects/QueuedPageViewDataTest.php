<?php

declare(strict_types=1);

use Jpeters8889\JourneyTrackerLaravel\DataObjects\QueuedPageViewData;

it('serialises to the wire shape the api expects', function (): void {
    $data = new QueuedPageViewData('session-abc', 'blog/my-post', 'blog.show', 'blog/{post}', 1787577135, 'JourneyBot/1.0');

    expect($data->toArray())->toBe([
        'visit_id' => 'session-abc',
        'path' => 'blog/my-post',
        'route' => 'blog.show',
        'route_path' => 'blog/{post}',
        'timestamp' => 1787577135,
        'user_agent' => 'JourneyBot/1.0',
        'visit_key_was_new' => false,
        'confirmation_expected' => false,
    ]);
});

it('passes the timestamp through as an unmodified epoch', function (): void {
    $data = new QueuedPageViewData('session', '/', null, null, 1787577135);

    expect($data->toArray()['timestamp'])->toBe(1787577135);
});

it('sends a null route, route path and user agent rather than omitting them', function (): void {
    $data = new QueuedPageViewData('session', '/', null, null, 1787577135);

    expect($data->toArray())->toHaveKeys(['route', 'route_path', 'user_agent'])
        ->and($data->toArray()['route'])->toBeNull()
        ->and($data->toArray()['route_path'])->toBeNull()
        ->and($data->toArray()['user_agent'])->toBeNull();
});

it('reports whether the visit key was freshly minted and whether the client can confirm itself', function (): void {
    $data = new QueuedPageViewData(
        'visit-abc',
        'blog/my-post',
        null,
        null,
        1787577135,
        null,
        visitKeyWasNew: true,
        confirmationExpected: true,
    );

    expect($data->toArray())
        ->toHaveKeys(['visit_key_was_new', 'confirmation_expected'])
        ->and($data->toArray()['visit_key_was_new'])->toBeTrue()
        ->and($data->toArray()['confirmation_expected'])->toBeTrue();
});
