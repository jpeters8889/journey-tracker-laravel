<?php

declare(strict_types=1);

use Jpeters8889\JourneyTrackerLaravel\DataObjects\QueuedTagData;

it('serialises to the wire shape the api expects', function (): void {
    $data = new QueuedTagData('session-abc', 'Shop Purchase');

    expect($data->toArray())->toBe([
        'session_id' => 'session-abc',
        'tag' => 'Shop Purchase',
    ]);
});

it('passes the tag through verbatim', function (string $tag): void {
    expect(new QueuedTagData('session-abc', $tag)->toArray()['tag'])->toBe($tag);
})->with([
    'Shop Purchase',
    'trial-started',
    'Café ☕ signup',
    'plan: "pro" & annual',
    '0',
    '',
]);
