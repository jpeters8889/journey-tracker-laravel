<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Support;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Throwable;

class RouteResolver
{
    public function __construct(protected Router $router)
    {
        //
    }

    public function resolve(string $path, ?string $host = null): ResolvedRoute
    {
        try {
            $route = $this->router->getRoutes()->match($this->requestFor($path, $host));

            return new ResolvedRoute($route->getName(), $route->uri());
        } catch (Throwable) {
            return new ResolvedRoute();
        }
    }

    protected function requestFor(string $path, ?string $host): Request
    {
        $uri = '/' . ltrim(Str::before($path, '?'), '/');

        if ($host !== null && $host !== '') {
            $uri = 'http://' . $host . $uri;
        }

        return Request::create($uri);
    }
}
