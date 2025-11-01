-- 1. Add missing Facebook settings 
INSERT IGNORE INTO gh3sp_settings (name, value) VALUES
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

