-- Payment Transactions
CREATE TABLE `pyr_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED,
  `transaction_id` VARCHAR(50) UNIQUE NOT NULL,
  `amount` DECIMAL(10,2) UNSIGNED NOT NULL,
  `currency` VARCHAR(3) DEFAULT 'KES',
  `payment_method` ENUM('mpesa', 'bank', 'airtime', 'manual') NOT NULL,
  `payment_type` ENUM('registration', 'deposit', 'withdrawal', 'transfer') NOT NULL,
  `status` ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
  `mpesa_receipt` VARCHAR(50),
  `phone_number` VARCHAR(20),
  `description` VARCHAR(255),
  `metadata` JSON,
  `completed_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `pyr_users`(`id`) ON DELETE SET NULL,
  INDEX `idx_payments_user` (`user_id`),
  INDEX `idx_payments_status` (`status`),
  INDEX `idx_payments_transaction` (`transaction_id`)
) ENGINE=InnoDB;

-- Withdrawal Requests
CREATE TABLE `pyr_withdrawals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `payment_id` BIGINT UNSIGNED,
  `amount` DECIMAL(10,2) UNSIGNED NOT NULL,
  `fee` DECIMAL(10,2) UNSIGNED DEFAULT 0.00,
  `net_amount` DECIMAL(10,2) UNSIGNED NOT NULL,
  `payment_method` ENUM('mpesa', 'bank', 'airtime') NOT NULL,
  `account_details` VARCHAR(255) NOT NULL,
  `status` ENUM('pending', 'processing', 'completed', 'rejected') DEFAULT 'pending',
  `processed_by` BIGINT UNSIGNED,
  `processed_at` DATETIME,
  `rejection_reason` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `pyr_users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`payment_id`) REFERENCES `pyr_payments`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`processed_by`) REFERENCES `pyr_admins`(`id`) ON DELETE SET NULL,
  INDEX `idx_withdrawals_user` (`user_id`),
  INDEX `idx_withdrawals_status` (`status`)
) ENGINE=InnoDB;