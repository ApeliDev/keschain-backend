<?php
namespace App\Validators;

class BaseValidator {
    protected function validateData(array $data, array $rules): array {
        $validated = [];
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $isRequired = $rule['required'] ?? false;
            
            // Skip validation if field is not required and empty
            if (!$isRequired && empty($value)) {
                continue;
            }
            
            // Required check
            if ($isRequired && empty($value)) {
                $errors[$field] = "The $field field is required";
                continue;
            }
            
            // Type checks
            if (isset($rule['type'])) {
                switch ($rule['type']) {
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = "Invalid email format";
                        }
                        break;
                    case 'phone':
                        if (!preg_match('/^[0-9+]{10,15}$/', $value)) {
                            $errors[$field] = "Invalid phone number format";
                        }
                        break;
                    case 'number':
                        case 'numeric':
                        if (!is_numeric($value)) {
                            $errors[$field] = "The $field must be a number";
                        }
                        break;
                }
            }
            
            // Length checks
            if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                $errors[$field] = "The $field must be at least {$rule['min_length']} characters";
            }
            
            if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                $errors[$field] = "The $field may not be greater than {$rule['max_length']} characters";
            }
            
            // Value checks
            if (isset($rule['contains'])) {
                foreach ($rule['contains'] as $requirement) {
                    switch ($requirement) {
                        case 'uppercase':
                            if (!preg_match('/[A-Z]/', $value)) {
                                $errors[$field] = "The $field must contain at least one uppercase letter";
                            }
                            break;
                        case 'lowercase':
                            if (!preg_match('/[a-z]/', $value)) {
                                $errors[$field] = "The $field must contain at least one lowercase letter";
                            }
                            break;
                        case 'number':
                            if (!preg_match('/[0-9]/', $value)) {
                                $errors[$field] = "The $field must contain at least one number";
                            }
                            break;
                        case 'special':
                            if (!preg_match('/[^a-zA-Z0-9]/', $value)) {
                                $errors[$field] = "The $field must contain at least one special character";
                            }
                            break;
                    }
                }
            }
            
            // If no errors, add to validated data
            if (!isset($errors[$field])) {
                $validated[$field] = $value;
            }
        }
        
        if (!empty($errors)) {
            throw new \App\Exceptions\ValidationException('Validation failed', $errors);
        }
        
        return $validated;
    }
}