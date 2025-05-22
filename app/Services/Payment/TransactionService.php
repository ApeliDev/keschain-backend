<?php
namespace App\Services\Payment;

use App\Config\Database;
use App\Models\Wallet;
use App\Models\Payment;
use App\Models\ReferralEarning;

class TransactionService {
    public function recordTransaction(int $userId, float $amount, string $type, array $metadata = []) {
        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();
            
            // Create payment record
            $paymentId = (new Payment())->create([
                'user_id' => $userId,
                'transaction_id' => $this->generateTransactionId($type),
                'amount' => $amount,
                'payment_method' => 'system',
                'payment_type' => $type,
                'status' => 'completed',
                'description' => $this->getDescriptionForType($type),
                'metadata' => $metadata
            ]);
            
            // Update wallet based on transaction type
            $wallet = new Wallet();
            switch ($type) {
                case 'task_reward':
                case 'referral_earning':
                    $wallet->deposit($userId, $amount);
                    break;
                case 'withdrawal':
                    $wallet->withdraw($userId, $amount);
                    break;
                case 'bonus':
                    $wallet->addBonus($userId, $amount);
                    break;
            }
            
            $db->commit();
            return $paymentId;
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
    
    private function generateTransactionId(string $type): string {
        $prefix = match($type) {
            'task_reward' => 'TSK',
            'referral_earning' => 'REF',
            'withdrawal' => 'WTH',
            'bonus' => 'BNS',
            default => 'TRN'
        };
        
        return $prefix . time() . rand(100, 999);
    }
    
    private function getDescriptionForType(string $type): string {
        return match($type) {
            'task_reward' => 'Task completion reward',
            'referral_earning' => 'Referral commission',
            'withdrawal' => 'Withdrawal processing',
            'bonus' => 'System bonus',
            default => 'System transaction'
        };
    }
    
    public function getUserTransactionHistory(int $userId, int $limit = 10): array {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("
            SELECT * FROM pyr_payments 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        
        return $stmt->fetchAll();
    }
}