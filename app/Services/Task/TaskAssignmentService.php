<?php
namespace App\Services\Task;

use App\Config\Database;
use App\Models\Task;
use App\Models\UserTask;
use App\Services\Notification\NotificationService;

class TaskAssignmentService {
    public function assignTask(int $userId, int $taskId): int {
        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();
            
            $taskModel = new Task();
            $task = $taskModel->findById($taskId);
            
            if (!$task || !$task['is_active']) {
                throw new \Exception('Task not available');
            }
            
            // Check if user already has this task
            $userTask = new UserTask();
            $existingTask = $userTask->findUserTask($userId, $taskId);
            
            if ($existingTask) {
                throw new \Exception('You already have this task');
            }
            
            // Assign task
            $assignmentId = $userTask->create([
                'user_id' => $userId,
                'task_id' => $taskId,
                'status' => 'assigned',
                'reward_amount' => $task['reward_amount']
            ]);
            
            // Update task count
            $taskModel->incrementCompletedCount($taskId);
            
            // Notify user
            (new NotificationService())->createNotification(
                $userId,
                'New Task Assigned',
                "You have been assigned a new task: {$task['title']}",
                'task',
                ['task_id' => $taskId, 'assignment_id' => $assignmentId]
            );
            
            $db->commit();
            return $assignmentId;
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
    
    public function getAvailableTasks(int $userId, int $limit = 10): array {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("
            SELECT t.*, tc.name as category_name
            FROM pyr_tasks t
            JOIN pyr_task_categories tc ON t.category_id = tc.id
            WHERE t.is_active = TRUE
            AND (t.expires_at IS NULL OR t.expires_at > NOW())
            AND (t.total_available IS NULL OR t.completed_count < t.total_available)
            AND t.id NOT IN (
                SELECT task_id FROM pyr_user_tasks 
                WHERE user_id = ? AND status IN ('assigned', 'started', 'completed')
            )
            ORDER BY t.reward_amount DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        
        return $stmt->fetchAll();
    }
}