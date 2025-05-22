<?php
require_once __DIR__ . '/../vendor/autoload.php';

spl_autoload_register(function ($className) {
    $baseDir = __DIR__ . '/../app/';
    $className = str_replace('\\', '/', $className);
    $file = $baseDir . $className . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});