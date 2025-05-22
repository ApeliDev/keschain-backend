<?php
namespace App\Services\Referral;

use App\Config\Database;
use App\Models\Referral;
use App\Models\ReferralEarning;
use App\Services\Payment\TransactionService;

class CommissionService {
    public function processReferralCommission(int $referredUserId, float $amount, string $paymentType) {
        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();
            
            $referralModel = new Referral();
            $referralChain = $referralModel->getReferralChain($referredUserId, 5);
            
            foreach ($referralChain as $referral) {
                $commissionRate = $this->getCommissionRate($referral['level']);
                $commissionAmount = $amount * ($commissionRate / 100);
                
                if ($commissionAmount > 0) {
                    $this->awardCommission(
                        $referral['referrer_id'],
                        $referredUserId,
                        $referral['id'],
                        $referral['level'],
                        $commissionAmount,
                        $paymentType
                    );
                }
            }
            
            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
    
    private function getCommissionRate(int $level): float {
        // Get commission rates from settings or fixed values
        $rates = [
            1 => 30, // Level 1: 30%
            2 => 15, // Level 2: 15%
            3 => 10, // Level 3: 10%
            4 => 5,  // Level 4: 5%
            5 => 2   // Level 5: 2%
        ];
        
        return $rates[$level] ?? 0;
    }
    
    private function awardCommission(
        int $referrerId, 
        int $referredId, 
        int $referralId, 
        int $level, 
        float $amount,
        string $source
    ): void {
        $db = Database::getInstance();
        
        // Record referral earning
        $earningId = (new ReferralEarning())->create([
            'user_id' => $referrerId,
            'referral_id' => $referralId,
            'referred_user_id' => $referredId,
            'level' => $level,
            'amount' => $amount,
            'source' => $source,
            'status' => 'approved'
        ]);
        
        // Record transaction
        (new TransactionService())->recordTransaction(
            $referrerId,
            $amount,
            'referral_earning',
            [
                'referral_id' => $referralId,
                'referred_user_id' => $referredId,
                'level' => $level,
                'source' => $source
            ]
        );
    }
}