<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config/config.php';

foreach (glob(__DIR__ . '/../app/core/*.php') as $file) {
    require_once $file;
}

foreach (glob(__DIR__ . '/../app/models/*.php') as $file) {
    require_once $file;
}

foreach (glob(__DIR__ . '/../app/controllers/*.php') as $file) {
    require_once $file;
}

try {
    $app = new App();
    $app->run();
} catch (Throwable $exception) {
    http_response_code(500);
    echo '<h1>Error del servidor</h1>';
    echo '<p>' . htmlspecialchars($exception->getMessage()) . '</p>';
}
