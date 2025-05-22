<?php
namespace App\Validators\Auth;

use App\Validators\BaseValidator;
use App\Helpers\PhoneHelper;

class RegisterValidator extends BaseValidator {
    public function validate(array $data): array {
        $rules = [
            'phone' => [
                'required' => true,
                'type' => 'phone',
                'min_length' => 10,
                'max_length' => 15
            ],
            'first_name' => [
                'required' => true,
                'min_length' => 2,
                'max_length' => 50
            ],
            'last_name' => [
                'required' => true,
                'min_length' => 2,
                'max_length' => 50
            ],
            'password' => [
                'required' => true,
                'min_length' => 8,
                'contains' => ['uppercase', 'lowercase', 'number']
            ],
            'referral_code' => [
                'required' => false,
                'min_length' => 8,
                'max_length' => 8
            ]
        ];
        
        // Standard validation
        $validated = $this->validateData($data, $rules);
        
        // Format phone number
        $validated['phone'] = PhoneHelper::format($validated['phone']);
        
        // Generate username if not provided
        $validated['username'] = $this->generateUsername(
            $validated['first_name'], 
            $validated['last_name']
        );
        
        return $validated;
    }
    
    private function generateUsername(string $firstName, string $lastName): string {
        $base = strtolower(substr($firstName, 0, 1) . strtolower($lastName));
        $username = preg_replace('/[^a-z0-9]/', '', $base);
        $suffix = '';
        
        // Check if username exists and add suffix if needed
        $db = \App\Config\Database::getInstance();
        $stmt = $db->prepare("SELECT COUNT(*) FROM pyr_users WHERE username = ?");
        
        do {
            $testUsername = $username . $suffix;
            $stmt->execute([$testUsername]);
            $exists = $stmt->fetchColumn();
            
            if ($exists) {
                $suffix = $suffix ? $suffix + 1 : 1;
            }
        } while ($exists);
        
        return $testUsername;
    }
}