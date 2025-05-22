<?php
use App\Controllers\Auth\LoginController;
use App\Controllers\Auth\RegistrationController;
use App\Controllers\Auth\VerificationController;
use App\Controllers\User\DashboardController;
use App\Controllers\User\ProfileController;
use App\Controllers\User\WalletController;
use App\Controllers\User\TaskController;
use App\Controllers\User\ReferralController;
use App\Controllers\User\WithdrawalController;
use App\Controllers\Admin\AdminAuthController;
use App\Controllers\Admin\UserManagementController;
use App\Controllers\Admin\WithdrawalManagementController;
use App\Controllers\Admin\TaskManagementController;
use App\Controllers\API\MpesaCallbackController;
use App\Controllers\Admin\PaymentManagementController;
use App\Controllers\API\WebhookController;



// Auth routes
$router->post('/auth/register', [RegistrationController::class, 'register']);
$router->post('/auth/login', [LoginController::class, 'login']);
$router->post('/auth/verify-phone', [VerificationController::class, 'verifyPhone']);

// User routes (require authentication)
$router->group('/user', function($router) {
    $router->get('/dashboard', [DashboardController::class, 'index']);
    
    // Profile
    $router->get('/profile', [ProfileController::class, 'getProfile']);
    $router->put('/profile', [ProfileController::class, 'updateProfile']);
    
    // Wallet
    $router->get('/wallet/balance', [WalletController::class, 'getBalance']);
    $router->post('/wallet/withdraw', [WalletController::class, 'requestWithdrawal']);
    
    // Tasks
    $router->get('/tasks/available', [TaskController::class, 'getAvailableTasks']);
    $router->post('/tasks/start', [TaskController::class, 'startTask']);
    $router->post('/tasks/submit', [TaskController::class, 'submitTask']);
    $router->get('/tasks/completed', [TaskController::class, 'getCompletedTasks']);
    
    // Referrals
    $router->get('/referrals/tree', [ReferralController::class, 'getReferralTree']);
    $router->get('/referrals/stats', [ReferralController::class, 'getReferralStats']);
    
    // Withdrawals
    $router->get('/withdrawals/history', [WithdrawalController::class, 'getWithdrawalHistory']);
}, ['middleware' => 'auth']);

// Admin routes
$router->group('/admin', function($router) {
    $router->post('/auth/login', [AdminAuthController::class, 'login']);
    
    $router->group('', function($router) {
        $router->get('/users', [UserManagementController::class, 'getUsers']);
        $router->get('/users/{id}', [UserManagementController::class, 'getUser']);
        $router->put('/users/{id}/status', [UserManagementController::class, 'updateUserStatus']);
        
        $router->get('/withdrawals/pending', [WithdrawalManagementController::class, 'getPendingWithdrawals']);
        $router->post('/withdrawals/{id}/process', [WithdrawalManagementController::class, 'processWithdrawal']);
        
        $router->get('/tasks', [TaskManagementController::class, 'getTasks']);
        $router->post('/tasks', [TaskManagementController::class, 'createTask']);
    }, ['middleware' => 'admin']);
});


// Admin withdrawal management
$router->group('/admin/withdrawals', function($router) {
    $router->get('/pending', [WithdrawalManagementController::class, 'getPendingWithdrawals']);
    $router->post('/{id}/process', [WithdrawalManagementController::class, 'processWithdrawal']);
    $router->post('/{id}/complete', [WithdrawalManagementController::class, 'completeWithdrawal']);
}, ['middleware' => 'admin']);

// Payment webhooks
$router->post('/webhooks/mpesa', [MpesaCallbackController::class, 'handleCallback']);
$router->post('/webhooks/mpesa/deposit', [MpesaCallbackController::class, 'handleDepositCallback']);

// User payment routes
$router->group('/user/wallet', function($router) {
    $router->get('/balance', [WalletController::class, 'getBalance']);
    $router->post('/deposit', [WalletController::class, 'deposit']);
    $router->post('/withdraw', [WalletController::class, 'requestWithdrawal']);
    $router->get('/transactions', [WalletController::class, 'getTransactionHistory']);
    $router->get('/referral-earnings', [WalletController::class, 'getReferralEarnings']);
}, ['middleware' => 'auth']);

// Admin payment management
$router->group('/admin/payments', function($router) {
    $router->get('/stats', [PaymentManagementController::class, 'getPaymentStats']);
    $router->get('/search', [PaymentManagementController::class, 'searchPayments']);
    $router->post('/manual', [PaymentManagementController::class, 'manualPayment']);
}, ['middleware' => 'admin']);


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

// Webhooks
$router->post('/webhooks/mpesa', [MpesaCallbackController::class, 'handleCallback']);
$router->post('/webhooks/task-payment', [WebhookController::class, 'handleTaskPaymentWebhook']);
$router->post('/webhooks/mpesa', [WebhookController::class, 'handleMpesaWebhook']);