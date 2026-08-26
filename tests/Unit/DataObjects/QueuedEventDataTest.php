<?php

declare(strict_types=1);

use Jpeters8889\JourneyTrackerLaravel\DataObjects\QueuedEventData;
use Jpeters8889\JourneyTrackerLaravel\Enums\EventType;

it('serialises to the wire shape the api expects', function (): void {
    $data = new QueuedEventData(
        'session-abc',
        'blog/my-post',
        EventType::CLICKED,
        'BlogDetailCard',
        ['id' => 7],
        true,
        1787577135,
    );

    expect($data->toArray())->toBe([
        'session_id' => 'session-abc',
        'path' => 'blog/my-post',
        'event_type' => 'clicked',
        'event_identifier' => 'BlogDetailCard',
        'data' => ['id' => 7],
        'sensitive' => true,
        'timestamp' => 1787577135,
    ]);
});

it('passes the timestamp through as an unmodified epoch', function (): void {
    $data = new QueuedEventData('session', '/', EventType::CLICKED, 'cta', [], false, 1787577135);

    expect($data->toArray()['timestamp'])->toBe(1787577135);
});

it('sends the string value of every event type', function (EventType $eventType): void {
    $data = new QueuedEventData('session', '/', $eventType, 'cta', [], false, 1);

    expect($data->toArray()['event_type'])->toBe($eventType->value);
})->with(EventType::cases());

it('keeps an empty data array empty rather than dropping the key', function (): void {
    $data = new QueuedEventData('session', '/', EventType::OTHER, 'cta', [], false, 1);

    expect($data->toArray())->toHaveKey('data')
        ->and($data->toArray()['data'])->toBe([]);
});

it('preserves a nested data payload untouched', function (): void {
    $payload = ['order' => ['id' => 1, 'lines' => [['sku' => 'A']]], 'coupon' => null];

    $data = new QueuedEventData('session', '/', EventType::OTHER, 'purchase', $payload, false, 1);

    expect($data->toArray()['data'])->toBe($payload);
});

it('keeps sensitive a strict boolean', function (bool $sensitive): void {
    $data = new QueuedEventData('session', '/', EventType::OTHER, 'cta', [], $sensitive, 1);

    expect($data->toArray()['sensitive'])->toBe($sensitive);
})->with([true, false]);
