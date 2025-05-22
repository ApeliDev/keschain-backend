<?php
namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Services\Payment\MpesaService;

class MpesaCallbackController extends BaseController {
    public function handleCallback() {
        try {
            $data = $this->getJsonInput();
            
            $result = (new MpesaService())->handleCallback($data);
            
            if ($result['success']) {
                return $this->jsonResponse(['success' => true, 'message' => 'Callback processed']);
            }
            
            return $this->jsonResponse(['error' => $result['message']], 400);
            
        } catch (\Exception $e) {
            error_log('MPesa callback error: ' . $e->getMessage());
            return $this->jsonResponse(['error' => 'Error processing callback'], 500);
        }
    }
    
    public function handleDepositCallback() {
        try {
            $data = $this->getJsonInput();
            
            $result = (new MpesaService())->handleDepositCallback($data);
            
            if ($result['success']) {
                return $this->jsonResponse(['success' => true, 'message' => 'Deposit processed']);
            }
            
            return $this->jsonResponse(['error' => $result['message']], 400);
            
        } catch (\Exception $e) {
            error_log('MPesa deposit callback error: ' . $e->getMessage());
            return $this->jsonResponse(['error' => 'Error processing deposit'], 500);
        }
    }
}