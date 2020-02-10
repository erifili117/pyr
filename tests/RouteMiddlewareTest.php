<?php

declare(strict_types = 1);

namespace Beat\Pyr;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\TestCase;
use Prometheus\Histogram;

class RouteMiddlewareTest extends TestCase
{
    public function testMiddleware()
    {
        Config::set('prometheus.route_middleware_export_path_uri', false);

        $value = null;
        $labels = null;
        $observe = function (float $time, array $data) use (&$value, &$labels) {
            $value = $time;
            $labels = $data;
        };
        $histogram = \Mockery::mock(Histogram::class);
        $histogram->shouldReceive('observe')->andReturnUsing($observe);

        $prometheus = \Mockery::mock(PrometheusExporter::class);
        $prometheus->shouldReceive('getOrRegisterHistogram')->andReturn($histogram);
        app()['prometheus'] = $prometheus;

        $request = new Request();
        $expectedResponse = new Response();
        $next = function (Request $request) use ($expectedResponse) {
            return $expectedResponse;
        };
        $middleware = new RouteMiddleware();
        $actualResponse = $middleware->handle($request, $next);

        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertGreaterThan(0, $value);
        $this->assertSame(['GET', '/', 200], $labels);
    }

    public function testMiddlewareWithPath()
    {
        Config::set('prometheus.route_middleware_export_path_uri', true);
        $this->app['prometheus.routes.paths'] = [ 'App\Http\Controllers\Test@getTest' => '/test/{id}/test'];

        $value = null;
        $labels = null;
        $observe = function (float $time, array $data) use (&$value, &$labels) {
            $value = $time;
            $labels = $data;
        };

        $histogram = \Mockery::mock(Histogram::class);
        $histogram->shouldReceive('observe')
            ->andReturnUsing($observe);

        $prometheus = \Mockery::mock(PrometheusExporter::class);
        $prometheus->shouldReceive('getOrRegisterHistogram')->andReturn($histogram);
        $this->app['prometheus'] = $prometheus;

        /* @var \Illuminate\Support\Facades\Route $router */
        $router = $this->app['router'];

        $router->get(
            '/test/{id}/test', 'App\Http\Controllers\Test@getTest'
        );

        $this->app->instance('router', $router);
        $route = new RouteMiddlewareServiceProvider($this->app);
        $route->register();

        $request = Request::create('/test/1/test', 'GET', ['id' => 1]);
        $request->setRouteResolver(function() {
            return [
                1,
                [
                    "uses" => "App\Http\Controllers\Test@getTest",
                    "id" => "1"
                ]
            ];
        });

        $expectedResponse = new Response();
        $next = function (Request $request) use ($expectedResponse) {
            return $expectedResponse;
        };

        $middleware = new RouteMiddleware();
        $actualResponse = $middleware->handle($request, $next);

        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertGreaterThan(0, $value);
        $this->assertSame(['GET', 'test/{id}/test', 200], $labels);
    }
}