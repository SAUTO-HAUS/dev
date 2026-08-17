CREATE TABLE IF NOT EXISTS `gh3sp_car_changelog` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `car_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(50) NOT NULL,
  `field_name` VARCHAR(100) DEFAULT NULL,
  `old_value` TEXT DEFAULT NULL,
  `new_value` TEXT DEFAULT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `user_login` VARCHAR(100) DEFAULT NULL,
  `catalog_type` VARCHAR(20) DEFAULT 'cars',
  `created_at` INT UNSIGNED NOT NULL,
  INDEX `idx_car_id` (`car_id`),
  INDEX `idx_action` (`action`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_catalog_type` (`catalog_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 