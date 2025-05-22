<?php
namespace App\Models;

use App\Config\Database;

class ReferralEarning extends BaseModel {
    protected $table = 'pyr_referral_earnings';
    
    public function create(array $data): int {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("
            INSERT INTO {$this->table} 
            (user_id, referral_id, referred_user_id, level, amount, source, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['user_id'],
            $data['referral_id'],
            $data['referred_user_id'] ?? null,
            $data['level'],
            $data['amount'],
            $data['source'],
            $data['status'] ?? 'pending'
        ]);
        
        return $db->lastInsertId();
    }
    
    public function getUserEarnings(int $userId, int $limit = 10): array {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("
            SELECT e.*, u.username as referred_username, u.phone as referred_phone
            FROM {$this->table} e
            LEFT JOIN pyr_users u ON e.referred_user_id = u.id
            WHERE e.user_id = ?
            ORDER BY e.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        
        return $stmt->fetchAll();
    }
    
    public function getEarningsByReferral(int $referralId): array {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("
            SELECT * FROM {$this->table} 
            WHERE referral_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$referralId]);
        
        return $stmt->fetchAll();
    }
}