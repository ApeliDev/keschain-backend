<?php
namespace App\Middlewares;

use App\Services\Auth\AuthService;
use App\Helpers\ResponseHelper;

class AuthMiddleware implements MiddlewareInterface {
    public function handle() {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            ResponseHelper::jsonResponse(['error' => 'Authorization header missing'], 401);
            exit;
        }
        
        $token = $matches[1];
        
        try {
            $decoded = AuthService::validateToken($token);
            
            if ($decoded['type'] !== 'access') {
                ResponseHelper::jsonResponse(['error' => 'Invalid token type'], 401);
                exit;
            }
            
            // Verify user exists and is active
            $user = (new \App\Models\User())->findById($decoded['sub']);
            if (!$user || $user['account_status'] !== 'active') {
                ResponseHelper::jsonResponse(['error' => 'User account not active'], 403);
                exit;
            }
            
            // Store user ID in request context
            $GLOBALS['user_id'] = $decoded['sub'];
            
        } catch (\Exception $e) {
            ResponseHelper::jsonResponse(['error' => $e->getMessage()], 401);
            exit;
        }
    }
}