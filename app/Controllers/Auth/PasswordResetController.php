<?php
namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\User;
use App\Services\Auth\PasswordService;
use App\Services\Notification\SMSService;

class PasswordResetController extends BaseController {
    public function requestReset() {
        try {
            $data = $this->getJsonInput();
            
            if (empty($data['phone'])) {
                throw new \Exception('Phone number is required');
            }
            
            $user = (new User())->findByPhone($data['phone']);
            if (!$user) {
                throw new \Exception('User not found');
            }
            
            $resetToken = (new PasswordService())->createPasswordResetToken($user['id']);
            
            // Send SMS with reset link
            $resetLink = getenv('APP_URL') . '/reset-password?token=' . $resetToken;
            (new SMSService())->sendSMS(
                $user['phone'],
                "Keschain password reset. Use this link to reset your password: $resetLink"
            );
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Password reset instructions sent'
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }
    
    public function resetPassword() {
        try {
            $data = $this->getJsonInput();
            
            if (empty($data['token']) || empty($data['new_password'])) {
                throw new \Exception('Token and new password are required');
            }
            
            if ((new PasswordService())->resetPassword($data['token'], $data['new_password'])) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Password reset successfully'
                ]);
            }
            
            throw new \Exception('Invalid or expired token');
            
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}