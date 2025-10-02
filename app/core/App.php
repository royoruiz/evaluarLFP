<?php

class App
{
    private Router $router;

    public function __construct()
    {
        $this->router = new Router();
        $this->registerRoutes();
    }

    public function run(): void
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        if ($base !== '' && $base !== '/') {
            $uri = '/' . ltrim(substr($uri, strlen($base)), '/');
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->router->dispatch($method, $uri);
    }

    private function registerRoutes(): void
    {
        $this->router->add('GET', '/', function () {
            $controller = new HomeController();
            $controller->index();
        });

        $this->router->add('GET', '/login', function () {
            $controller = new AuthController();
            $controller->showLogin();
        });

        $this->router->add('POST', '/login', function () {
            $controller = new AuthController();
            $controller->login();
        });

        $this->router->add('GET', '/registro', function () {
            $controller = new AuthController();
            $controller->showRegister();
        });

        $this->router->add('POST', '/registro', function () {
            $controller = new AuthController();
            $controller->register();
        });

        $this->router->add('POST', '/logout', function () {
            $controller = new AuthController();
            $controller->logout();
        });
    }
}
