<?php

declare(strict_types=1);

use Jpeters8889\JourneyTrackerLaravel\DataObjects\QueuedPageViewData;

it('passes the timestamp through as an unmodified epoch', function (): void {
    $data = new QueuedPageViewData('session', '/', null, 1787577135);

    expect($data->toArray()['timestamp'])->toBe(1787577135);
});
