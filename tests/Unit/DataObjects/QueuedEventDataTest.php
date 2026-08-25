<?php

declare(strict_types=1);

use Jpeters8889\JourneyTrackerLaravel\DataObjects\QueuedEventData;
use Jpeters8889\JourneyTrackerLaravel\Enums\EventType;

it('passes the timestamp through as an unmodified epoch', function (): void {
    $data = new QueuedEventData('session', '/', EventType::CLICKED, 'cta', [], false, 1787577135);

    expect($data->toArray()['timestamp'])->toBe(1787577135);
});
