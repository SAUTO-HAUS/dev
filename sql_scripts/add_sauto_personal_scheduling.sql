-- Create table for SAUTO Personal custom scheduling
CREATE TABLE IF NOT EXISTS `sauto_personal_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `car_id` int(11) NOT NULL,
  `catalog_type` enum('in_stock','on_order') NOT NULL DEFAULT 'in_stock',
  `schedule_date` date NOT NULL,
  `schedule_time` time NOT NULL,
  `status` enum('pending','published','failed','cancelled') NOT NULL DEFAULT 'pending',
  `published_at` datetime NULL,
  `999_id` varchar(50) NULL,
  `error_message` text NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_car_id` (`car_id`),
  KEY `idx_schedule_datetime` (`schedule_date`, `schedule_time`),
  KEY `idx_status` (`status`),
  KEY `idx_catalog_type` (`catalog_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add index for efficient cron job queries
CREATE INDEX `idx_pending_schedules` ON `sauto_personal_schedules` (`status`, `schedule_date`, `schedule_time`);
