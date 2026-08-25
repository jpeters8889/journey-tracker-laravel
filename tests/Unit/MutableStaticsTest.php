<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

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
