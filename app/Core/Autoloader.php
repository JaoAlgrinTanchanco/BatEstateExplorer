<?php

spl_autoload_register(function ($class) {
    // Only handle our App namespace
    if (strpos($class, 'App\\') !== 0) {
        return;
    }

    $baseDir = __DIR__ . '/../';
    $relative = substr($class, strlen('App\\'));
    $path = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';

    if (file_exists($path)) {
        require_once $path;
    }
});


