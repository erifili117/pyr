<?php

declare(strict_types = 1);

namespace Beat\Pyr;

use Closure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Prometheus\Histogram;

class RouteMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next) : Response
    {
        $start = microtime(true);
        /** @var Response $response */
        $response = $next($request);
        $duration = microtime(true) - $start;
        /** @var PrometheusExporter $exporter */
        $exporter = app('prometheus');
        $histogram = $exporter->getOrRegisterHistogram(
            'response_time_seconds',
            'It observes response time.',
            [
                'method',
                'route',
                'status_code',
            ]
        );

        $path = $request->getPathInfo();
        if(config('prometheus.route_middleware_export_path_uri')){
            $path = $this->getUri($request);
        }

        /** @var  Histogram $histogram */
        $histogram->observe(
            $duration,
            [
                $request->method(),
                $path,
                $response->getStatusCode(),
            ]
        );

        return $response;
    }

    /**
     * Gets the uri of the current route `/v1/test/{id}`
     * without the variables replaced `/v1/test/45`.
     *
     * @param Request $request
     *
     * @return string The route URI
     */
    public function getUri(Request $request) : string
    {
        $routes = app('prometheus.routes.paths');
        $uses = $request->route()[1]['uses'] ?? '';
        foreach ($routes as $routeUses => $routeUri) {
            if (!empty($uses) && !empty($routeUri) && $routeUses === $uses) {
                return $routeUri;
            }
        }

        return '';
    }
}
