<?php
namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\User;
use App\Validators\Auth\LoginValidator;
use App\Services\Auth\LoginService;
use App\Helpers\SecurityHelper;

class LoginController extends BaseController {
    public function login() {
        try {
            $validator = new LoginValidator();
            $data = $validator->validate($_POST);
            
            $user = (new User())->findByPhone($data['phone']);
            
            if (!$user) {
                return $this->jsonResponse(['error' => 'Invalid phone number or password'], 401);
            }
            
            if (!SecurityHelper::verifyPassword($data['password'], $user['password'])) {
                return $this->jsonResponse(['error' => 'Invalid phone number or password'], 401);
            }
            
            if ($user['account_status'] !== 'active') {
                return $this->jsonResponse(['error' => 'Account is not active. Please contact support.'], 403);
            }
            
            $tokens = (new LoginService())->generateTokens($user['id']);
            
            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'access_token' => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'],
                    'user' => [
                        'id' => $user['id'],
                        'phone' => $user['phone'],
                        'first_name' => $user['first_name'],
                        'last_name' => $user['last_name']
                    ]
                ]
            ]);
            
        } catch (\App\Exceptions\ValidationException $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => 'Login failed. Please try again.'], 500);
        }
    }
}