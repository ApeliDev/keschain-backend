<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\User;
use App\Middlewares\AdminMiddleware;

class UserManagementController extends BaseController {
    public function __construct() {
        $this->registerMiddleware(new AdminMiddleware());
    }
    
    public function getUsers() {
        $page = $this->getQueryParam('page', 1);
        $perPage = $this->getQueryParam('per_page', 20);
        $status = $this->getQueryParam('status');
        $search = $this->getQueryParam('search');
        
        $userModel = new User();
        $users = $userModel->getPaginatedUsers($page, $perPage, $status, $search);
        $total = $userModel->getTotalUsers($status, $search);
        
        return $this->jsonResponse([
            'success' => true,
            'data' => $users,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ]
        ]);
    }
    
    public function getUser($userId) {
        $user = (new User())->findById($userId);
        if (!$user) {
            return $this->jsonResponse(['error' => 'User not found'], 404);
        }
        
        $wallet = (new \App\Models\Wallet())->getByUserId($userId);
        $referrals = (new \App\Models\Referral())->getDirectReferrals($userId);
        $tasks = (new \App\Models\UserTask())->getUserCompletedTasks($userId);
        
        return $this->jsonResponse([
            'success' => true,
            'data' => [
                'user' => $user,
                'wallet' => $wallet,
                'referrals_count' => count($referrals),
                'completed_tasks_count' => count($tasks)
            ]
        ]);
    }
    
    public function updateUserStatus($userId) {
        $data = $this->getJsonInput();
        
        if (!isset($data['status']) || !in_array($data['status'], ['active', 'suspended', 'banned'])) {
            return $this->jsonResponse(['error' => 'Invalid status'], 400);
        }
        
        try {
            (new User())->update($userId, [
                'account_status' => $data['status']
            ]);
            
            // Log activity
            $adminId = $this->getAuthUserId();
            (new \App\Models\ActivityLog())->create([
                'admin_id' => $adminId,
                'user_id' => $userId,
                'action' => 'update_user_status',
                'description' => "Changed user status to {$data['status']}",
                'metadata' => json_encode($data)
            ]);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'User status updated'
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}