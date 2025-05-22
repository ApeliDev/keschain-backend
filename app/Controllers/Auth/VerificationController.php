<?php
namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\User;
use App\Services\Auth\VerificationService;
use App\Services\Notification\SMSService;

class VerificationController extends BaseController {
    public function verifyPhone() {
        try {
            $data = $this->getJsonInput();
            
            if (empty($data['phone']) || empty($data['code'])) {
                throw new \Exception('Phone and verification code are required');
            }
            
            $user = (new User())->findByPhone($data['phone']);
            if (!$user) {
                throw new \Exception('User not found');
            }
            
            if ((new VerificationService())->verifyPhone($user['id'], $data['code'])) {
                // Send welcome SMS if this is the first verification
                if ($user['account_status'] === 'pending') {
                    (new User())->update($user['id'], [
                        'account_status' => 'active'
                    ]);
                    
                    (new SMSService())->sendSMS(
                        $user['phone'],
                        "Welcome to Keschain! Your account is now active. Your referral code is {$user['referral_code']}. Start inviting friends to earn more."
                    );
                }
                
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Phone number verified successfully'
                ]);
            }
            
            throw new \Exception('Invalid verification code');
            
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }
    
    public function resendCode() {
        try {
            $data = $this->getJsonInput();
            
            if (empty($data['phone'])) {
                throw new \Exception('Phone number is required');
            }
            
            $user = (new User())->findByPhone($data['phone']);
            if (!$user) {
                throw new \Exception('User not found');
            }
            
            $verificationCode = (new VerificationService())->generateNewCode($user['id']);
            
            // Send SMS with new code
            (new SMSService())->sendVerificationCode($user['phone'], $verificationCode);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Verification code resent'
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}