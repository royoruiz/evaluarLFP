<?php

class Router
{
    private array $routes = [];

    public function add(string $method, string $path, callable $handler): void
    {
        $method = strtoupper($method);
        $path = $this->normalizePath($path);
        $this->routes[$method][$path] = $handler;
    }

    public function dispatch(string $method, string $path)
    {
        $method = strtoupper($method);
        $path = $this->normalizePath($path);

        if (isset($this->routes[$method][$path])) {
            return call_user_func($this->routes[$method][$path]);
        }

        http_response_code(404);
        echo 'Página no encontrada';
        return null;
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? $path : rtrim($path, '/');
    }
}
