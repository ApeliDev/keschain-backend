-- Task Categories
CREATE TABLE `pyr_task_categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  `description` VARCHAR(255),
  `reward_amount` DECIMAL(8,2) UNSIGNED NOT NULL,
  `daily_limit` TINYINT UNSIGNED DEFAULT 5,
  `task_count` INT UNSIGNED DEFAULT 0,
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_task_categories_active` (`is_active`)
) ENGINE=InnoDB;

-- Tasks
CREATE TABLE `pyr_tasks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `url` VARCHAR(255),
  `reward_amount` DECIMAL(8,2) UNSIGNED NOT NULL,
  `duration_minutes` SMALLINT UNSIGNED DEFAULT 5,
  `is_verified` BOOLEAN DEFAULT FALSE,
  `verification_type` ENUM('automatic', 'manual') DEFAULT 'manual',
  `expires_at` DATETIME,
  `total_available` INT UNSIGNED,
  `completed_count` INT UNSIGNED DEFAULT 0,
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `pyr_task_categories`(`id`) ON DELETE CASCADE,
  INDEX `idx_tasks_category` (`category_id`),
  INDEX `idx_tasks_active` (`is_active`)
) ENGINE=InnoDB;

-- User Tasks
CREATE TABLE `pyr_user_tasks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `task_id` BIGINT UNSIGNED NOT NULL,
  `status` ENUM('assigned', 'started', 'completed', 'verified', 'rejected') DEFAULT 'assigned',
  `proof` TEXT,
  `reward_amount` DECIMAL(8,2) UNSIGNED,
  `completed_at` DATETIME,
  `verified_at` DATETIME,
  `verified_by` BIGINT UNSIGNED,
  `rejection_reason` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `pyr_users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`task_id`) REFERENCES `pyr_tasks`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`verified_by`) REFERENCES `pyr_admins`(`id`) ON DELETE SET NULL,
  INDEX `idx_user_tasks_user` (`user_id`),
  INDEX `idx_user_tasks_status` (`status`)
) ENGINE=InnoDB;