<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Jpeters8889\JourneyTrackerLaravel\Support\RouteResolver;

it('resolves the name and uri of a static route', function (): void {
    Route::get('blog', fn (): string => 'ok')->name('blog.index');

    $route = resolver()->resolve('blog');

    expect($route->name)->toBe('blog.index')
        ->and($route->uri)->toBe('blog');
});

it('resolves a parameterised route without the bound record existing', function (): void {
    Route::get('blog/{post}', fn (): string => 'ok')->name('blog.show');

    $route = resolver()->resolve('blog/a-post-that-was-never-written');

    expect($route->name)->toBe('blog.show')
        ->and($route->uri)->toBe('blog/{post}');
});

it('leaves the name null for a route that was never given one', function (): void {
    Route::get('uses', fn (): string => 'ok');

    $route = resolver()->resolve('uses');

    expect($route->name)->toBeNull()
        ->and($route->uri)->toBe('uses');
});

it('returns nulls rather than throwing when nothing matches the path', function (): void {
    $route = resolver()->resolve('nothing/here');

    expect($route->name)->toBeNull()
        ->and($route->uri)->toBeNull();
});

it('ignores a query string when matching', function (): void {
    Route::get('blog', fn (): string => 'ok')->name('blog.index');

    $route = resolver()->resolve('blog?page=2');

    expect($route->name)->toBe('blog.index');
});

it('treats a leading slash and a bare path the same', function (): void {
    Route::get('blog', fn (): string => 'ok')->name('blog.index');

    expect(resolver()->resolve('/blog')->name)->toBe('blog.index')
        ->and(resolver()->resolve('blog')->name)->toBe('blog.index');
});

it('matches the route belonging to the host it is given', function (): void {
    Route::domain('admin.example.test')->get('reports', fn (): string => 'ok')->name('admin.reports');
    Route::get('reports', fn (): string => 'ok')->name('public.reports');

    expect(resolver()->resolve('reports', 'admin.example.test')->name)->toBe('admin.reports')
        ->and(resolver()->resolve('reports', 'www.example.test')->name)->toBe('public.reports');
});

it('falls back to a hostless match when no host is given', function (): void {
    Route::get('speaking', fn (): string => 'ok')->name('speaking');

    expect(resolver()->resolve('speaking', '')->name)->toBe('speaking');
});

function resolver(): RouteResolver
{
    return app(RouteResolver::class);
}
