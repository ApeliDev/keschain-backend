<?php
namespace App\Services\Notification;

use App\Config\Database;
use App\Models\SMSLog;
use App\Helpers\PhoneHelper;

class SMSService {
    private $apiKey;
    private $senderId;
    
    public function __construct() {
        $this->apiKey = getenv('SMS_API_KEY');
        $this->senderId = getenv('SMS_SENDER_ID');
    }
    
    public function sendSMS(string $phone, string $message): bool {
        $phone = PhoneHelper::format($phone);
        
        $logId = $this->logSMS($phone, $message, 'notification');
        
        // In production, actually send the SMS
        if (getenv('APP_ENV') === 'production') {
            $sent = $this->sendViaHostpinnacle($phone, $message);
        } else {
            $sent = true; // Skip sending in dev/test
        }
        
        $this->updateSMSLog($logId, $sent ? 'sent' : 'failed');
        return $sent;
    }
    
    public function sendVerificationCode(string $phone, string $code): bool {
        $message = "Your Keschain verification code is: $code";
        return $this->sendSMS($phone, $message);
    }
    
    private function sendViaHostpinnacle(string $phone, string $message): bool {
        $url = "https://api.hostpinnacle.co.ke/SendSMS";
        
        $data = [
            'apiKey' => $this->apiKey,
            'senderId' => $this->senderId,
            'message' => $message,
            'phone' => $phone
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        return $result['success'] ?? false;
    }
    
    private function logSMS(string $phone, string $message, string $type): int {
        return (new SMSLog())->create([
            'recipient' => $phone,
            'message' => $message,
            'sms_type' => $type,
            'status' => 'pending'
        ]);
    }
    
    private function updateSMSLog(int $logId, string $status): void {
        (new SMSLog())->update($logId, [
            'status' => $status,
            'response' => $status === 'sent' ? 'Delivered to gateway' : 'Failed to send'
        ]);
    }
}