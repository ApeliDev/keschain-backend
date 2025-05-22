<?php
namespace App\Services\Auth;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Referral;
use App\Services\Payment\MpesaService;

class RegistrationService {
    public function registerUser(array $data): int {
        $db = \App\Config\Database::getInstance();
        
        try {
            $db->beginTransaction();
            
            // Create user
            $userModel = new User();
            $userId = $userModel->create($data);
            
            // Create wallet
            $wallet = new Wallet();
            $wallet->create(['user_id' => $userId]);
            
            // Process referral if exists
            if (!empty($data['referred_by'])) {
                $referral = new Referral();
                $referral->create([
                    'referrer_id' => $data['referred_by'],
                    'referred_id' => $userId,
                    'level' => 1
                ]);
            }
            
            // Process registration payment via M-Pesa
            if (getenv('REGISTRATION_FEE_REQUIRED') === 'true') {
                $mpesa = new MpesaService();
                $paymentResponse = $mpesa->processRegistrationPayment($data['phone'], getenv('REGISTRATION_FEE'));
                
                if (!$paymentResponse['success']) {
                    throw new \Exception('Payment processing failed');
                }
            }
            
            $db->commit();
            return $userId;
            
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}