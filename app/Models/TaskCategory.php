<?php
namespace App\Models;

use App\Config\Database;

class TaskCategory extends BaseModel {
    protected $table = 'pyr_task_categories';
    
    public function create(array $data): int {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("
            INSERT INTO {$this->table} 
            (name, description, reward_amount, daily_limit, is_active)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['name'],
            $data['description'] ?? '',
            $data['reward_amount'],
            $data['daily_limit'] ?? 5,
            $data['is_active'] ?? true
        ]);
        
        return $db->lastInsertId();
    }
    
    public function getActiveCategories(): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM {$this->table} 
            WHERE is_active = TRUE
            ORDER BY name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function updateCategoryStats(int $categoryId): bool {
        $db = Database::getInstance();
        
        // Update task count
        $stmt = $db->prepare("
            UPDATE {$this->table} c
            SET c.task_count = (
                SELECT COUNT(*) FROM pyr_tasks 
                WHERE category_id = c.id AND is_active = TRUE
            )
            WHERE c.id = ?
        ");
        return $stmt->execute([$categoryId]);
    }
}