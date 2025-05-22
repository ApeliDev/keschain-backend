<?php
namespace App;

use App\Router\Router;

class Application {
    private Router $router;
    
    public function __construct() {
        $this->router = new Router();
        $this->registerRoutes();
    }
    
    private function registerRoutes(): void {
        // Auth routes
        $this->router->add('POST', '/api/v1/auth/register', 'App\Controllers\Auth\RegistrationController@register');
        $this->router->add('POST', '/api/v1/auth/login', 'App\Controllers\Auth\LoginController@login');
        $this->router->add('POST', '/api/v1/auth/verify-phone', 'App\Controllers\Auth\VerificationController@verifyPhone');
        
        // Add more routes as needed...
    }
    
    public function run(): void {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        try {
            $route = $this->router->match($requestMethod, $requestUri);
            
            if ($route) {
                $this->dispatch($route);
            } else {
                ResponseHelper::jsonResponse([
                    'success' => false,
                    'error' => 'Endpoint not found',
                    'code' => 'NOT_FOUND'
                ], 404);
            }
        } catch (\Throwable $e) {
            $handler = new \App\Handlers\ErrorHandler();
            $handler->handleException($e);
        }
    }
    
    private function dispatch(array $route): void {
        [$controllerClass, $method] = explode('@', $route['handler']);
        
        if (!class_exists($controllerClass)) {
            throw new \RuntimeException("Controller $controllerClass not found");
        }
        
        $controller = new $controllerClass();
        
        if (!method_exists($controller, $method)) {
            throw new \RuntimeException("Method $method not found in $controllerClass");
        }
        
        // Get request data
        $requestData = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            
            if (strpos($contentType, 'application/json') !== false) {
                $requestData = json_decode(file_get_contents('php://input'), true) ?? [];
            } else {
                $requestData = $_POST;
            }
        }
        
        // Call the controller method
        $response = $controller->$method($requestData);
        
        if ($response !== null) {
            echo json_encode($response);
        }
    }
}