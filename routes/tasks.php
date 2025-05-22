<?php
use App\Controllers\User\TaskController;
use App\Controllers\Admin\TaskManagementController;

// User task routes
$router->group('/user/tasks', function($router) {
    $router->get('/available', [TaskController::class, 'getAvailableTasks']);
    $router->post('/start', [TaskController::class, 'startTask']);
    $router->post('/submit', [TaskController::class, 'submitTask']);
    $router->get('/completed', [TaskController::class, 'getCompletedTasks']);
}, ['middleware' => 'auth']);

// Admin task management
$router->group('/admin/tasks', function($router) {
    $router->get('/', [TaskManagementController::class, 'getTasks']);
    $router->post('/', [TaskManagementController::class, 'createTask']);
    $router->put('/{id}', [TaskManagementController::class, 'updateTask']);
    $router->post('/verify', [TaskManagementController::class, 'verifyTasks']);
}, ['middleware' => 'admin']);