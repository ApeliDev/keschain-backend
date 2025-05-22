<?php
namespace App\Helpers;

class PhoneHelper {
    public static function format(string $phone): string {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Handle Kenyan numbers
        if (strlen($phone) === 9 && substr($phone, 0, 1) === '7') {
            return '254' . $phone;
        }
        
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            return '254' . substr($phone, 1);
        }
        
        // Handle international numbers with +
        if (substr($phone, 0, 1) === '1') {
            return $phone; // US numbers
        }
        
        return $phone;
    }
    
    public static function isKenyanNumber(string $phone): bool {
        $phone = self::format($phone);
        return substr($phone, 0, 3) === '254';
    }
    
    public static function mask(string $phone): string {
        $phone = self::format($phone);
        return substr($phone, 0, 3) . '****' . substr($phone, -3);
    }
}