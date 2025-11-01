-- Setup Facebook Scheduling System
-- Run this SQL to configure everything needed

-- 1. Add Facebook settings if they don't exist
INSERT IGNORE INTO gh3sp_settings (name, value) VALUES
-- Facebook settings (location-based)
('location_1_facebook_page_id', '725963964220309'),
('location_1_facebook_token', 'EAAPYJ3JWk0UBPsgxBX8CZAarZAbDkllOe5rkXFZAfW29EnDKf7S68aVZC4Y4zvyswEGiLns1JMkp2iNPRYm5ZCoTgUFUyz2k6cfnlGNzHFAWAhRtYcYAZC8BlkBxKbpNj1cPU4jSdeXLeeRDwEoLXySRidMrUQVz2TrtR8gIe1AelIQWfqYOVPowDqosS10Y2GJjCO'),
('location_2_facebook_page_id', '482777831588669'),
('location_2_facebook_token', 'EAAPYJ3JWk0UBPhHoFrglY8vNF9Jrm40RdjCvuPkYB0mO226K3yqF5qQrZAUasvkmAidLqK87dTZCCRyVwpMReuR5EKscMKwJjoAFZAiTUwjMSjLdstz15BmWr6QQfJR8YBKZAkBy0ksMHXwdvL8vzZAUpoF5K7osglWrLVQZB91xbtFJnUkw2MZCMhZAB6YRMATSJc46'),
-- Default schedule time
('facebook_default_schedule_time', '20:00');

-- 2. Create scheduled Facebook posts table
CREATE TABLE IF NOT EXISTS gh3sp_scheduled_facebook_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    car_id INT NOT NULL,
    catalog_type ENUM('in_stock', 'on_order') NOT NULL DEFAULT 'in_stock',
    scheduled_date DATE NOT NULL,
    scheduled_time TIME NOT NULL DEFAULT '20:00:00',
    status ENUM('pending', 'published', 'failed') NOT NULL DEFAULT 'pending',
    facebook_post_id VARCHAR(255) NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL,
    INDEX idx_schedule (scheduled_date, scheduled_time, status),
    INDEX idx_car (car_id, catalog_type),
    INDEX idx_status (status)
);

-- 3. Show current Facebook settings
SELECT 'Current Facebook Settings:' as info;
SELECT name, 
       CASE 
           WHEN name LIKE '%token%' THEN CONCAT(LEFT(value, 20), '...')
           ELSE value 
       END as value 
FROM gh3sp_settings 
WHERE name LIKE '%facebook%' 
ORDER BY name;
