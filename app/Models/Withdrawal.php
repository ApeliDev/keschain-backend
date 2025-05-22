<?php
namespace App\Models;

use App\Config\Database;

class Withdrawal extends BaseModel {
    protected $table = 'pyr_withdrawals';
    
    public function create(array $data): int {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("
            INSERT INTO {$this->table} 
            (user_id, amount, fee, net_amount, payment_method, account_details, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['user_id'],
            $data['amount'],
            $data['fee'] ?? 0.00,
            $data['net_amount'] ?? $data['amount'] - ($data['fee'] ?? 0.00),
            $data['payment_method'],
            $data['account_details'],
            $data['status'] ?? 'pending'
        ]);
        
        return $db->lastInsertId();
    }
    
    public function getPendingWithdrawals(int $limit = 50) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT w.*, u.username, u.phone 
            FROM {$this->table} w
            JOIN pyr_users u ON w.user_id = u.id
            WHERE w.status = 'pending'
            ORDER BY w.created_at ASC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    public function processWithdrawal(int $withdrawalId, int $adminId, bool $approve, ?string $rejectionReason = null): bool {
        $status = $approve ? 'processing' : 'rejected';
        $updateData = [
            'status' => $status,
            'processed_by' => $adminId,
            'processed_at' => date('Y-m-d H:i:s')
        ];
        
        if (!$approve) {
            $updateData['rejection_reason'] = $rejectionReason;
        }
        
        return $this->update($withdrawalId, $updateData);
    }
    
    public function completeWithdrawal(int $withdrawalId, int $paymentId): bool {
        return $this->update($withdrawalId, [
            'status' => 'completed',
            'payment_id' => $paymentId
        ]);
    }
    
    public function getUserWithdrawals(int $userId, int $limit = 10) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM {$this->table} 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
}