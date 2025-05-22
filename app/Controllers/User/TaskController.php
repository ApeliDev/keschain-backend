<?php
namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\Task;
use App\Middlewares\AuthMiddleware;

class TaskController extends BaseController {
    public function __construct() {
        $this->registerMiddleware(new AuthMiddleware());
    }
    
    public function getAvailableTasks() {
        $userId = $this->getAuthUserId();
        $tasks = (new Task())->getAvailableTasks($userId);
        
        return $this->jsonResponse([
            'success' => true,
            'data' => $tasks
        ]);
    }
    
    public function startTask() {
        $userId = $this->getAuthUserId();
        $taskId = $this->getJsonInput('task_id');
        
        try {
            $taskAssignmentId = (new Task())->assignTask($userId, $taskId);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Task assigned successfully',
                'data' => [
                    'task_assignment_id' => $taskAssignmentId
                ]
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }
    
    public function submitTask() {
        $userId = $this->getAuthUserId();
        $data = $this->getJsonInput();
        
        try {
            $taskSubmissionId = (new \App\Services\Task\TaskService())->submitTask(
                $userId,
                $data['task_assignment_id'],
                $data['proof']
            );
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Task submitted for verification',
                'data' => [
                    'task_submission_id' => $taskSubmissionId
                ]
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }
    
    public function getCompletedTasks() {
        $userId = $this->getAuthUserId();
        $tasks = (new \App\Models\UserTask())->getUserCompletedTasks($userId);
        
        return $this->jsonResponse([
            'success' => true,
            'data' => $tasks
        ]);
    }
}