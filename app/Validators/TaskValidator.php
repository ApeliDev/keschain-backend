<?php
namespace App\Validators;

use App\Validators\BaseValidator;

class TaskValidator extends BaseValidator {
    public function validateCreate(array $data): array {
        $rules = [
            'category_id' => [
                'required' => true,
                'type' => 'numeric'
            ],
            'title' => [
                'required' => true,
                'min_length' => 5,
                'max_length' => 100
            ],
            'description' => [
                'required' => true,
                'min_length' => 10
            ],
            'reward_amount' => [
                'required' => true,
                'type' => 'numeric',
                'min_value' => 5,
                'max_value' => 100
            ],
            'duration_minutes' => [
                'required' => false,
                'type' => 'numeric',
                'min_value' => 1,
                'max_value' => 1440
            ],
            'verification_type' => [
                'required' => false,
                'allowed_values' => ['automatic', 'manual'],
                'default' => 'manual'
            ],
            'total_available' => [
                'required' => false,
                'type' => 'numeric',
                'min_value' => 1
            ]
        ];
        
        return $this->validateData($data, $rules);
    }
    
    public function validateUpdate(array $data): array {
        $rules = [
            'category_id' => [
                'required' => false,
                'type' => 'numeric'
            ],
            'title' => [
                'required' => false,
                'min_length' => 5,
                'max_length' => 100
            ],
            'description' => [
                'required' => false,
                'min_length' => 10
            ],
            'reward_amount' => [
                'required' => false,
                'type' => 'numeric',
                'min_value' => 5,
                'max_value' => 100
            ],
            'is_active' => [
                'required' => false,
                'type' => 'boolean'
            ],
            'expires_at' => [
                'required' => false,
                'type' => 'datetime'
            ]
        ];
        
        return $this->validateData($data, $rules);
    }
    
    public function validateSubmission(array $data): array {
        $rules = [
            'task_assignment_id' => [
                'required' => true,
                'type' => 'numeric'
            ],
            'proof' => [
                'required' => true,
                'min_length' => 10
            ]
        ];
        
        return $this->validateData($data, $rules);
    }
}