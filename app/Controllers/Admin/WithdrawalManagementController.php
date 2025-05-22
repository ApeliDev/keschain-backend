<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Middlewares\AdminMiddleware;

class WithdrawalManagementController extends BaseController {
    public function __construct() {
        $this->registerMiddleware(new AdminMiddleware());
    }
    
    public function getPendingWithdrawals() {
        $limit = $this->getQueryParam('limit', 50);
        $withdrawals = (new \App\Models\Withdrawal())->getPendingWithdrawals($limit);
        
        return $this->jsonResponse([
            'success' => true,
            'data' => $withdrawals
        ]);
    }
    
    public function processWithdrawal($withdrawalId) {
        try {
            $adminId = $this->getAuthUserId();
            $data = $this->getJsonInput();
            
            $approve = $data['action'] === 'approve';
            $reason = $data['reason'] ?? null;
            
            $processed = (new \App\Services\Payment\WithdrawalService())->processWithdrawal(
                $withdrawalId,
                $adminId,
                $approve,
                $reason
            );
            
            if ($processed) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => $approve ? 'Withdrawal approved' : 'Withdrawal rejected'
                ]);
            }
            
            throw new \Exception('Failed to process withdrawal');
            
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }
    
    public function completeWithdrawal($withdrawalId) {
        try {
            $data = $this->getJsonInput();
            
            if (empty($data['transaction_id'])) {
                throw new \Exception('Transaction ID is required');
            }
            
            $completed = (new \App\Services\Payment\WithdrawalService())->completeWithdrawal(
                $withdrawalId,
                $data['transaction_id']
            );
            
            if ($completed) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Withdrawal marked as completed'
                ]);
            }
            
            throw new \Exception('Failed to complete withdrawal');
            
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}