<?php
namespace App\Models;

use App\Config\Database;

class Referral extends BaseModel {
    protected $table = 'pyr_referrals';
    
    public function create(array $data) {
        $db = Database::getInstance();
        
        // Check if referral already exists
        $stmt = $db->prepare("SELECT id FROM {$this->table} WHERE referred_id = ?");
        $stmt->execute([$data['referred_id']]);
        if ($stmt->fetch()) {
            throw new \Exception('User already referred');
        }
        
        $stmt = $db->prepare("
            INSERT INTO {$this->table} (referrer_id, referred_id, level)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $data['referrer_id'],
            $data['referred_id'],
            $data['level']
        ]);
        
        return $db->lastInsertId();
    }
    
    public function getReferralTree(int $userId, int $levels = 5) {
        $db = Database::getInstance();
        
        $query = "
            WITH RECURSIVE referral_tree AS (
                SELECT id, referrer_id, referred_id, level, 1 as depth
                FROM {$this->table}
                WHERE referrer_id = ?
                
                UNION ALL
                
                SELECT r.id, r.referrer_id, r.referred_id, r.level, rt.depth + 1
                FROM {$this->table} r
                JOIN referral_tree rt ON r.referrer_id = rt.referred_id
                WHERE rt.depth < ?
            )
            SELECT rt.*, u.username, u.phone, u.first_name, u.last_name, w.balance
            FROM referral_tree rt
            JOIN pyr_users u ON rt.referred_id = u.id
            JOIN pyr_wallets w ON u.id = w.user_id
            ORDER BY rt.depth, rt.level
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$userId, $levels]);
        
        return $stmt->fetchAll();
    }
    
    public function getDirectReferrals(int $userId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT r.*, u.username, u.phone, u.first_name, u.last_name, u.created_at as join_date
            FROM {$this->table} r
            JOIN pyr_users u ON r.referred_id = u.id
            WHERE r.referrer_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public function calculateEarnings(int $userId) {
        $db = Database::getInstance();
        
        // Get commission rates from settings
        $commissionRates = [];
        for ($i = 1; $i <= 5; $i++) {
            $rate = $this->getSetting("referral_level_{$i}_commission");
            if ($rate) {
                $commissionRates[$i] = (float)$rate;
            }
        }
        
        if (empty($commissionRates)) {
            return [];
        }
        
        // Calculate earnings from each level
        $earnings = [];
        foreach ($commissionRates as $level => $rate) {
            $stmt = $db->prepare("
                SELECT COUNT(*) as count, SUM(p.amount) as total
                FROM {$this->table} r
                JOIN pyr_payments p ON r.referred_id = p.user_id
                WHERE r.referrer_id = ? AND r.level = ? 
                AND p.payment_type = 'registration' AND p.status = 'completed'
            ");
            $stmt->execute([$userId, $level]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                $earnings[] = [
                    'level' => $level,
                    'referrals_count' => $result['count'],
                    'commission_rate' => $rate,
                    'total_earned' => $result['total'] * $rate / 100
                ];
            }
        }
        
        return $earnings;
    }
    
    private function getSetting(string $key) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT value FROM pyr_settings WHERE `key` = ?");
        $stmt->execute([$key]);
        return $stmt->fetchColumn();
    }
}