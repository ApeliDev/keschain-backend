<?php
namespace App\Models;

use App\Config\Database;
use App\Helpers\SecurityHelper;
use App\Services\Auth\VerificationService;

class User extends BaseModel {
    protected $table = 'pyr_users';
    
    public function create(array $data) {
        $db = Database::getInstance();
        
        // Generate referral code
        $referralCode = $this->generateReferralCode();
        
        $stmt = $db->prepare("
            INSERT INTO {$this->table} 
            (username, email, phone, password, first_name, last_name, referral_code, referred_by, verification_code, verification_expires)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $verificationCode = VerificationService::generateVerificationCode();
        $verificationExpires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        $hashedPassword = SecurityHelper::hashPassword($data['password']);
        
        $stmt->execute([
            $data['username'],
            $data['email'] ?? null,
            $data['phone'],
            $hashedPassword,
            $data['first_name'],
            $data['last_name'],
            $referralCode,
            $data['referred_by'] ?? null,
            $verificationCode,
            $verificationExpires
        ]);
        
        return $db->lastInsertId();
    }
    
    public function findByPhone(string $phone) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM {$this->table} WHERE phone = ?");
        $stmt->execute([$phone]);
        return $stmt->fetch();
    }
    
    public function findByEmail(string $email) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    public function findByReferralCode(string $code) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id FROM {$this->table} WHERE referral_code = ?");
        $stmt->execute([$code]);
        return $stmt->fetchColumn();
    }
    
    private function generateReferralCode(): string {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $characters[rand(0, strlen($characters) - 1)];
            }
            $exists = $this->findByReferralCode($code);
        } while ($exists);
        
        return $code;
    }
    
    // Other user methods...
}