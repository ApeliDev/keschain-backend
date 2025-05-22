<?php
namespace App\Models;

use App\Config\Database;

class Task extends BaseModel {
    protected $table = 'pyr_tasks';
    
    public function getAvailableTasks(int $userId) {
        $db = Database::getInstance();
        
        $query = "
            SELECT t.*, tc.name as category_name
            FROM {$this->table} t
            JOIN pyr_task_categories tc ON t.category_id = tc.id
            WHERE t.is_active = TRUE
            AND (t.expires_at IS NULL OR t.expires_at > NOW())
            AND (t.total_available IS NULL OR t.completed_count < t.total_available)
            AND t.id NOT IN (
                SELECT task_id FROM pyr_user_tasks 
                WHERE user_id = ? AND status IN ('assigned', 'started', 'completed')
            )
            ORDER BY t.reward_amount DESC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll();
    }
    
    public function assignTask(int $userId, int $taskId) {
        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();
            
            // Check if task is available
            $task = $this->findById($taskId);
            if (!$task || !$task['is_active'] || 
                ($task['expires_at'] && $task['expires_at'] < date('Y-m-d H:i:s')) ||
                ($task['total_available'] && $task['completed_count'] >= $task['total_available'])) {
                throw new \Exception('Task is not available');
            }
            
            // Check if user already has this task
            $stmt = $db->prepare("
                SELECT id FROM pyr_user_tasks 
                WHERE user_id = ? AND task_id = ? AND status IN ('assigned', 'started', 'completed')
            ");
            $stmt->execute([$userId, $taskId]);
            if ($stmt->fetch()) {
                throw new \Exception('You already have this task');
            }
            
            // Assign task
            $userTask = new UserTask();
            $userTaskId = $userTask->create([
                'user_id' => $userId,
                'task_id' => $taskId,
                'status' => 'assigned',
                'reward_amount' => $task['reward_amount']
            ]);
            
            // Update task count
            $stmt = $db->prepare("
                UPDATE {$this->table} 
                SET completed_count = completed_count + 1 
                WHERE id = ?
            ");
            $stmt->execute([$taskId]);
            
            $db->commit();
            return $userTaskId;
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}