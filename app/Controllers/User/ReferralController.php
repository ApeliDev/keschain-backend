<?php
namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\Referral;
use App\Middlewares\AuthMiddleware;

class ReferralController extends BaseController {
    public function __construct() {
        $this->registerMiddleware(new AuthMiddleware());
    }
    
    public function getReferralTree() {
        $userId = $this->getAuthUserId();
        $levels = $this->getQueryParam('levels', 5);
        
        $referralTree = (new Referral())->getReferralTree($userId, $levels);
        
        return $this->jsonResponse([
            'success' => true,
            'data' => $referralTree
        ]);
    }
    
    public function getReferralStats() {
        $userId = $this->getAuthUserId();
        $referralModel = new Referral();
        
        $directReferrals = $referralModel->getDirectReferrals($userId);
        $earnings = $referralModel->calculateEarnings($userId);
        
        $totalEarnings = array_reduce($earnings, function($carry, $item) {
            return $carry + $item['total_earned'];
        }, 0);
        
        return $this->jsonResponse([
            'success' => true,
            'data' => [
                'direct_referrals_count' => count($directReferrals),
                'total_referrals_count' => $this->countTotalReferrals($userId),
                'referral_earnings' => $earnings,
                'total_earnings' => $totalEarnings,
                'referral_code' => $this->getUserReferralCode($userId)
            ]
        ]);
    }
    
    private function countTotalReferrals(int $userId): int {
        $db = \App\Config\Database::getInstance();
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM pyr_referrals 
            WHERE referrer_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
    
    private function getUserReferralCode(int $userId): string {
        $db = \App\Config\Database::getInstance();
        $stmt = $db->prepare("
            SELECT referral_code FROM pyr_users 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
}