<?php
namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Services\Payment\MpesaService;

class WebhookController extends BaseController {
    public function handleMpesaWebhook() {
        try {
            $data = $this->getJsonInput();
            
            // Verify webhook signature if needed
            if (!$this->verifyWebhookSignature($data)) {
                throw new \Exception('Invalid webhook signature');
            }
            
            $result = (new MpesaService())->handleCallback($data);
            
            if ($result['success']) {
                return $this->jsonResponse(['success' => true]);
            }
            
            return $this->jsonResponse(['error' => $result['message']], 400);
            
        } catch (\Exception $e) {
            error_log('Webhook error: ' . $e->getMessage());
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    private function verifyWebhookSignature(array $data): bool {
        // Implement your webhook signature verification logic
        // This might check a signature header against your secret key
        
        return true; // Skip verification in this example
    }
    
    public function handleTaskPaymentWebhook() {
        try {
            $data = $this->getJsonInput();
            
            // Verify task payment completion
            $taskPaymentId = $data['task_payment_id'];
            $status = $data['status'];
            
            if ($status === 'completed') {
                // Process task payment and award user
                $this->processTaskPayment($taskPaymentId);
            }
            
            return $this->jsonResponse(['success' => true]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    private function processTaskPayment(string $taskPaymentId): void {
        // Implement your task payment processing logic
        // This would typically:
        // 1. Verify the task payment exists and is valid
        // 2. Award the user the task reward
        // 3. Update the task payment status
    }
}