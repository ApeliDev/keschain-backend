<?php
namespace App\Services\Auth;

use App\Config\Database;
use App\Models\User;

class VerificationService {
    public static function generateVerificationCode(): string {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    public function verifyPhone(int $userId, string $code): bool {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("
            SELECT id FROM pyr_users 
            WHERE id = ? AND verification_code = ? 
            AND verification_expires > NOW()
        ");
        $stmt->execute([$userId, $code]);
        
        if ($stmt->fetch()) {
            // Update user as verified
            $stmt = $db->prepare("
                UPDATE pyr_users 
                SET phone_verified_at = NOW(), 
                    verification_code = NULL,
                    verification_expires = NULL,
                    account_status = 'active'
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            
            return true;
        }
        
        return false;
    }
    
    public function generateNewCode(int $userId): string {
        $db = Database::getInstance();
        
        $newCode = self::generateVerificationCode();
        $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        $stmt = $db->prepare("
            UPDATE pyr_users 
            SET verification_code = ?,
                verification_expires = ?
            WHERE id = ?
        ");
        $stmt->execute([$newCode, $expires, $userId]);
        
        return $newCode;
    }
}