<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Middlewares\AdminMiddleware;
use App\Validators\TaskValidator;

class TaskManagementController extends BaseController {
    public function __construct() {
        $this->registerMiddleware(new AdminMiddleware());
    }
    
    public function getTasks() {
        $status = $this->getQueryParam('status', 'active');
        $category = $this->getQueryParam('category');
        $limit = $this->getQueryParam('limit', 20);
        
        $db = \App\Config\Database::getInstance();
        
        $sql = "
            SELECT t.*, tc.name as category_name
            FROM pyr_tasks t
            JOIN pyr_task_categories tc ON t.category_id = tc.id
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($status === 'active') {
            $sql .= " AND t.is_active = TRUE AND (t.expires_at IS NULL OR t.expires_at > NOW())";
        } elseif ($status === 'expired') {
            $sql .= " AND t.is_active = TRUE AND t.expires_at < NOW()";
        } elseif ($status === 'inactive') {
            $sql .= " AND t.is_active = FALSE";
        }
        
        if (!empty($category)) {
            $sql .= " AND t.category_id = ?";
            $params[] = $category;
        }
        
        $sql .= " ORDER BY t.created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $this->jsonResponse([
            'success' => true,
            'data' => $stmt->fetchAll()
        ]);
    }
    
    public function createTask() {
        try {
            $data = $this->getJsonInput();
            
            $validator = new TaskValidator();
            $validated = $validator->validateCreate($data);
            
            $taskId = (new \App\Models\Task())->create($validated);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Task created successfully',
                'data' => [
                    'task_id' => $taskId
                ]
            ]);
            
        } catch (\App\Exceptions\ValidationException $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    public function updateTask($taskId) {
        try {
            $data = $this->getJsonInput();
            
            $validator = new TaskValidator();
            $validated = $validator->validateUpdate($data);
            
            (new \App\Models\Task())->update($taskId, $validated);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Task updated successfully'
            ]);
            
        } catch (\App\Exceptions\ValidationException $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    public function verifyTasks() {
        try {
            $data = $this->getJsonInput();
            $adminId = $this->getAuthUserId();
            
            if (empty($data['task_ids']) || !is_array($data['task_ids'])) {
                throw new \Exception('Invalid task IDs provided');
            }
            
            $approve = $data['action'] === 'approve';
            $reason = $data['reason'] ?? null;
            
            $verifiedCount = 0;
            $errors = [];
            
            foreach ($data['task_ids'] as $taskId) {
                try {
                    if ((new \App\Services\Task\TaskVerificationService())->verifyTask(
                        $taskId, 
                        $approve, 
                        $reason, 
                        $adminId
                    )) {
                        $verifiedCount++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Task $taskId: " . $e->getMessage();
                }
            }
            
            return $this->jsonResponse([
                'success' => true,
                'message' => "Processed $verifiedCount tasks",
                'data' => [
                    'processed_count' => $verifiedCount,
                    'errors' => $errors
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}