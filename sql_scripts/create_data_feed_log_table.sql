CREATE TABLE IF NOT EXISTS `gh3sp_data_feed_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `feed_type` ENUM('main', 'pruncul', 'orders') NOT NULL COMMENT 'Type of feed: main catalog, Pruncul branch, or orders',
    `generation_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When the feed was generated',
    `cars_added` INT DEFAULT 0 COMMENT 'Number of cars added in this generation',
    `cars_removed` INT DEFAULT 0 COMMENT 'Number of cars removed in this generation',
    `total_cars` INT DEFAULT 0 COMMENT 'Total number of cars in feed after generation',
    `execution_time` DECIMAL(10,3) DEFAULT 0 COMMENT 'Generation time in seconds',
    `status` ENUM('success', 'error') DEFAULT 'success' COMMENT 'Generation status',
    `error_message` TEXT NULL COMMENT 'Error message if generation failed',
    `notes` TEXT NULL COMMENT 'Additional notes or details',
    INDEX `idx_feed_type` (`feed_type`),
    INDEX `idx_generation_date` (`generation_date`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Logs for Facebook data feed changes';

CREATE OR REPLACE VIEW `gh3sp_data_feed_latest_stats` AS
SELECT 
    `feed_type`,
    `generation_date`,
    `cars_added`,
    `cars_removed`,
    `total_cars`,
    `execution_time`,
    `status`
FROM `gh3sp_data_feed_log`
WHERE `id` IN (
    SELECT MAX(`id`)
    FROM `gh3sp_data_feed_log`
    GROUP BY `feed_type`
)
ORDER BY `feed_type`;

INSERT INTO `gh3sp_data_feed_log` (`feed_type`, `cars_added`, `cars_removed`, `total_cars`, `status`, `notes`)
VALUES 
    ('main', 0, 0, 0, 'success', 'Initial setup - awaiting first generation'),
    ('pruncul', 0, 0, 0, 'success', 'Initial setup - awaiting first generation'),
    ('orders', 0, 0, 0, 'success', 'Initial setup - awaiting first generation');
