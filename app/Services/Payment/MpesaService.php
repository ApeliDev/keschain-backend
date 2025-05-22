<?php
namespace App\Services\Payment;

use App\Config\Database;
use App\Models\Payment;
use App\Helpers\SecurityHelper;
use App\Services\Notification\SMSService;

class MpesaService {
    private $consumerKey;
    private $consumerSecret;
    private $passkey;
    private $shortcode;
    private $callbackUrl;
    
    public function __construct() {
        $this->consumerKey = getenv('MPESA_CONSUMER_KEY');
        $this->consumerSecret = getenv('MPESA_CONSUMER_SECRET');
        $this->passkey = getenv('MPESA_PASSKEY');
        $this->shortcode = getenv('MPESA_SHORTCODE');
        $this->callbackUrl = getenv('MPESA_CALLBACK_URL');
    }
    
    public function processRegistrationPayment(string $phone, float $amount): array {
        $phone = $this->formatPhoneNumber($phone);
        $transactionId = 'REG' . SecurityHelper::generateRandomString(8);
        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);
        
        $payload = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'PartyA' => $phone,
            'PartyB' => $this->shortcode,
            'PhoneNumber' => $phone,
            'CallBackURL' => $this->callbackUrl . '/mpesa/registration-callback',
            'AccountReference' => 'Keschain Registration',
            'TransactionDesc' => 'Registration Fee',
            'Remark' => 'Payment for account registration'
        ];
        
        $accessToken = $this->getAccessToken();
        $response = $this->makeApiRequest(
            'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest',
            $payload,
            $accessToken
        );
        
        // Save payment record
        $paymentId = (new Payment())->create([
            'user_id' => null, // Not assigned yet
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'payment_method' => 'mpesa',
            'payment_type' => 'registration',
            'status' => 'pending',
            'phone_number' => $phone,
            'description' => 'Registration fee payment',
            'metadata' => json_encode([
                'mpesa_request' => $payload,
                'mpesa_response' => $response
            ])
        ]);
        
        return [
            'success' => isset($response['ResponseCode']) && $response['ResponseCode'] === '0',
            'message' => $response['ResponseDescription'] ?? 'Payment request failed',
            'payment_id' => $paymentId,
            'mpesa_response' => $response
        ];
    }
    
    public function handleCallback(array $data) {
        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();
            
            // Verify the callback is valid
            if (!isset($data['Body']['stkCallback'])) {
                throw new \Exception('Invalid callback format');
            }
            
            $callback = $data['Body']['stkCallback'];
            $merchantRequestId = $callback['MerchantRequestID'];
            $checkoutRequestId = $callback['CheckoutRequestID'];
            $resultCode = $callback['ResultCode'];
            
            // Find the payment record
            $payment = (new Payment())->findByMpesaRequestId($merchantRequestId, $checkoutRequestId);
            if (!$payment) {
                throw new \Exception('Payment record not found');
            }
            
            // Update payment status
            if ($resultCode === '0') {
                $callbackData = $callback['CallbackMetadata']['Item'];
                $mpesaReceipt = $this->getCallbackItemValue($callbackData, 'MpesaReceiptNumber');
                $phone = $this->getCallbackItemValue($callbackData, 'PhoneNumber');
                $amount = $this->getCallbackItemValue($callbackData, 'Amount');
                
                (new Payment())->update($payment['id'], [
                    'status' => 'completed',
                    'mpesa_receipt' => $mpesaReceipt,
                    'completed_at' => date('Y-m-d H:i:s'),
                    'metadata' => json_encode(array_merge(
                        json_decode($payment['metadata'], true),
                        ['callback' => $data]
                    ))
                ]);
                
                // If this is a registration payment, find the user by phone and update their status
                if ($payment['payment_type'] === 'registration') {
                    $user = (new \App\Models\User())->findByPhone($phone);
                    if ($user) {
                        (new \App\Models\User())->update($user['id'], [
                            'registration_fee_paid' => true,
                            'account_status' => 'active'
                        ]);
                        
                        // Send welcome SMS
                        (new SMSService())->sendSMS(
                            $phone,
                            "Welcome to Keschain! Your registration is complete. Your referral code is {$user['referral_code']}. Start inviting friends to earn more."
                        );
                    }
                }
                
                $db->commit();
                return ['success' => true, 'message' => 'Payment processed successfully'];
            } else {
                (new Payment())->update($payment['id'], [
                    'status' => 'failed',
                    'metadata' => json_encode(array_merge(
                        json_decode($payment['metadata'], true),
                        ['callback' => $data]
                    ))
                ]);
                
                $db->commit();
                return ['success' => false, 'message' => $callback['ResultDesc']];
            }
        } catch (\Exception $e) {
            $db->rollBack();
            error_log('Mpesa callback error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    private function getAccessToken(): string {
        $credentials = base64_encode($this->consumerKey . ':' . $this->consumerSecret);
        
        $ch = curl_init('https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $credentials
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        return $data['access_token'] ?? '';
    }
    
    private function makeApiRequest(string $url, array $payload, string $accessToken) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    private function formatPhoneNumber(string $phone): string {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) === 9 && substr($phone, 0, 1) === '7') {
            return '254' . $phone;
        }
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            return '254' . substr($phone, 1);
        }
        return $phone;
    }
    
    private function getCallbackItemValue(array $items, string $name) {
        foreach ($items as $item) {
            if ($item['Name'] === $name) {
                return $item['Value'];
            }
        }
        return null;
    }

    public function processDeposit(string $phone, float $amount): array {
        $phone = $this->formatPhoneNumber($phone);
        $transactionId = 'DPT' . \App\Helpers\SecurityHelper::generateRandomString(8);
        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);
        
        $payload = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'PartyA' => $phone,
            'PartyB' => $this->shortcode,
            'PhoneNumber' => $phone,
            'CallBackURL' => $this->callbackUrl . '/mpesa/deposit-callback',
            'AccountReference' => 'Keschain Deposit',
            'TransactionDesc' => 'Wallet deposit',
            'Remark' => 'Deposit to wallet'
        ];
        
        $accessToken = $this->getAccessToken();
        $response = $this->makeApiRequest(
            'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest',
            $payload,
            $accessToken
        );
        
        // Save payment record
        $paymentId = (new \App\Models\Payment())->create([
            'user_id' => $this->getUserIdByPhone($phone),
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'payment_method' => 'mpesa',
            'payment_type' => 'deposit',
            'status' => 'pending',
            'phone_number' => $phone,
            'description' => 'Wallet deposit',
            'metadata' => [
                'mpesa_request' => $payload,
                'mpesa_response' => $response
            ]
        ]);
        
        return [
            'success' => isset($response['ResponseCode']) && $response['ResponseCode'] === '0',
            'message' => $response['ResponseDescription'] ?? 'Deposit request failed',
            'transaction_id' => $transactionId,
            'payment_id' => $paymentId
        ];
    }

    public function handleDepositCallback(array $data): array {
        $db = \App\Config\Database::getInstance();
        
        try {
            $db->beginTransaction();
            
            if (!isset($data['Body']['stkCallback'])) {
                throw new \Exception('Invalid callback format');
            }
            
            $callback = $data['Body']['stkCallback'];
            $merchantRequestId = $callback['MerchantRequestID'];
            $checkoutRequestId = $callback['CheckoutRequestID'];
            $resultCode = $callback['ResultCode'];
            
            // Find the payment record
            $payment = (new \App\Models\Payment())->findByMpesaRequestId($merchantRequestId, $checkoutRequestId);
            if (!$payment) {
                throw new \Exception('Payment record not found');
            }
            
            if ($resultCode === '0') {
                $callbackData = $callback['CallbackMetadata']['Item'];
                $mpesaReceipt = $this->getCallbackItemValue($callbackData, 'MpesaReceiptNumber');
                $phone = $this->getCallbackItemValue($callbackData, 'PhoneNumber');
                $amount = $this->getCallbackItemValue($callbackData, 'Amount');
                
                // Update payment status
                (new \App\Models\Payment())->update($payment['id'], [
                    'status' => 'completed',
                    'mpesa_receipt' => $mpesaReceipt,
                    'completed_at' => date('Y-m-d H:i:s'),
                    'metadata' => json_encode(array_merge(
                        json_decode($payment['metadata'], true),
                        ['callback' => $data]
                    ))
                ]);
                
                // Credit user's wallet
                (new \App\Models\Wallet())->deposit($payment['user_id'], $amount);
                
                // Notify user
                $user = (new \App\Models\User())->findById($payment['user_id']);
                if ($user) {
                    (new \App\Services\Notification\SMSService())->sendSMS(
                        $user['phone'],
                        "Your deposit of KES $amount has been received. New wallet balance is KES " . 
                        (new \App\Models\Wallet())->getByUserId($user['id'])['balance']
                    );
                }
                
                $db->commit();
                return ['success' => true, 'message' => 'Deposit processed successfully'];
            } else {
                (new \App\Models\Payment())->update($payment['id'], [
                    'status' => 'failed',
                    'metadata' => json_encode(array_merge(
                        json_decode($payment['metadata'], true),
                        ['callback' => $data]
                    ))
                ]);
                
                $db->commit();
                return ['success' => false, 'message' => $callback['ResultDesc']];
            }
        } catch (\Exception $e) {
            $db->rollBack();
            error_log('MPesa deposit callback error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function getUserIdByPhone(string $phone): ?int {
        $db = \App\Config\Database::getInstance();
        $stmt = $db->prepare("SELECT id FROM pyr_users WHERE phone = ?");
        $stmt->execute([$phone]);
        return $stmt->fetchColumn() ?: null;
    }
}