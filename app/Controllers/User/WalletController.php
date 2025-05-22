<?php
namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Middlewares\AuthMiddleware;
use App\Validators\Payment\PaymentValidator;

class WalletController extends BaseController {
    public function __construct() {
        $this->registerMiddleware(new AuthMiddleware());
    }
    
    public function getBalance() {
        $userId = $this->getAuthUserId();
        $wallet = (new \App\Models\Wallet())->getByUserId($userId);
        
        return $this->jsonResponse([
            'success' => true,
            'data' => [
                'balance' => $wallet['balance'],
                'bonus_balance' => $wallet['bonus_balance'],
                'total_earned' => $wallet['total_earned'],
                'total_withdrawn' => $wallet['total_withdrawn'],
                'currency' => $wallet['currency']
            ]
        ]);
    }
    
    public function deposit() {
        try {
            $userId = $this->getAuthUserId();
            $data = $this->getJsonInput();
            
            $validator = new PaymentValidator();
            $validated = $validator->validateDeposit($data);
            
            $mpesa = new \App\Services\Payment\MpesaService();
            $response = $mpesa->processDeposit($validated['phone'], $validated['amount']);
            
            return $this->jsonResponse([
                'success' => $response['success'],
                'message' => $response['message'],
                'data' => [
                    'transaction_id' => $response['transaction_id']
                ]
            ]);
            
        } catch (\App\Exceptions\ValidationException $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    public function requestWithdrawal() {
        try {
            $userId = $this->getAuthUserId();
            $data = $this->getJsonInput();
            
            $validator = new PaymentValidator();
            $validated = $validator->validateWithdrawal($data);
            
            // For M-Pesa, use phone as account details
            if ($validated['payment_method'] === 'mpesa') {
                $accountDetails = $validated['phone'];
            } elseif ($validated['payment_method'] === 'bank') {
                $accountDetails = "{$validated['bank_name']} - {$validated['account_number']} ({$validated['account_name']})";
            } else {
                $accountDetails = $validated['account_details'];
            }
            
            $withdrawalId = (new \App\Services\Payment\WithdrawalService())->requestWithdrawal(
                $userId,
                $validated['amount'],
                $validated['payment_method'],
                $accountDetails
            );
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Withdrawal request submitted',
                'data' => [
                    'withdrawal_id' => $withdrawalId
                ]
            ]);
            
        } catch (\App\Exceptions\ValidationException $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    public function getPaymentHistory() {
        $userId = $this->getAuthUserId();
        $limit = $this->getQueryParam('limit', 10);
        
        $payments = (new \App\Services\Payment\PaymentService())->getUserPaymentHistory($userId, $limit);
        $withdrawals = (new \App\Services\Payment\WithdrawalService())->getUserWithdrawalHistory($userId, $limit);
        
        return $this->jsonResponse([
            'success' => true,
            'data' => [
                'deposits' => $payments,
                'withdrawals' => $withdrawals
            ]
        ]);
    }
}