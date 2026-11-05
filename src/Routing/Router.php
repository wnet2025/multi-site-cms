<?php
declare(strict_types=1);
namespace MultiSiteCms\Routing;
use MultiSiteCms\Exceptions\HttpException;
use MultiSiteCms\Http\Request;
final class Router
{
    private array $routes = [];
    public function add(string $method, string $path, callable $handler): void { $this->routes[] = [strtoupper($method), $path, $handler]; }
    public function dispatch(Request $request): mixed
    {
        $pathMatch = false;
        foreach ($this->routes as [$method, $path, $handler]) { if ($path === $request->path()) { $pathMatch = true; if ($method === $request->method()) { return $handler($request); } } }
        throw new HttpException($pathMatch ? 'Method not allowed.' : 'Not found.', $pathMatch ? 405 : 404);
    }
}

