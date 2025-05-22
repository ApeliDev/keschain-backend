keschain-backend/
├── app/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   ├── AuthController.php
│   │   │   ├── RegistrationController.php
│   │   │   ├── LoginController.php
│   │   │   ├── PasswordResetController.php
│   │   │   └── VerificationController.php
│   │   ├── User/
│   │   │   ├── DashboardController.php
│   │   │   ├── ProfileController.php
│   │   │   ├── WalletController.php
│   │   │   ├── TaskController.php
│   │   │   ├── ReferralController.php
│   │   │   ├── WithdrawalController.php
│   │   │   └── NotificationController.php
│   │   ├── Admin/
│   │   │   ├── AdminAuthController.php
│   │   │   ├── AdminDashboardController.php
│   │   │   ├── UserManagementController.php
│   │   │   ├── WithdrawalManagementController.php
│   │   │   ├── TaskManagementController.php
│   │   │   ├── PaymentManagementController.php
│   │   │   ├── SettingsController.php
│   │   │   └── ReportsController.php
│   │   ├── API/
│   │   │   ├── MpesaCallbackController.php
│   │   │   ├── SMSWebhookController.php
│   │   │   └── WebhookController.php
│   │   └── BaseController.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Wallet.php
│   │   ├── Admin.php
│   │   ├── Payment.php
│   │   ├── Withdrawal.php
│   │   ├── Referral.php
│   │   ├── ReferralEarning.php
│   │   ├── Task.php
│   │   ├── TaskCategory.php
│   │   ├── UserTask.php
│   │   ├── Notification.php
│   │   ├── ActivityLog.php
│   │   ├── SMSLog.php
│   │   ├── Setting.php
│   │   ├── AdminPermission.php
│   │   ├── EmailTemplate.php
│   │   └── BaseModel.php
│   ├── Services/
│   │   ├── Auth/
│   │   │   ├── AuthService.php
│   │   │   ├── RegistrationService.php
│   │   │   ├── LoginService.php
│   │   │   ├── PasswordService.php
│   │   │   └── VerificationService.php
│   │   ├── Payment/
│   │   │   ├── MpesaService.php
│   │   │   ├── PaymentService.php
│   │   │   └── WithdrawalService.php
│   │   ├── Referral/
│   │   │   ├── ReferralService.php
│   │   │   ├── CommissionService.php
│   │   │   └── ReferralTreeService.php
│   │   ├── Task/
│   │   │   ├── TaskService.php
│   │   │   ├── TaskAssignmentService.php
│   │   │   └── TaskVerificationService.php
│   │   ├── Notification/
│   │   │   ├── SMSService.php
│   │   │   ├── EmailService.php
│   │   │   └── NotificationService.php
│   │   ├── Wallet/
│   │   │   ├── WalletService.php
│   │   │   └── TransactionService.php
│   │   ├── Admin/
│   │   │   ├── AdminService.php
│   │   │   ├── UserManagementService.php
│   │   │   └── ReportService.php
│   │   └── Common/
│   │       ├── FileUploadService.php
│   │       ├── ValidationService.php
│   │       ├── LoggingService.php
│   │       ├── CacheService.php
│   │       └── SecurityService.php
│   ├── Middleware/
│   │   ├── AuthMiddleware.php
│   │   ├── AdminMiddleware.php
│   │   ├── VerificationMiddleware.php
│   │   ├── CSRFMiddleware.php
│   │   ├── RateLimitMiddleware.php
│   │   ├── CorsMiddleware.php
│   │   ├── MaintenanceMiddleware.php
│   │   └── LoggingMiddleware.php
│   ├── Validators/
│   │   ├── Auth/
│   │   │   ├── RegisterValidator.php
│   │   │   ├── LoginValidator.php
│   │   │   └── PasswordResetValidator.php
│   │   ├── User/
│   │   │   ├── ProfileValidator.php
│   │   │   ├── WalletValidator.php
│   │   │   └── TaskValidator.php
│   │   ├── Payment/
│   │   │   ├── PaymentValidator.php
│   │   │   └── WithdrawalValidator.php
│   │   ├── Admin/
│   │   │   └── AdminValidator.php
│   │   └── BaseValidator.php
│   ├── Helpers/
│   │   ├── DatabaseHelper.php
│   │   ├── SecurityHelper.php
│   │   ├── FormatHelper.php
│   │   ├── DateHelper.php
│   │   ├── StringHelper.php
│   │   ├── CurrencyHelper.php
│   │   ├── PhoneHelper.php
│   │   └── ResponseHelper.php
│   ├── Config/
│   │   ├── Database.php
│   │   ├── App.php
│   │   ├── Payment.php
│   │   ├── SMS.php
│   │   ├── Mail.php
│   │   ├── Security.php
│   │   └── Constants.php
│   ├── Exceptions/
│   │   ├── AuthException.php
│   │   ├── PaymentException.php
│   │   ├── ValidationException.php
│   │   ├── DatabaseException.php
│   │   └── APIException.php
│   └── Jobs/
│       ├── ProcessPaymentJob.php
│       ├── SendSMSJob.php
│       ├── SendEmailJob.php
│       ├── ProcessReferralCommissionJob.php
│       └── CleanupJob.php
├── api/
│   ├── v1/
│   │   ├── auth/
│   │   │   ├── register.php
│   │   │   ├── login.php
│   │   │   ├── logout.php
│   │   │   ├── refresh.php
│   │   │   ├── forgot-password.php
│   │   │   ├── reset-password.php
│   │   │   ├── verify-phone.php
│   │   │   └── verify-email.php
│   │   ├── user/
│   │   │   ├── dashboard.php
│   │   │   ├── profile.php
│   │   │   ├── wallet.php
│   │   │   ├── tasks.php
│   │   │   ├── referrals.php
│   │   │   ├── withdrawals.php
│   │   │   └── notifications.php
│   │   ├── admin/
│   │   │   ├── login.php
│   │   │   ├── dashboard.php
│   │   │   ├── users.php
│   │   │   ├── payments.php
│   │   │   ├── withdrawals.php
│   │   │   ├── tasks.php
│   │   │   ├── settings.php
│   │   │   └── reports.php
│   │   ├── payments/
│   │   │   ├── mpesa/
│   │   │   │   ├── initiate.php
│   │   │   │   ├── callback.php
│   │   │   │   ├── confirmation.php
│   │   │   │   └── validation.php
│   │   │   └── status.php
│   │   └── webhooks/
│   │       ├── sms.php
│   │       └── email.php
│   └── index.php
├── database/
│   ├── migrations/
│   │   └── schema.sql (your provided SQL)
│   ├── seeds/
│   │   ├── AdminSeeder.php
│   │   ├── TaskCategorySeeder.php
│   │   ├── SettingsSeeder.php
│   │   └── PermissionSeeder.php
│   └── DatabaseManager.php
├── storage/
│   ├── logs/
│   │   ├── app/
│   │   ├── error/
│   │   ├── payment/
│   │   ├── sms/
│   │   └── security/
│   ├── cache/
│   ├── sessions/
│   ├── uploads/
│   │   ├── avatars/
│   │   └── task_proofs/
│   └── temp/
├── routes/
│   ├── api.php
│   ├── auth.php
│   ├── user.php
│   ├── admin.php
│   └── webhooks.php
├── bootstrap/
│   ├── app.php
│   ├── autoload.php
│   ├── constants.php
│   └── error_handler.php
├── tests/
│   ├── Unit/
│   │   ├── Models/
│   │   ├── Services/
│   │   └── Helpers/
│   ├── Integration/
│   │   ├── Auth/
│   │   ├── Payment/
│   │   └── Referral/
│   └── TestCase.php
├── vendor/
├── .env
├── .env.example
├── .gitignore
├── .htaccess
├── composer.json
├── composer.lock
├── README.md
└── install.php