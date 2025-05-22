<?php
namespace App\Middlewares;

use App\Models\User;
use App\Helpers\ResponseHelper;

class VerificationMiddleware implements MiddlewareInterface {
    public function handle() {
        // Get user ID from previous middleware
        $userId = $GLOBALS['user_id'] ?? null;
        
        if (!$userId) {
            ResponseHelper::jsonResponse(['error' => 'Unauthorized'], 401);
            exit;
        }
        
        $user = (new User())->findById($userId);
        
        // Check if phone is verified
        if (!$user['phone_verified_at']) {
            ResponseHelper::jsonResponse([
                'error' => 'Phone number not verified',
                'code' => 'PHONE_NOT_VERIFIED'
            ], 403);
            exit;
        }
        
        // Check if registration fee is paid if required
        if (getenv('REGISTRATION_FEE_REQUIRED') === 'true' && !$user['registration_fee_paid']) {
            ResponseHelper::jsonResponse([
                'error' => 'Registration fee not paid',
                'code' => 'FEE_NOT_PAID'
            ], 403);
            exit;
        }
    }
}