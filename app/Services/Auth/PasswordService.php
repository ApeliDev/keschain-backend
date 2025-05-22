<?php
namespace App\Services\Auth;

use App\Config\Database;
use App\Models\User;
use App\Helpers\SecurityHelper;

class PasswordService {
    public function createPasswordResetToken(int $userId): string {
        $db = Database::getInstance();
        
        // Delete any existing tokens
        $this->deleteUserResetTokens($userId);
        
        // Create new token
        $token = SecurityHelper::generateToken(64);
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $stmt = $db->prepare("
            INSERT INTO pyr_password_resets 
            (user_id, token, expires_at) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$userId, $token, $expires]);
        
        return $token;
    }
    
    public function resetPassword(string $token, string $newPassword): bool {
        $db = Database::getInstance();
        
        // Find valid token
        $stmt = $db->prepare("
            SELECT user_id FROM pyr_password_resets 
            WHERE token = ? AND expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $userId = $stmt->fetchColumn();
        
        if ($userId) {
            // Update password
            $hashedPassword = SecurityHelper::hashPassword($newPassword);
            (new User())->update($userId, ['password' => $hashedPassword]);
            
            // Delete used token
            $this->deleteUserResetTokens($userId);
            
            return true;
        }
        
        return false;
    }
    
    private function deleteUserResetTokens(int $userId): void {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM pyr_password_resets WHERE user_id = ?");
        $stmt->execute([$userId]);
    }
}