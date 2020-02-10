<?php

declare(strict_types = 1);

namespace Beat\Pyr;

use Orchestra\Testbench\TestCase;

/**
 * @covers \Beat\Pyr\RouteMiddlewareServiceProvider<extended>
 */
class RouteMiddlewareServiceProviderTest extends TestCase
{
    public function testServiceProvider() : void
    {
        /* @var \Illuminate\Support\Facades\Route $router */
        $router = $this->app['router'];

        $router->post(
            '/test/{id}/test', 'Test@getTest'
        )->name('test');
        $this->app->instance('router', $router);
        $route = new RouteMiddlewareServiceProvider($this->app);
        $route->register();

        $routes = $this->app->get('prometheus.routes.paths');
        $this->assertEquals([ 'Test@getTest' => 'test/{id}/test'], $routes);
    }

    public function testServiceProviderEmptyRoutes() : void
    {
        /* @var \Illuminate\Support\Facades\Route $router */
        $router = $this->app['router'];

        $this->app['router'] = $router;
        $this->app->instance('router', $router);
        $route = new RouteMiddlewareServiceProvider($this->app);
        $route->register();

        $routes = $this->app->get('prometheus.routes.paths');
        $this->assertEquals([], $routes);
    }
}
