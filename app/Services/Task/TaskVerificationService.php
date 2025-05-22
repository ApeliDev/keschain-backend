<?php
namespace App\Services\Task;

use App\Config\Database;
use App\Models\UserTask;
use App\Models\Wallet;
use App\Services\Payment\TransactionService;
use App\Services\Referral\CommissionService;

class TaskVerificationService {
    public function verifyTask(int $taskAssignmentId, bool $approve, ?string $rejectionReason = null, ?int $adminId = null): bool {
        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();
            
            $userTask = new UserTask();
            $task = $userTask->findById($taskAssignmentId);
            
            if (!$task) {
                throw new \Exception('Task assignment not found');
            }
            
            if ($approve) {
                // Approve the task
                $userTask->update($taskAssignmentId, [
                    'status' => 'verified',
                    'verified_at' => date('Y-m-d H:i:s'),
                    'verified_by' => $adminId,
                    'reward_amount' => $task['reward_amount']
                ]);
                
                // Award user
                $this->awardUserForTask($task['user_id'], $task['reward_amount'], $task['id']);
                
                // Process referral commissions
                (new CommissionService())->processReferralCommission(
                    $task['user_id'],
                    $task['reward_amount'],
                    'task'
                );
            } else {
                // Reject the task
                $userTask->update($taskAssignmentId, [
                    'status' => 'rejected',
                    'rejection_reason' => $rejectionReason,
                    'verified_by' => $adminId
                ]);
            }
            
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
    
    private function awardUserForTask(int $userId, float $amount, int $taskAssignmentId): void {
        // Record transaction
        (new TransactionService())->recordTransaction(
            $userId,
            $amount,
            'task_reward',
            ['task_assignment_id' => $taskAssignmentId]
        );
        
        // Update wallet
        (new Wallet())->deposit($userId, $amount);
    }
    
    public function autoVerifyTasks(): int {
        $db = Database::getInstance();
        $verifiedCount = 0;
        
        // Get tasks with automatic verification
        $stmt = $db->prepare("
            SELECT ut.id 
            FROM pyr_user_tasks ut
            JOIN pyr_tasks t ON ut.task_id = t.id
            WHERE ut.status = 'completed'
            AND t.verification_type = 'automatic'
            AND ut.verified_at IS NULL
            LIMIT 100
        ");
        $stmt->execute();
        $tasks = $stmt->fetchAll();
        
        foreach ($tasks as $task) {
            try {
                if ($this->verifyTask($task['id'], true)) {
                    $verifiedCount++;
                }
            } catch (\Exception $e) {
                error_log("Auto-verify failed for task {$task['id']}: " . $e->getMessage());
            }
        }
        
        return $verifiedCount;
    }
}