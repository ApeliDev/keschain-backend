<?php
namespace App\Validators\Payment;

use App\Validators\BaseValidator;

class PaymentValidator extends BaseValidator {
    public function validateDeposit(array $data): array {
        $rules = [
            'amount' => [
                'required' => true,
                'type' => 'numeric',
                'min_value' => 10,
                'max_value' => 70000
            ],
            'phone' => [
                'required' => true,
                'type' => 'phone',
                'min_length' => 10,
                'max_length' => 15
            ]
        ];
        
        return $this->validateData($data, $rules);
    }
    
    public function validateWithdrawal(array $data): array {
        $rules = [
            'amount' => [
                'required' => true,
                'type' => 'numeric',
                'min_value' => 100,
                'max_value' => 50000
            ],
            'payment_method' => [
                'required' => true,
                'allowed_values' => ['mpesa', 'bank', 'airtime']
            ],
            'account_details' => [
                'required' => true,
                'min_length' => 3,
                'max_length' => 255
            ]
        ];
        
        // Additional validation for M-Pesa
        if (($data['payment_method'] ?? '') === 'mpesa') {
            $rules['phone'] = [
                'required' => true,
                'type' => 'phone',
                'min_length' => 10,
                'max_length' => 15
            ];
        }
        
        // Additional validation for bank
        if (($data['payment_method'] ?? '') === 'bank') {
            $rules['bank_name'] = [
                'required' => true,
                'min_length' => 3,
                'max_length' => 50
            ];
            $rules['account_number'] = [
                'required' => true,
                'min_length' => 5,
                'max_length' => 20
            ];
            $rules['account_name'] = [
                'required' => true,
                'min_length' => 5,
                'max_length' => 100
            ];
        }
        
        return $this->validateData($data, $rules);
    }
}