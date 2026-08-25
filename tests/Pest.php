<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Unit', 'Feature');

function fakePageViewEndpoint(): void
{
    Http::fake([
        '*/api/page-view' => Http::response(),
    ]);
}
