<?php
namespace App\Router;

class Router {
    private array $routes = [];
    
    public function add(string $method, string $path, string $handler): void {
        $this->routes[$method][$path] = $handler;
    }
    
    public function match(string $method, string $path): ?array {
        $methodRoutes = $this->routes[$method] ?? [];
        
        foreach ($methodRoutes as $routePath => $handler) {
            if ($this->pathMatches($routePath, $path)) {
                return [
                    'handler' => $handler,
                    'params' => $this->extractParams($routePath, $path)
                ];
            }
        }
        
        return null;
    }
    
    private function pathMatches(string $routePath, string $requestPath): bool {
        $routeParts = explode('/', trim($routePath, '/'));
        $requestParts = explode('/', trim($requestPath, '/'));
        
        if (count($routeParts) !== count($requestParts)) {
            return false;
        }
        
        foreach ($routeParts as $index => $part) {
            if (strpos($part, '{') === 0 && strpos($part, '}') === strlen($part) - 1) {
                continue; // It's a parameter
            }
            
            if ($part !== $requestParts[$index]) {
                return false;
            }
        }
        
        return true;
    }
    
    private function extractParams(string $routePath, string $requestPath): array {
        $params = [];
        $routeParts = explode('/', trim($routePath, '/'));
        $requestParts = explode('/', trim($requestPath, '/'));
        
        foreach ($routeParts as $index => $part) {
            if (strpos($part, '{') === 0 && strpos($part, '}') === strlen($part) - 1) {
                $paramName = substr($part, 1, -1);
                $params[$paramName] = $requestParts[$index];
            }
        }
        
        return $params;
    }
}