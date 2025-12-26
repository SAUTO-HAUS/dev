CREATE TABLE IF NOT EXISTS `gh3sp_calculator_usage_log` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for storing excise rates (editable by super admin)
CREATE TABLE IF NOT EXISTS `gh3sp_calculator_excise_rates` (
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
CREATE TABLE IF NOT EXISTS `gh3sp_calculator_settings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `updated_by` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `gh3sp_calculator_excise_rates` (`fuel_type`, `capacity_min`, `capacity_max`, `age_min`, `age_max`, `rate`) VALUES
-- Benzină rates by capacity and age (MDL/cm³) - conform Codului Fiscal RM 2024
-- 0-1000 cm³
('benzina', 0, 1000, 0, 2, 6.00),
('benzina', 0, 1000, 3, 4, 7.50),
('benzina', 0, 1000, 5, 6, 9.00),
('benzina', 0, 1000, 7, 7, 10.50),
('benzina', 0, 1000, 8, 8, 12.00),
('benzina', 0, 1000, 9, 9, 13.50),
('benzina', 0, 1000, 10, 0, 15.00),

-- 1001-1500 cm³
('benzina', 1001, 1500, 0, 2, 9.00),
('benzina', 1001, 1500, 3, 4, 11.25),
('benzina', 1001, 1500, 5, 6, 13.50),
('benzina', 1001, 1500, 7, 7, 15.75),
('benzina', 1001, 1500, 8, 8, 18.00),
('benzina', 1001, 1500, 9, 9, 20.25),
('benzina', 1001, 1500, 10, 0, 22.50),

-- 1501-2000 cm³
('benzina', 1501, 2000, 0, 2, 15.00),
('benzina', 1501, 2000, 3, 4, 18.75),
('benzina', 1501, 2000, 5, 6, 22.50),
('benzina', 1501, 2000, 7, 7, 26.25),
('benzina', 1501, 2000, 8, 8, 30.00),
('benzina', 1501, 2000, 9, 9, 33.75),
('benzina', 1501, 2000, 10, 0, 37.50),

-- 2001-2500 cm³
('benzina', 2001, 2500, 0, 2, 21.00),
('benzina', 2001, 2500, 3, 4, 26.25),
('benzina', 2001, 2500, 5, 6, 31.50),
('benzina', 2001, 2500, 7, 7, 36.75),
('benzina', 2001, 2500, 8, 8, 42.00),
('benzina', 2001, 2500, 9, 9, 47.25),
('benzina', 2001, 2500, 10, 0, 52.50),

-- 2501-3000 cm³
('benzina', 2501, 3000, 0, 2, 30.00),
('benzina', 2501, 3000, 3, 4, 37.50),
('benzina', 2501, 3000, 5, 6, 45.00),
('benzina', 2501, 3000, 7, 7, 52.50),
('benzina', 2501, 3000, 8, 8, 60.00),
('benzina', 2501, 3000, 9, 9, 67.50),
('benzina', 2501, 3000, 10, 0, 75.00),

-- peste 3000 cm³
('benzina', 3001, 0, 0, 2, 45.00),
('benzina', 3001, 0, 3, 4, 56.25),
('benzina', 3001, 0, 5, 6, 67.50),
('benzina', 3001, 0, 7, 7, 78.75),
('benzina', 3001, 0, 8, 8, 90.00),
('benzina', 3001, 0, 9, 9, 101.25),
('benzina', 3001, 0, 10, 0, 112.50),

-- Diesel rates by capacity and age (MDL/cm³) - conform Codului Fiscal RM 2024
-- 0-1500 cm³
('diesel', 0, 1500, 0, 2, 9.00),
('diesel', 0, 1500, 3, 4, 11.25),
('diesel', 0, 1500, 5, 6, 13.50),
('diesel', 0, 1500, 7, 7, 15.75),
('diesel', 0, 1500, 8, 8, 18.00),
('diesel', 0, 1500, 9, 9, 20.25),
('diesel', 0, 1500, 10, 0, 22.50),

-- 1501-2000 cm³
('diesel', 1501, 2000, 0, 2, 15.00),
('diesel', 1501, 2000, 3, 4, 18.75),
('diesel', 1501, 2000, 5, 6, 22.50),
('diesel', 1501, 2000, 7, 7, 26.25),
('diesel', 1501, 2000, 8, 8, 30.00),
('diesel', 1501, 2000, 9, 9, 33.75),
('diesel', 1501, 2000, 10, 0, 37.50),

-- 2001-2500 cm³
('diesel', 2001, 2500, 0, 2, 18.00),
('diesel', 2001, 2500, 3, 4, 22.50),
('diesel', 2001, 2500, 5, 6, 27.00),
('diesel', 2001, 2500, 7, 7, 31.50),
('diesel', 2001, 2500, 8, 8, 36.00),
('diesel', 2001, 2500, 9, 9, 40.50),
('diesel', 2001, 2500, 10, 0, 45.00),

-- 2501-3000 cm³
('diesel', 2501, 3000, 0, 2, 24.00),
('diesel', 2501, 3000, 3, 4, 30.00),
('diesel', 2501, 3000, 5, 6, 36.00),
('diesel', 2501, 3000, 7, 7, 42.00),
('diesel', 2501, 3000, 8, 8, 48.00),
('diesel', 2501, 3000, 9, 9, 54.00),
('diesel', 2501, 3000, 10, 0, 60.00),

-- peste 3000 cm³
('diesel', 3001, 0, 0, 2, 36.00),
('diesel', 3001, 0, 3, 4, 45.00),
('diesel', 3001, 0, 5, 6, 54.00),
('diesel', 3001, 0, 7, 7, 63.00),
('diesel', 3001, 0, 8, 8, 72.00),
('diesel', 3001, 0, 9, 9, 81.00),
('diesel', 3001, 0, 10, 0, 90.00),

-- Motociclete rates by capacity and age (MDL/cm³)
('motocicleta', 0, 250, 0, 2, 3.00),
('motocicleta', 0, 250, 3, 4, 3.75),
('motocicleta', 0, 250, 5, 6, 4.50),
('motocicleta', 0, 250, 7, 0, 6.00),

('motocicleta', 251, 500, 0, 2, 4.50),
('motocicleta', 251, 500, 3, 4, 5.63),
('motocicleta', 251, 500, 5, 6, 6.75),
('motocicleta', 251, 500, 7, 0, 9.00),

('motocicleta', 501, 800, 0, 2, 6.00),
('motocicleta', 501, 800, 3, 4, 7.50),
('motocicleta', 501, 800, 5, 6, 9.00),
('motocicleta', 501, 800, 7, 0, 12.00),

('motocicleta', 801, 0, 0, 2, 9.00),
('motocicleta', 801, 0, 3, 4, 11.25),
('motocicleta', 801, 0, 5, 6, 13.50),
('motocicleta', 801, 0, 7, 0, 18.00);

-- Insert default settings
INSERT INTO `gh3sp_calculator_settings` (`setting_key`, `setting_value`, `description`) VALUES
('tva_rate', '20', 'TVA rate in percentage'),
('customs_duty_rate', '0', 'Customs duty rate in percentage (usually 0 for cars from EU)'),
('hybrid_discount_full', '25', 'Discount for Full Hybrid in percentage'),
('hybrid_discount_plugin', '50', 'Discount for Plug-in Hybrid in percentage'),
('hybrid_discount_mild', '0', 'Discount for Mild Hybrid in percentage'),
('vintage_30_39_rate', '40000', 'Fixed excise for vintage cars 30-39 years old'),
('vintage_40_49_rate', '30000', 'Fixed excise for vintage cars 40-49 years old'),
('vintage_50_plus_rate', '20000', 'Fixed excise for vintage cars 50+ years old');
