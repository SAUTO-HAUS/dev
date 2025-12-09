CREATE TABLE IF NOT EXISTS `gh3sp_brands_seo` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `brand_code` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Brand code from _car_list table (e.g., bmw, mercedes_benz)',
  `brand_name` VARCHAR(100) COMMENT 'Brand display name for reference',
  `description_ro` TEXT COMMENT 'SEO description in Romanian (HTML supported)',
  `description_ru` TEXT COMMENT 'SEO description in Russian (HTML supported)',
  `description_en` TEXT COMMENT 'SEO description in English (HTML supported)',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_brand_code` (`brand_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Brand SEO descriptions for brand pages footer';

INSERT INTO `gh3sp_brands_seo` (`brand_code`, `brand_name`, `description_ro`, `description_ru`, `description_en`)
SELECT DISTINCT `br` as brand_code, `br_nm` as brand_name, NULL, NULL, NULL
FROM `gh3sp_car_list`
WHERE `br` IS NOT NULL AND `br` != '' AND `br_nm` IS NOT NULL AND `br_nm` != ''
ORDER BY `br_nm`
ON DUPLICATE KEY UPDATE `brand_name` = VALUES(`brand_name`);
