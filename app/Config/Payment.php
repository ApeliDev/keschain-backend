<?php
namespace App\Config;

class Payment {
    public static function getWithdrawalFees(): array {
        return [
            'mpesa' => [
                'ranges' => [
                    ['min' => 0, 'max' => 100, 'fee' => 7],
                    ['min' => 101, 'max' => 500, 'fee' => 13],
                    // ... other ranges
                ],
                'max_amount' => 70000
            ],
            'bank' => [
                'percentage' => 5,
                'min_fee' => 100,
                'max_amount' => 100000
            ],
            'airtime' => [
                'fixed_fee' => 0,
                'max_amount' => 5000
            ]
        ];
    }
    
    public static function getCommissionRates(): array {
        return [
            1 => 30, // Level 1: 30%
            2 => 15, // Level 2: 15%
            3 => 10, // Level 3: 10%
            4 => 5,  // Level 4: 5%
            5 => 2   // Level 5: 2%
        ];
    }
    
    public static function getTaskRewardRange(): array {
        return [
            'min' => 5.00,
            'max' => 100.00
        ];
    }
    
    public static function getRegistrationFee(): float {
        return (float) (getenv('REGISTRATION_FEE') ?: 50.00);
    }
}