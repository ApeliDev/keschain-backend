<?php
// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Define application constants
define('APP_ROOT', dirname(__DIR__));
define('PUBLIC_ROOT', APP_ROOT . '/public');

// Set default timezone
date_default_timezone_set('Africa/Nairobi');

// Replace the Dotenv section with:
if (file_exists(APP_ROOT . '/.env')) {
    $lines = file(APP_ROOT . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        putenv($line);
    }
}

// Configure CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

// Set content type header
header("Content-Type: application/json; charset=UTF-8");

// Register autoloader
require APP_ROOT . '/bootstrap/autoload.php';

// Initialize database connection
require APP_ROOT . '/app/Config/Database.php';

// Register error handler
set_error_handler([new \App\Handlers\ErrorHandler(), 'handleError']);
set_exception_handler([new \App\Handlers\ErrorHandler(), 'handleException']);

// Start session if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize the application
$app = new \App\Application();
$app->run();