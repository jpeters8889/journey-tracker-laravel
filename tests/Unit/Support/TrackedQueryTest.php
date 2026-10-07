<?php

declare(strict_types=1);

use Jpeters8889\JourneyTrackerLaravel\Support\TrackedQuery;

it('keeps page and cursor by default', function (): void {
    expect(new TrackedQuery()->filter(['page' => '2', 'cursor' => 'abc', 'fbclid' => 'xyz']))
        ->toBe(['cursor' => 'abc', 'page' => '2']);
});

it('keeps only the configured parameters', function (): void {
    config(['journey-tracker-laravel.track-query-strings' => ['meals', 'features']]);

    expect(new TrackedQuery()->filter(['meals' => 'lunch', 'features' => 'low-calorie', 'page' => '2']))
        ->toBe(['features' => 'low-calorie', 'meals' => 'lunch']);
});

it('matches wildcard patterns against the parameter name', function (): void {
    config(['journey-tracker-laravel.track-query-strings' => ['*Page']]);

    expect(new TrackedQuery()->filter(['commentsPage' => '3', 'page' => '1']))
        ->toBe(['commentsPage' => '3']);
});

it('sorts the parameters by name so the same state always compares equal', function (): void {
    config(['journey-tracker-laravel.track-query-strings' => ['*']]);

    expect(array_keys(new TrackedQuery()->filter(['b' => '1', 'c' => '1', 'a' => '1']) ?? []))
        ->toBe(['a', 'b', 'c']);
});

it('casts scalar values to strings', function (): void {
    expect(new TrackedQuery()->filter(['page' => 2]))->toBe(['page' => '2']);
});

it('keeps a list of values in the order it arrived', function (): void {
    config(['journey-tracker-laravel.track-query-strings' => ['freeFrom']]);

    expect(new TrackedQuery()->filter(['freeFrom' => ['egg', 'dairy']]))
        ->toBe(['freeFrom' => ['egg', 'dairy']]);
});

it('keeps keyed values', function (): void {
    config(['journey-tracker-laravel.track-query-strings' => ['filter']]);

    expect(new TrackedQuery()->filter(['filter' => ['status' => 'open', 'type' => 1]]))
        ->toBe(['filter' => ['status' => 'open', 'type' => '1']]);
});

it('keeps a comma separated value as a single string', function (): void {
    config(['journey-tracker-laravel.track-query-strings' => ['freeFrom']]);

    expect(new TrackedQuery()->filter(['freeFrom' => 'egg,dairy']))->toBe(['freeFrom' => 'egg,dairy']);
});

it('returns null when nothing is allowed', function (): void {
    expect(new TrackedQuery()->filter(['fbclid' => 'xyz']))->toBeNull()
        ->and(new TrackedQuery()->filter([]))->toBeNull();
});

it('drops values that hold nothing', function (): void {
    expect(new TrackedQuery()->filter(['page' => []]))->toBeNull();
});

it('falls back to page and cursor when the published config predates the key', function (): void {
    $config = config()->array('journey-tracker-laravel');

    unset($config['track-query-strings']);

    config(['journey-tracker-laravel' => $config]);

    expect(new TrackedQuery()->filter(['page' => '2', 'cursor' => 'abc', 'sort' => 'name']))
        ->toBe(['cursor' => 'abc', 'page' => '2']);
});

it('ignores patterns that are not strings', function (): void {
    config(['journey-tracker-laravel.track-query-strings' => ['page', 42, null]]);

    expect(new TrackedQuery()->filter(['page' => '2']))->toBe(['page' => '2']);
});

it('parses and filters a raw query string', function (): void {
    config(['journey-tracker-laravel.track-query-strings' => ['page', 'freeFrom']]);

    expect(new TrackedQuery()->filterQueryString('?page=2&freeFrom%5B%5D=egg&freeFrom%5B%5D=dairy&fbclid=xyz'))
        ->toBe(['freeFrom' => ['egg', 'dairy'], 'page' => '2']);
});

it('returns null for an empty query string', function (): void {
    expect(new TrackedQuery()->filterQueryString(''))->toBeNull();
});

it('turns each pattern into an anchored regular expression for the browser', function (): void {
    config(['journey-tracker-laravel.track-query-strings' => ['page', '*Page', 'filter.x']]);

    expect(new TrackedQuery()->scriptPatterns())->toBe(['^page$', '^.*Page$', '^filter\.x$']);
});

it('drops a value it cannot read as text', function (mixed $value): void {
    expect(new TrackedQuery()->filter(['page' => $value]))->toBeNull();
})->with([
    'nothing at all' => [null],
    'an object' => [new stdClass()],
]);
