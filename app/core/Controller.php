<?php

class Controller
{
    protected function render(string $view, array $params = []): void
    {
        $viewFile = VIEW_PATH . '/' . $view . '.php';
        if (!file_exists($viewFile)) {
            throw new RuntimeException("La vista {$view} no existe");
        }

        extract($params, EXTR_SKIP);
        $content = function () use ($viewFile, $params) {
            extract($params, EXTR_SKIP);
            include $viewFile;
        };

        include VIEW_PATH . '/layout.php';
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }
}
