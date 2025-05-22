<?php
namespace App\Services\Auth;

use App\Config\Database;
use App\Models\User;
use App\Helpers\SecurityHelper;

class LoginService {
    public function generateTokens(int $userId): array {
        return [
            'access_token' => AuthService::generateToken($userId, 'access'),
            'refresh_token' => AuthService::generateToken($userId, 'refresh')
        ];
    }
    
    public function recordLoginAttempt(int $userId, bool $success): void {
        $db = Database::getInstance();
        
        $updateFields = [
            'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'last_login' => date('Y-m-d H:i:s')
        ];
        
        if ($success) {
            $updateFields['login_attempts'] = 0;
        } else {
            $updateFields['login_attempts'] = new \PDOExpr('login_attempts + 1');
        }
        
        (new User())->update($userId, $updateFields);
    }
    
    public function checkAccountLock(int $userId): bool {
        $user = (new User())->findById($userId);
        
        // If account has 5 or more failed attempts, lock for 30 minutes
        if ($user['login_attempts'] >= 5) {
            $lastAttempt = strtotime($user['last_login']);
            if (time() - $lastAttempt < 1800) { // 30 minutes
                return true;
            } else {
                // Reset attempts if lock period has passed
                $this->recordLoginAttempt($userId, true);
                return false;
            }
        }
        
        return false;
    }
}