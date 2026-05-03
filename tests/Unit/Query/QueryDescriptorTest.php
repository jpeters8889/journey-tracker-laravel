<?php

declare(strict_types=1);

use Jpeters8889\JourneyTrackerLaravel\Enums\EventType;
use Jpeters8889\JourneyTrackerLaravel\Query\EventFilter;
use Jpeters8889\JourneyTrackerLaravel\Query\PageFilter;
use Jpeters8889\JourneyTrackerLaravel\Query\QueryDescriptor;

it('always emits the has key even with no filters', function (): void {
    $descriptor = new QueryDescriptor('my_metric');

    expect($descriptor->toArray())->toBe(['as' => 'my_metric', 'has' => []]);
});

it('does not include events in has when no filters are added', function (): void {
    $descriptor = new QueryDescriptor('my_metric');

    expect($descriptor->toArray()['has'])->not->toHaveKey('events');
});

it('includes events in has when a filter is added', function (): void {
    $descriptor = new QueryDescriptor('clicks');
    $descriptor->addEventFilter((new EventFilter())->type(EventType::CLICKED));

    expect($descriptor->toArray()['has'])->toHaveKey('events')
        ->and($descriptor->toArray()['has']['events'])->toHaveCount(1);
});

it('maps multiple filters into the events array', function (): void {
    $descriptor = new QueryDescriptor('funnel');
    $descriptor->addEventFilter((new EventFilter())->type(EventType::CLICKED)->identifier('start'));
    $descriptor->addEventFilter((new EventFilter())->type(EventType::CLICKED)->identifier('end'));

    $events = $descriptor->toArray()['has']['events'];

    expect($events)->toHaveCount(2)
        ->and($events[0])->toBe(['type' => 'clicked', 'identifier' => 'start'])
        ->and($events[1])->toBe(['type' => 'clicked', 'identifier' => 'end']);
});

it('uses the alias in the as key', function (): void {
    expect((new QueryDescriptor('my_alias'))->toArray()['as'])->toBe('my_alias');
});

it('does not include pages in has when no page filters are added', function (): void {
    $descriptor = new QueryDescriptor('my_metric');

    expect($descriptor->toArray()['has'])->not->toHaveKey('pages');
});

it('includes pages in has when a page filter is added', function (): void {
    $descriptor = new QueryDescriptor('home_events');
    $descriptor->addPageFilter((new PageFilter())->path('/home'));

    expect($descriptor->toArray()['has'])->toHaveKey('pages')
        ->and($descriptor->toArray()['has']['pages'])->toHaveCount(1);
});

it('maps multiple page filters into the pages array', function (): void {
    $descriptor = new QueryDescriptor('funnel');
    $descriptor->addPageFilter((new PageFilter())->path('/start'));
    $descriptor->addPageFilter((new PageFilter())->path('/end'));

    $pages = $descriptor->toArray()['has']['pages'];

    expect($pages)->toHaveCount(2)
        ->and($pages[0])->toBe(['path' => '/start'])
        ->and($pages[1])->toBe(['path' => '/end']);
});
