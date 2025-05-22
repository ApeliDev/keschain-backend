<?php
namespace App\Services\Payment;

use App\Config\Database;
use App\Models\Payment;
use App\Models\Wallet;
use App\Services\Notification\SMSService;

class PaymentService {
    public function recordPayment(array $data): int {
        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();
            
            // Create payment record
            $paymentId = (new Payment())->create($data);
            
            // If this is a deposit, update wallet
            if ($data['payment_type'] === 'deposit' && $data['status'] === 'completed') {
                (new Wallet())->deposit($data['user_id'], $data['amount']);
            }
            
            // If this is a registration payment, update user status
            if ($data['payment_type'] === 'registration' && $data['status'] === 'completed') {
                $this->handleRegistrationPayment($data);
            }
            
            $db->commit();
            return $paymentId;
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
    
    private function handleRegistrationPayment(array $paymentData): void {
        $user = (new \App\Models\User())->findByPhone($paymentData['phone_number']);
        if ($user) {
            (new \App\Models\User())->update($user['id'], [
                'registration_fee_paid' => true,
                'account_status' => 'active'
            ]);
            
            // Send welcome SMS
            (new SMSService())->sendSMS(
                $user['phone'],
                "Welcome to Keschain! Your registration is complete. Your referral code is {$user['referral_code']}. Start inviting friends to earn more."
            );
        }
    }
    
    public function getPaymentStatus(string $transactionId): array {
        $payment = (new Payment())->findByTransactionId($transactionId);
        
        if (!$payment) {
            throw new \Exception('Payment not found');
        }
        
        return [
            'status' => $payment['status'],
            'amount' => $payment['amount'],
            'currency' => $payment['currency'],
            'payment_method' => $payment['payment_method'],
            'created_at' => $payment['created_at'],
            'completed_at' => $payment['completed_at']
        ];
    }
    
    public function getUserPaymentHistory(int $userId, int $limit = 10): array {
        return (new Payment())->getUserPayments($userId, $limit);
    }
}