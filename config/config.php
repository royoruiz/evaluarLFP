<?php

define('BASE_PATH', dirname(__DIR__));
define('VIEW_PATH', BASE_PATH . '/app/views');

$defaults = [
    'DB_HOST' => getenv('DB_HOST') ?: '127.0.0.1',
    'DB_PORT' => getenv('DB_PORT') ?: '3306',
    'DB_NAME' => getenv('DB_NAME') ?: 'mvc_app',
    'DB_USER' => getenv('DB_USER') ?: 'root',
    'DB_PASS' => getenv('DB_PASS') ?: '',
    'DB_CHARSET' => getenv('DB_CHARSET') ?: 'utf8mb4',
    'BASE_URL' => getenv('BASE_URL') ?: '/',
];

$localConfigFile = __DIR__ . '/config.local.php';
if (file_exists($localConfigFile)) {
    $localValues = require $localConfigFile;

    if (is_array($localValues)) {
        $defaults = array_merge($defaults, $localValues);
    }
}

foreach ($defaults as $constant => $value) {
    if (!defined($constant)) {
        define($constant, $value);
    }
}
