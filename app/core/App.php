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

        $this->router->add('GET', '/admin', function () {
            $controller = new AdminController();
            $controller->index();
        });

        $this->router->add('POST', '/admin/users/role', function () {
            $controller = new AdminController();
            $controller->updateUserRole();
        });

        $this->router->add('POST', '/admin/ciclos', function () {
            $controller = new AdminController();
            $controller->saveCycle();
        });

        $this->router->add('POST', '/admin/ciclos/eliminar', function () {
            $controller = new AdminController();
            $controller->deleteCycle();
        });

        $this->router->add('POST', '/admin/modulos', function () {
            $controller = new AdminController();
            $controller->saveModule();
        });

        $this->router->add('POST', '/admin/modulos/eliminar', function () {
            $controller = new AdminController();
            $controller->deleteModule();
        });

        $this->router->add('POST', '/admin/resultados', function () {
            $controller = new AdminController();
            $controller->saveLearningOutcome();
        });

        $this->router->add('POST', '/admin/resultados/eliminar', function () {
            $controller = new AdminController();
            $controller->deleteLearningOutcome();
        });

        $this->router->add('POST', '/admin/criterios', function () {
            $controller = new AdminController();
            $controller->saveEvaluationCriterion();
        });

        $this->router->add('POST', '/admin/criterios/eliminar', function () {
            $controller = new AdminController();
            $controller->deleteEvaluationCriterion();
        });

        $this->router->add('GET', '/modulos/nuevo', function () {
            $controller = new UserModuleController();
            $controller->create();
        });

        $this->router->add('POST', '/modulos/nuevo', function () {
            $controller = new UserModuleController();
            $controller->store();
        });

        $this->router->add('GET', '/modulos/configurar', function () {
            $controller = new UserModuleController();
            $controller->configure();
        });

        $this->router->add('POST', '/modulos/configurar', function () {
            $controller = new UserModuleController();
            $controller->saveStep();
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
