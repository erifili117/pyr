<?php

declare(strict_types = 1);

namespace Beat\Pyr;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class RouteMiddlewareServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register() : void
    {
        $router = $this->app['router'];

        $routerPaths = [];

        /** @var Route $route */
        $isLumen = Str::contains($this->app->version(), 'Lumen');

        foreach ($router->getRoutes() as $routeName => $routeInfo) {
            if ($isLumen) {
                $routeUses = isset($routeInfo['action']['uses']) ? $routeInfo['action']['uses'] : '';
                $routeUri = isset($routeInfo['uri']) ? $routeInfo['uri'] : '';
            } else {
                $routeUses = isset($routeInfo->action['uses']) ? $routeInfo->action['uses'] : '';
                $routeUri = $routeInfo->uri;
            }

            $routerPaths[$routeUses] = $routeUri;
        }

        $this->app['prometheus.routes.paths'] = $routerPaths;
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides() : array
    {
        return [
            'prometheus.routes.paths',
        ];
    }
}

