<?php

declare(strict_types=1);

use Jpeters8889\JourneyTrackerLaravel\JourneyTracker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Symfony\Component\Finder\Finder;

arch('the package declares strict types throughout')
    ->expect('Jpeters8889\JourneyTrackerLaravel')
    ->toUseStrictTypes();

arch('data objects are immutable value objects')
    ->expect('Jpeters8889\JourneyTrackerLaravel\DataObjects')
    ->toBeFinal()
    ->toBeReadonly();

arch('data objects stay behind the layers that queue them')
    ->expect('Jpeters8889\JourneyTrackerLaravel\DataObjects')
    ->toOnlyBeUsedIn([
        'Jpeters8889\JourneyTrackerLaravel\DataObjects',
        'Jpeters8889\JourneyTrackerLaravel\Http',
        'Jpeters8889\JourneyTrackerLaravel\Jobs',
        JourneyTracker::class,
    ]);

arch('every job is queueable')
    ->expect('Jpeters8889\JourneyTrackerLaravel\Jobs')
    ->toImplement(ShouldQueue::class);

arch('event types are string backed')
    ->expect('Jpeters8889\JourneyTrackerLaravel\Enums')
    ->toBeStringBackedEnums();

it('declares no mutable static properties anywhere in src', function (): void {
    $sourcePath = dirname(__DIR__, 2) . '/src';

    $offenders = [];

    foreach (Finder::create()->files()->in($sourcePath)->name('*.php') as $file) {
        $relativePath = str_replace([$sourcePath . DIRECTORY_SEPARATOR, '.php'], '', (string) $file->getRealPath());

        /** @var class-string $class */
        $class = 'Jpeters8889\JourneyTrackerLaravel\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

        $reflection = new ReflectionClass($class);

        foreach ($reflection->getProperties(ReflectionProperty::IS_STATIC) as $property) {
            if ($property->getDeclaringClass()->getName() !== $class) {
                continue;
            }

            $offenders[] = $class . '::$' . $property->getName();
        }
    }

    expect($offenders)->toBeEmpty();
});
