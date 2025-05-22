<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Middlewares\AdminMiddleware;

class PaymentManagementController extends BaseController {
    public function __construct() {
        $this->registerMiddleware(new AdminMiddleware());
    }
    
    public function getPaymentStats() {
        $db = \App\Config\Database::getInstance();
        
        // Total deposits
        $stmt = $db->prepare("
            SELECT COUNT(*) as count, SUM(amount) as total 
            FROM pyr_payments 
            WHERE payment_type = 'deposit' AND status = 'completed'
        ");
        $stmt->execute();
        $deposits = $stmt->fetch();
        
        // Total withdrawals
        $stmt = $db->prepare("
            SELECT COUNT(*) as count, SUM(amount) as total 
            FROM pyr_withdrawals 
            WHERE status = 'completed'
        ");
        $stmt->execute();
        $withdrawals = $stmt->fetch();
        
        // Recent transactions
        $stmt = $db->prepare("
            SELECT p.*, u.username, u.phone 
            FROM pyr_payments p
            LEFT JOIN pyr_users u ON p.user_id = u.id
            ORDER BY p.created_at DESC 
            LIMIT 10
        ");
        $stmt->execute();
        $recentTransactions = $stmt->fetchAll();
        
        return $this->jsonResponse([
            'success' => true,
            'data' => [
                'deposits' => $deposits,
                'withdrawals' => $withdrawals,
                'recent_transactions' => $recentTransactions
            ]
        ]);
    }
    
    public function searchPayments() {
        $query = $this->getQueryParam('query');
        $type = $this->getQueryParam('type');
        $status = $this->getQueryParam('status');
        $limit = $this->getQueryParam('limit', 20);
        
        $db = \App\Config\Database::getInstance();
        
        $sql = "
            SELECT p.*, u.username, u.phone 
            FROM pyr_payments p
            LEFT JOIN pyr_users u ON p.user_id = u.id
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($query)) {
            $sql .= " AND (
                p.transaction_id LIKE ? OR 
                p.mpesa_receipt LIKE ? OR 
                u.phone LIKE ? OR 
                u.username LIKE ?
            )";
            $searchTerm = "%$query%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (!empty($type)) {
            $sql .= " AND p.payment_type = ?";
            $params[] = $type;
        }
        
        if (!empty($status)) {
            $sql .= " AND p.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY p.created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $this->jsonResponse([
            'success' => true,
            'data' => $stmt->fetchAll()
        ]);
    }
    
    public function manualPayment() {
        try {
            $data = $this->getJsonInput();
            
            $requiredFields = ['user_id', 'amount', 'payment_type', 'description'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new \Exception("Field $field is required");
                }
            }
            
            $paymentId = (new \App\Services\Payment\PaymentService())->recordPayment([
                'user_id' => $data['user_id'],
                'transaction_id' => 'MAN' . time(),
                'amount' => $data['amount'],
                'payment_method' => 'manual',
                'payment_type' => $data['payment_type'],
                'status' => 'completed',
                'description' => $data['description'],
                'metadata' => [
                    'admin_id' => $this->getAuthUserId(),
                    'notes' => $data['notes'] ?? ''
                ]
            ]);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Manual payment recorded',
                'data' => [
                    'payment_id' => $paymentId
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}