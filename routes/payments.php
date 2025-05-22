<?php
use App\Controllers\User\WalletController;
use App\Controllers\Admin\PaymentManagementController;
use App\Controllers\Admin\WithdrawalManagementController;
use App\Controllers\API\WebhookController;

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

// Webhooks
$router->post('/webhooks/mpesa', [WebhookController::class, 'handleMpesaWebhook']);
$router->post('/webhooks/task-payment', [WebhookController::class, 'handleTaskPaymentWebhook']);