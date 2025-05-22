<?php
namespace App\Middlewares;

use App\Services\Auth\AuthService;
use App\Helpers\ResponseHelper;
use App\Models\Admin;

class AdminMiddleware implements MiddlewareInterface {
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
            
            // Verify admin exists and is active
            $admin = (new Admin())->findById($decoded['sub']);
            if (!$admin || $admin['status'] !== 'active') {
                ResponseHelper::jsonResponse(['error' => 'Admin account not active'], 403);
                exit;
            }
            
            // Store admin ID in request context
            $GLOBALS['admin_id'] = $decoded['sub'];
            $GLOBALS['admin_role'] = $admin['role'];
            
        } catch (\Exception $e) {
            ResponseHelper::jsonResponse(['error' => $e->getMessage()], 401);
            exit;
        }
    }
}