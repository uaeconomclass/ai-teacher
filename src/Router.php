<?php
declare(strict_types=1);

namespace App;

use App\Support\Response;

final class Router
{
    /** @var array<string, array<string, array{0: class-string, 1: string}>> */
    private array $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
    ];

    /**
     * @param array{0: class-string, 1: string} $handler
     */
    public function get(string $path, array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    /**
     * @param array{0: class-string, 1: string} $handler
     */
    public function post(string $path, array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $method = strtoupper($method);

        $handler = $this->routes[$method][$path] ?? null;
        if ($handler === null) {
            Response::json(['error' => 'Route not found'], 404);
            return;
        }

        [$class, $action] = $handler;
        $controller = new $class();
        $controller->{$action}();
    }
}
