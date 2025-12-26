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
-- Gasoline
('benzina', 0, 1000, 0, 2, 7.53),
('benzina', 0, 1000, 3, 4, 9.41),
('benzina', 0, 1000, 5, 6, 11.30),
('benzina', 0, 1000, 7, 7, 13.18),
('benzina', 0, 1000, 8, 8, 15.06),
('benzina', 0, 1000, 9, 9, 16.94),
('benzina', 0, 1000, 10, 0, 18.83),

('benzina', 1001, 1500, 0, 2, 11.30),
('benzina', 1001, 1500, 3, 4, 14.12),
('benzina', 1001, 1500, 5, 6, 16.94),
('benzina', 1001, 1500, 7, 7, 19.77),
('benzina', 1001, 1500, 8, 8, 22.59),
('benzina', 1001, 1500, 9, 9, 25.41),
('benzina', 1001, 1500, 10, 0, 28.24),

('benzina', 1501, 2000, 0, 2, 18.83),
('benzina', 1501, 2000, 3, 4, 23.53),
('benzina', 1501, 2000, 5, 6, 28.24),
('benzina', 1501, 2000, 7, 7, 32.94),
('benzina', 1501, 2000, 8, 8, 37.65),
('benzina', 1501, 2000, 9, 9, 42.36),
('benzina', 1501, 2000, 10, 0, 47.06),

('benzina', 2001, 2500, 0, 2, 26.36),
('benzina', 2001, 2500, 3, 4, 32.94),
('benzina', 2001, 2500, 5, 6, 39.53),
('benzina', 2001, 2500, 7, 7, 46.12),
('benzina', 2001, 2500, 8, 8, 52.71),
('benzina', 2001, 2500, 9, 9, 59.30),
('benzina', 2001, 2500, 10, 0, 65.89),

('benzina', 2501, 3000, 0, 2, 37.65),
('benzina', 2501, 3000, 3, 4, 47.06),
('benzina', 2501, 3000, 5, 6, 56.48),
('benzina', 2501, 3000, 7, 7, 65.89),
('benzina', 2501, 3000, 8, 8, 75.30),
('benzina', 2501, 3000, 9, 9, 84.71),
('benzina', 2501, 3000, 10, 0, 94.13),

('benzina', 3001, 0, 0, 2, 56.48),
('benzina', 3001, 0, 3, 4, 70.59),
('benzina', 3001, 0, 5, 6, 84.71),
('benzina', 3001, 0, 7, 7, 98.83),
('benzina', 3001, 0, 8, 8, 112.95),
('benzina', 3001, 0, 9, 9, 127.07),
('benzina', 3001, 0, 10, 0, 141.19),

-- Diesel
('diesel', 0, 1500, 0, 2, 12.61),
('diesel', 0, 1500, 3, 4, 15.76),
('diesel', 0, 1500, 5, 6, 18.90),
('diesel', 0, 1500, 7, 7, 22.06),
('diesel', 0, 1500, 8, 8, 25.21),
('diesel', 0, 1500, 9, 9, 28.36),
('diesel', 0, 1500, 10, 0, 31.52),

('diesel', 1501, 2000, 0, 2, 21.01),
('diesel', 1501, 2000, 3, 4, 26.26),
('diesel', 1501, 2000, 5, 6, 31.52),
('diesel', 1501, 2000, 7, 7, 36.76),
('diesel', 1501, 2000, 8, 8, 42.02),
('diesel', 1501, 2000, 9, 9, 47.27),
('diesel', 1501, 2000, 10, 0, 52.52),

('diesel', 2001, 2500, 0, 2, 25.21),
('diesel', 2001, 2500, 3, 4, 31.52),
('diesel', 2001, 2500, 5, 6, 37.82),
('diesel', 2001, 2500, 7, 7, 44.12),
('diesel', 2001, 2500, 8, 8, 50.42),
('diesel', 2001, 2500, 9, 9, 56.73),
('diesel', 2001, 2500, 10, 0, 63.03),

('diesel', 2501, 3000, 0, 2, 33.61),
('diesel', 2501, 3000, 3, 4, 42.02),
('diesel', 2501, 3000, 5, 6, 50.42),
('diesel', 2501, 3000, 7, 7, 58.82),
('diesel', 2501, 3000, 8, 8, 67.23),
('diesel', 2501, 3000, 9, 9, 75.63),
('diesel', 2501, 3000, 10, 0, 84.03),

('diesel', 3001, 0, 0, 2, 50.42),
('diesel', 3001, 0, 3, 4, 63.03),
('diesel', 3001, 0, 5, 6, 75.63),
('diesel', 3001, 0, 7, 7, 88.24),
('diesel', 3001, 0, 8, 8, 100.84),
('diesel', 3001, 0, 9, 9, 113.45),
('diesel', 3001, 0, 10, 0, 126.05),

-- Motorcycles
('motocicleta', 0, 250, 0, 2, 3.77),
('motocicleta', 0, 250, 3, 4, 4.71),
('motocicleta', 0, 250, 5, 6, 5.65),
('motocicleta', 0, 250, 7, 0, 7.53),

('motocicleta', 251, 500, 0, 2, 5.65),
('motocicleta', 251, 500, 3, 4, 7.07),
('motocicleta', 251, 500, 5, 6, 8.47),
('motocicleta', 251, 500, 7, 0, 11.30),

('motocicleta', 501, 800, 0, 2, 7.53),
('motocicleta', 501, 800, 3, 4, 9.41),
('motocicleta', 501, 800, 5, 6, 11.30),
('motocicleta', 501, 800, 7, 0, 15.06),

('motocicleta', 801, 0, 0, 2, 11.30),
('motocicleta', 801, 0, 3, 4, 14.12),
('motocicleta', 801, 0, 5, 6, 16.94),
('motocicleta', 801, 0, 7, 0, 22.59);

-- Default settings
INSERT INTO `gh3sp_calculator_settings` (`setting_key`, `setting_value`, `description`) VALUES
('tva_rate', '20', 'TVA rate in percentage'),
('customs_duty_rate', '0', 'Customs duty rate in percentage (usually 0 for cars from EU)'),
('hybrid_discount_full', '25', 'Discount for Full Hybrid in percentage'),
('hybrid_discount_plugin', '50', 'Discount for Plug-in Hybrid in percentage'),
('hybrid_discount_mild', '0', 'Discount for Mild Hybrid in percentage'),
('vintage_30_39_rate', '40000', 'Fixed excise for vintage cars 30-39 years old'),
('vintage_40_49_rate', '30000', 'Fixed excise for vintage cars 40-49 years old'),
('vintage_50_plus_rate', '20000', 'Fixed excise for vintage cars 50+ years old');
