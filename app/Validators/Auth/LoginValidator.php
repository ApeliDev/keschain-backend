<?php
namespace App\Validators\Auth;

use App\Validators\BaseValidator;
use App\Helpers\PhoneHelper;

class LoginValidator extends BaseValidator {
    public function validate(array $data): array {
        $rules = [
            'phone' => [
                'required' => true,
                'type' => 'phone',
                'min_length' => 10,
                'max_length' => 15
            ],
            'password' => [
                'required' => true
            ]
        ];
        
        $validated = $this->validateData($data, $rules);
        $validated['phone'] = PhoneHelper::format($validated['phone']);
        return $validated;
    }
}