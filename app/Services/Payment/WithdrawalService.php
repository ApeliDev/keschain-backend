<?php
namespace App\Services\Payment;

use App\Config\Database;
use App\Models\Withdrawal;
use App\Models\Wallet;
use App\Models\Payment;
use App\Services\Notification\SMSService;

class WithdrawalService {
    public function requestWithdrawal(
        int $userId, 
        float $amount, 
        string $paymentMethod, 
        string $accountDetails
    ): int {
        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();
            
            // Calculate fee
            $fee = $this->calculateWithdrawalFee($amount, $paymentMethod);
            $netAmount = $amount - $fee;
            
            // Verify sufficient balance
            $wallet = (new Wallet())->getByUserId($userId);
            if ($wallet['balance'] < $amount) {
                throw new \Exception('Insufficient balance for withdrawal');
            }
            
            // Deduct from wallet immediately
            (new Wallet())->withdraw($userId, $amount);
            
            // Create withdrawal request
            $withdrawalId = (new Withdrawal())->create([
                'user_id' => $userId,
                'amount' => $amount,
                'fee' => $fee,
                'net_amount' => $netAmount,
                'payment_method' => $paymentMethod,
                'account_details' => $accountDetails,
                'status' => 'pending'
            ]);
            
            $db->commit();
            return $withdrawalId;
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
    
    public function processWithdrawal(int $withdrawalId, int $adminId, bool $approve, ?string $rejectionReason = null): bool {
        $db = Database::getInstance();
        $withdrawalModel = new Withdrawal();
        $withdrawal = $withdrawalModel->findById($withdrawalId);
        
        if (!$withdrawal) {
            throw new \Exception('Withdrawal not found');
        }
        
        try {
            $db->beginTransaction();
            
            // Update withdrawal status
            $withdrawalModel->processWithdrawal($withdrawalId, $adminId, $approve, $rejectionReason);
            
            // If rejected, refund to wallet
            if (!$approve) {
                (new Wallet())->deposit($withdrawal['user_id'], $withdrawal['amount']);
                
                // Notify user
                $user = (new \App\Models\User())->findById($withdrawal['user_id']);
                if ($user) {
                    (new SMSService())->sendSMS(
                        $user['phone'],
                        "Your withdrawal of KES {$withdrawal['amount']} was rejected. Reason: {$rejectionReason}. Amount refunded to your wallet."
                    );
                }
            }
            
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
    
    public function completeWithdrawal(int $withdrawalId, string $transactionId): bool {
        $db = Database::getInstance();
        $withdrawalModel = new Withdrawal();
        $withdrawal = $withdrawalModel->findById($withdrawalId);
        
        if (!$withdrawal) {
            throw new \Exception('Withdrawal not found');
        }
        
        try {
            $db->beginTransaction();
            
            // Record payment
            $paymentId = (new Payment())->create([
                'user_id' => $withdrawal['user_id'],
                'transaction_id' => $transactionId,
                'amount' => $withdrawal['net_amount'],
                'payment_method' => $withdrawal['payment_method'],
                'payment_type' => 'withdrawal',
                'status' => 'completed',
                'description' => 'Withdrawal processed'
            ]);
            
            // Mark withdrawal as completed
            $withdrawalModel->completeWithdrawal($withdrawalId, $paymentId);
            
            // Notify user
            $user = (new \App\Models\User())->findById($withdrawal['user_id']);
            if ($user) {
                (new SMSService())->sendSMS(
                    $user['phone'],
                    "Your withdrawal of KES {$withdrawal['amount']} has been processed. Net amount KES {$withdrawal['net_amount']} sent to {$withdrawal['account_details']}. Transaction ID: {$transactionId}"
                );
            }
            
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
    
    private function calculateWithdrawalFee(float $amount, string $paymentMethod): float {
        // Default fee structure
        $fee = 0.0;
        
        if ($paymentMethod === 'mpesa') {
            // M-Pesa fees (Kenya rates)
            if ($amount <= 100) {
                $fee = 7.0;
            } elseif ($amount <= 500) {
                $fee = 13.0;
            } elseif ($amount <= 1000) {
                $fee = 23.0;
            } elseif ($amount <= 1500) {
                $fee = 33.0;
            } elseif ($amount <= 2500) {
                $fee = 53.0;
            } elseif ($amount <= 3500) {
                $fee = 67.0;
            } elseif ($amount <= 5000) {
                $fee = 82.0;
            } elseif ($amount <= 7500) {
                $fee = 110.0;
            } elseif ($amount <= 10000) {
                $fee = 132.0;
            } elseif ($amount <= 15000) {
                $fee = 170.0;
            } elseif ($amount <= 20000) {
                $fee = 195.0;
            } else {
                $fee = 195.0 + (0.1 * ($amount - 20000)); // 10% of amount above 20,000
            }
        } elseif ($paymentMethod === 'bank') {
            $fee = max(100.0, 0.05 * $amount); // 5% or KES 100, whichever is higher
        }
        
        return $fee;
    }
    
    public function getUserWithdrawalHistory(int $userId, int $limit = 10): array {
        return (new Withdrawal())->getUserWithdrawals($userId, $limit);
    }
}