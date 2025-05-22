<?php
namespace App\Models;

use App\Config\Database;

class Payment extends BaseModel {
    protected $table = 'pyr_payments';
    
    public function create(array $data): int {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("
            INSERT INTO {$this->table} 
            (user_id, transaction_id, amount, currency, payment_method, payment_type, 
             status, mpesa_receipt, phone_number, description, metadata)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['user_id'] ?? null,
            $data['transaction_id'],
            $data['amount'],
            $data['currency'] ?? 'KES',
            $data['payment_method'],
            $data['payment_type'],
            $data['status'] ?? 'pending',
            $data['mpesa_receipt'] ?? null,
            $data['phone_number'] ?? null,
            $data['description'] ?? '',
            json_encode($data['metadata'] ?? [])
        ]);
        
        return $db->lastInsertId();
    }
    
    public function findByTransactionId(string $transactionId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM {$this->table} WHERE transaction_id = ?");
        $stmt->execute([$transactionId]);
        return $stmt->fetch();
    }
    
    public function findByMpesaRequestId(string $merchantRequestId, string $checkoutRequestId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM {$this->table} 
            WHERE JSON_EXTRACT(metadata, '$.mpesa_request.MerchantRequestID') = ? 
            AND JSON_EXTRACT(metadata, '$.mpesa_request.CheckoutRequestID') = ?
        ");
        $stmt->execute([$merchantRequestId, $checkoutRequestId]);
        return $stmt->fetch();
    }
    
    public function updateStatus(int $paymentId, string $status, ?string $mpesaReceipt = null): bool {
        $updateData = ['status' => $status];
        
        if ($status === 'completed') {
            $updateData['completed_at'] = date('Y-m-d H:i:s');
        }
        
        if ($mpesaReceipt) {
            $updateData['mpesa_receipt'] = $mpesaReceipt;
        }
        
        return $this->update($paymentId, $updateData);
    }
    
    public function getUserPayments(int $userId, int $limit = 10) {
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