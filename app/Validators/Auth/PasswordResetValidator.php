<?php
namespace App\Validators\Auth;

use App\Validators\BaseValidator;

class PasswordResetValidator extends BaseValidator {
    public function validateResetRequest(array $data): array {
        $rules = [
            'phone' => [
                'required' => true,
                'type' => 'phone',
                'min_length' => 10,
                'max_length' => 15
            ]
        ];
        
        return $this->validateData($data, $rules);
    }
    
    public function validateResetPassword(array $data): array {
        $rules = [
            'token' => [
                'required' => true,
                'min_length' => 64,
                'max_length' => 64
            ],
            'new_password' => [
                'required' => true,
                'min_length' => 8,
                'contains' => ['uppercase', 'lowercase', 'number']
            ]
        ];
        
        return $this->validateData($data, $rules);
    }
}