CREATE TABLE IF NOT EXISTS `sa_calculator_usage_log` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for storing excise rates (editable by super admin)
CREATE TABLE IF NOT EXISTS `sa_calculator_excise_rates` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `fuel_type` VARCHAR(50) NOT NULL COMMENT 'benzina, diesel',
    `capacity_min` INT(11) NOT NULL COMMENT 'Minimum cylinder capacity in cm3',
    `capacity_max` INT(11) NOT NULL COMMENT 'Maximum cylinder capacity in cm3 (0 = unlimited)',
    `age_min` INT(11) NOT NULL COMMENT 'Minimum vehicle age in years',
    `age_max` INT(11) NOT NULL COMMENT 'Maximum vehicle age in years (0 = unlimited)',
    `rate` DECIMAL(10,2) NOT NULL COMMENT 'Rate in MDL per cm3',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `updated_by` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_fuel_type` (`fuel_type`),
    INDEX `idx_capacity` (`capacity_min`, `capacity_max`),
    INDEX `idx_age` (`age_min`, `age_max`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for storing calculator settings
CREATE TABLE IF NOT EXISTS `sa_calculator_settings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `updated_by` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sa_calculator_excise_rates` (`fuel_type`, `capacity_min`, `capacity_max`, `age_min`, `age_max`, `rate`) VALUES
-- Benzină rates by capacity and age
('benzina', 0, 1000, 0, 3, 0.30),
('benzina', 0, 1000, 3, 5, 0.35),
('benzina', 0, 1000, 5, 7, 0.50),
('benzina', 0, 1000, 7, 0, 0.70),

('benzina', 1001, 1500, 0, 3, 0.35),
('benzina', 1001, 1500, 3, 5, 0.45),
('benzina', 1001, 1500, 5, 7, 0.65),
('benzina', 1001, 1500, 7, 0, 0.90),

('benzina', 1501, 2000, 0, 3, 0.50),
('benzina', 1501, 2000, 3, 5, 0.70),
('benzina', 1501, 2000, 5, 7, 1.00),
('benzina', 1501, 2000, 7, 0, 1.40),

('benzina', 2001, 2500, 0, 3, 0.80),
('benzina', 2001, 2500, 3, 5, 1.10),
('benzina', 2001, 2500, 5, 7, 1.60),
('benzina', 2001, 2500, 7, 0, 2.20),

('benzina', 2501, 3000, 0, 3, 1.30),
('benzina', 2501, 3000, 3, 5, 1.80),
('benzina', 2501, 3000, 5, 7, 2.60),
('benzina', 2501, 3000, 7, 0, 3.60),

('benzina', 3001, 0, 0, 3, 2.00),
('benzina', 3001, 0, 3, 5, 2.80),
('benzina', 3001, 0, 5, 7, 4.00),
('benzina', 3001, 0, 7, 0, 5.50),

-- Diesel rates by capacity and age
('diesel', 0, 1500, 0, 3, 0.35),
('diesel', 0, 1500, 3, 5, 0.50),
('diesel', 0, 1500, 5, 7, 0.70),
('diesel', 0, 1500, 7, 0, 1.00),

('diesel', 1501, 2000, 0, 3, 0.55),
('diesel', 1501, 2000, 3, 5, 0.80),
('diesel', 1501, 2000, 5, 7, 1.10),
('diesel', 1501, 2000, 7, 0, 1.60),

('diesel', 2001, 2500, 0, 3, 0.90),
('diesel', 2001, 2500, 3, 5, 1.30),
('diesel', 2001, 2500, 5, 7, 1.80),
('diesel', 2001, 2500, 7, 0, 2.50),

('diesel', 2501, 3000, 0, 3, 1.50),
('diesel', 2501, 3000, 3, 5, 2.10),
('diesel', 2501, 3000, 5, 7, 3.00),
('diesel', 2501, 3000, 7, 0, 4.20),

('diesel', 3001, 0, 0, 3, 2.30),
('diesel', 3001, 0, 3, 5, 3.20),
('diesel', 3001, 0, 5, 7, 4.60),
('diesel', 3001, 0, 7, 0, 6.40);

-- Insert default settings
INSERT INTO `sa_calculator_settings` (`setting_key`, `setting_value`, `description`) VALUES
('tva_rate', '20', 'TVA rate in percentage'),
('customs_duty_rate', '0', 'Customs duty rate in percentage (usually 0 for cars from EU)'),
('hybrid_discount_full', '25', 'Discount for Full Hybrid in percentage'),
('hybrid_discount_plugin', '50', 'Discount for Plug-in Hybrid in percentage'),
('hybrid_discount_mild', '0', 'Discount for Mild Hybrid in percentage'),
('vintage_30_39_rate', '40000', 'Fixed excise for vintage cars 30-39 years old'),
('vintage_40_49_rate', '30000', 'Fixed excise for vintage cars 40-49 years old'),
('vintage_50_plus_rate', '20000', 'Fixed excise for vintage cars 50+ years old');
