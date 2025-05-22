<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Admin;
use App\Services\Auth\AuthService;
use App\Validators\Auth\LoginValidator;

class AdminAuthController extends BaseController {
    public function login() {
        try {
            $validator = new LoginValidator();
            $data = $validator->validate($_POST);
            
            $admin = (new Admin())->findByEmail($data['email']);
            
            if (!$admin) {
                return $this->jsonResponse(['error' => 'Invalid credentials'], 401);
            }
            
            if (!password_verify($data['password'], $admin['password'])) {
                return $this->jsonResponse(['error' => 'Invalid credentials'], 401);
            }
            
            if ($admin['status'] !== 'active') {
                return $this->jsonResponse(['error' => 'Account is not active'], 403);
            }
            
            $tokens = [
                'access_token' => AuthService::generateToken($admin['id'], 'access'),
                'refresh_token' => AuthService::generateToken($admin['id'], 'refresh')
            ];
            
            // Update last login
            (new Admin())->update($admin['id'], [
                'last_login' => date('Y-m-d H:i:s'),
                'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
            
            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'access_token' => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'],
                    'admin' => [
                        'id' => $admin['id'],
                        'name' => $admin['name'],
                        'email' => $admin['email'],
                        'role' => $admin['role']
                    ]
                ]
            ]);
            
        } catch (\App\Exceptions\ValidationException $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => 'Login failed'], 500);
        }
    }
    
    public function logout() {
        // Invalidate token (implementation depends on your JWT setup)
        return $this->jsonResponse(['success' => true, 'message' => 'Logged out successfully']);
    }
}