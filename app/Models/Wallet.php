<?php
namespace App\Models;

use App\Config\Database;

class Wallet extends BaseModel {
    protected $table = 'pyr_wallets';
    
    public function create(array $data) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO {$this->table} (user_id) VALUES (?)
        ");
        $stmt->execute([$data['user_id']]);
        return $db->lastInsertId();
    }
    
    public function getByUserId(int $userId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM {$this->table} WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    public function deposit(int $userId, float $amount) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            UPDATE {$this->table} 
            SET balance = balance + ?, total_earned = total_earned + ?
            WHERE user_id = ?
        ");
        return $stmt->execute([$amount, $amount, $userId]);
    }
    
    public function withdraw(int $userId, float $amount) {
        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();
            
            // Check balance first
            $wallet = $this->getByUserId($userId);
            if ($wallet['balance'] < $amount) {
                throw new \Exception('Insufficient balance');
            }
            
            $stmt = $db->prepare("
                UPDATE {$this->table} 
                SET balance = balance - ?, total_withdrawn = total_withdrawn + ?
                WHERE user_id = ?
            ");
            $stmt->execute([$amount, $amount, $userId]);
            
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
    
    public function addBonus(int $userId, float $amount) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            UPDATE {$this->table} 
            SET bonus_balance = bonus_balance + ?
            WHERE user_id = ?
        ");
        return $stmt->execute([$amount, $userId]);
    }
}