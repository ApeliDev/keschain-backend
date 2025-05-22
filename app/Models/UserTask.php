<?php
namespace App\Models;

use App\Config\Database;

class UserTask extends BaseModel {
    protected $table = 'pyr_user_tasks';
    
    public function create(array $data): int {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("
            INSERT INTO {$this->table} 
            (user_id, task_id, status, reward_amount)
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['user_id'],
            $data['task_id'],
            $data['status'] ?? 'assigned',
            $data['reward_amount']
        ]);
        
        return $db->lastInsertId();
    }
    
    public function findUserTask(int $userId, int $taskId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM {$this->table} 
            WHERE user_id = ? AND task_id = ?
            LIMIT 1
        ");
        $stmt->execute([$userId, $taskId]);
        return $stmt->fetch();
    }
    
    public function getUserCompletedTasks(int $userId, int $limit = 10): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT ut.*, t.title, t.description, t.url, tc.name as category_name
            FROM {$this->table} ut
            JOIN pyr_tasks t ON ut.task_id = t.id
            JOIN pyr_task_categories tc ON t.category_id = tc.id
            WHERE ut.user_id = ? AND ut.status = 'verified'
            ORDER BY ut.verified_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
    
    public function submitTask(int $assignmentId, string $proof): bool {
        return $this->update($assignmentId, [
            'status' => 'completed',
            'proof' => $proof,
            'completed_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function getPendingVerificationTasks(int $limit = 50): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT ut.*, u.username, u.phone, t.title
            FROM {$this->table} ut
            JOIN pyr_users u ON ut.user_id = u.id
            JOIN pyr_tasks t ON ut.task_id = t.id
            WHERE ut.status = 'completed'
            ORDER BY ut.completed_at ASC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}