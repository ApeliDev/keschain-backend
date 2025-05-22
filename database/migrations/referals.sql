-- Referral Earnings
CREATE TABLE `pyr_referral_earnings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `referral_id` BIGINT UNSIGNED NOT NULL,
  `referred_user_id` BIGINT UNSIGNED,
  `level` TINYINT UNSIGNED NOT NULL,
  `amount` DECIMAL(10,2) UNSIGNED NOT NULL,
  `source` ENUM('registration', 'task', 'deposit', 'withdrawal') NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `pyr_users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`referral_id`) REFERENCES `pyr_referrals`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`referred_user_id`) REFERENCES `pyr_users`(`id`) ON DELETE SET NULL,
  INDEX `idx_referral_earnings_user` (`user_id`),
  INDEX `idx_referral_earnings_referral` (`referral_id`)
) ENGINE=InnoDB;