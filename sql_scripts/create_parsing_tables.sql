-- =====================================================================
-- UPDATE gh3sp_car_ctlg
-- =====================================================================

ALTER TABLE `gh3sp_car_ctlg`
    ADD COLUMN `parsing_id` INT(11) DEFAULT NULL;

ALTER TABLE `gh3sp_car_ctlg`
    ADD COLUMN `parsing_source` VARCHAR(50) DEFAULT NULL;

ALTER TABLE `gh3sp_car_ctlg`
    ADD INDEX `idx_parsing_id` (`parsing_id`);

ALTER TABLE `gh3sp_car_ctlg`
    ADD INDEX `idx_parsing_source` (`parsing_source`);
-- =====================================================================
-- END UPDATE
-- =====================================================================

CREATE TABLE IF NOT EXISTS `gh3sp_parsing_filters` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `sources` VARCHAR(100) NOT NULL,
    `brand` VARCHAR(100) DEFAULT NULL,
    `model` VARCHAR(100) DEFAULT NULL,
    `year_from` INT(11) DEFAULT NULL,
    `year_to` INT(11) DEFAULT NULL,
    `km_max` INT(11) DEFAULT NULL,
    `price_max` INT(11) DEFAULT NULL,
    `fuel_type` VARCHAR(50) DEFAULT NULL,
    `gearbox` VARCHAR(50) DEFAULT NULL,
    `drive_type` VARCHAR(50) DEFAULT NULL,
    `criteria_extra` JSON DEFAULT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_run_at` DATETIME DEFAULT NULL,
    `total_found` INT(11) NOT NULL DEFAULT 0,
    `total_imported` INT(11) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_active` (`active`),
    INDEX `idx_last_run` (`last_run_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gh3sp_parsing_runs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `filter_id` INT(11) NOT NULL,
    `source` VARCHAR(50) NOT NULL,
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `finished_at` DATETIME DEFAULT NULL,
    `status` ENUM('running','success','failed','partial') NOT NULL DEFAULT 'running',
    `found_count` INT(11) NOT NULL DEFAULT 0,
    `imported_count` INT(11) NOT NULL DEFAULT 0,
    `skipped_count` INT(11) NOT NULL DEFAULT 0,
    `error_message` TEXT DEFAULT NULL,
    `duration_ms` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_filter_id` (`filter_id`),
    INDEX `idx_source` (`source`),
    INDEX `idx_started_at` (`started_at`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gh3sp_parsing_cars` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `filter_id` INT(11) DEFAULT NULL,
    `source` VARCHAR(50) NOT NULL,
    `source_id` VARCHAR(100) NOT NULL,
    `source_url` TEXT DEFAULT NULL,
    `vin` VARCHAR(20) DEFAULT NULL,
    `brand` VARCHAR(100) DEFAULT NULL,
    `model` VARCHAR(150) DEFAULT NULL,
    `year` INT(11) DEFAULT NULL,
    `km` INT(11) DEFAULT NULL,
    `fuel_type` VARCHAR(50) DEFAULT NULL,
    `gearbox` VARCHAR(50) DEFAULT NULL,
    `engine_volume` INT(11) DEFAULT NULL,
    `power_hp` INT(11) DEFAULT NULL,
    `color` VARCHAR(50) DEFAULT NULL,
    `body_type` VARCHAR(50) DEFAULT NULL,
    `price_source` DECIMAL(12,2) DEFAULT NULL,
    `price_source_currency` VARCHAR(10) DEFAULT NULL,
    `price_eur` DECIMAL(12,2) DEFAULT NULL,
    `price_final_eur` DECIMAL(12,2) DEFAULT NULL,
    `price_breakdown` JSON DEFAULT NULL,
    `title_ro` TEXT DEFAULT NULL,
    `description_ro` TEXT DEFAULT NULL,
    `features_ro` JSON DEFAULT NULL,
    `report_data` JSON DEFAULT NULL,
    `images_local` JSON DEFAULT NULL,
    `raw_data` JSON DEFAULT NULL,
    `status` ENUM('proposed','published','rejected','unavailable') NOT NULL DEFAULT 'proposed',
    `published_sauto` TINYINT(1) NOT NULL DEFAULT 0,
    `published_999` TINYINT(1) NOT NULL DEFAULT 0,
    `published_fb` TINYINT(1) NOT NULL DEFAULT 0,
    `published_tg` TINYINT(1) NOT NULL DEFAULT 0,
    `car_ctlg_id` INT(11) DEFAULT NULL,
    `ad_999_id` VARCHAR(100) DEFAULT NULL,
    `last_checked_at` DATETIME DEFAULT NULL,
    `found_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `published_at` DATETIME DEFAULT NULL,
    `rejected_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_source_id` (`source`, `source_id`),
    INDEX `idx_filter_id` (`filter_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_vin` (`vin`),
    INDEX `idx_car_ctlg_id` (`car_ctlg_id`),
    INDEX `idx_found_at` (`found_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gh3sp_parsing_credentials` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `source` VARCHAR(50) NOT NULL,
    `username_encrypted` TEXT NOT NULL,
    `password_encrypted` TEXT NOT NULL,
    `api_key_encrypted` TEXT DEFAULT NULL,
    `extra_data` JSON DEFAULT NULL,
    `last_login_at` DATETIME DEFAULT NULL,
    `last_error` TEXT DEFAULT NULL,
    `status` ENUM('active','blocked','expired','error') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_source` (`source`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gh3sp_parsing_price_config` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `country_code` VARCHAR(10) NOT NULL,
    `country_name` VARCHAR(100) NOT NULL,
    `delivery_to_moldova` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `inspection` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `evacuator_source_country` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `dealer_commission` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `broker_service` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `evacuator_chisinau` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `interpol_check` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `documents_processing` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `recycling_tax` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `oil_change` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `cleaning` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `extra_costs` JSON DEFAULT NULL,
    `markup_percent` DECIMAL(5,2) NOT NULL DEFAULT 0,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `updated_by` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_country_code` (`country_code`),
    INDEX `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gh3sp_parsing_settings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `updated_by` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `gh3sp_parsing_price_config`
(`country_code`, `country_name`, `delivery_to_moldova`, `inspection`, `evacuator_source_country`,
 `dealer_commission`, `broker_service`, `evacuator_chisinau`, `interpol_check`,
 `documents_processing`, `recycling_tax`, `oil_change`, `cleaning`, `markup_percent`)
VALUES
('KR', 'Coreea de Sud', 1850, 100, 139, 300, 50, 55, 70, 320, 51, 150, 60, 15),
('BE', 'Belgia', 800, 80, 100, 250, 50, 55, 70, 320, 51, 150, 60, 15),
('NL', 'Olanda', 850, 80, 100, 250, 50, 55, 70, 320, 51, 150, 60, 15),
('DE', 'Germania', 750, 80, 100, 250, 50, 55, 70, 320, 51, 150, 60, 15),
('FR', 'Franta', 900, 80, 100, 250, 50, 55, 70, 320, 51, 150, 60, 15),
('IT', 'Italia', 950, 80, 100, 250, 50, 55, 70, 320, 51, 150, 60, 15);

INSERT INTO `gh3sp_parsing_settings` (`setting_key`, `setting_value`, `description`) VALUES
('cron_frequency_minutes', '60', 'Worker run cadence in minutes'),
('default_target_999', '1', 'Auto-publish on 999.md (1 = yes, 0 = no)'),
('default_target_facebook', '1', 'Auto-publish on Facebook'),
('default_target_telegram', '1', 'Auto-publish on Telegram'),
('notification_email', '', 'Email address that receives new-car notifications'),
('notification_telegram_chat_id', '', 'Telegram chat id that receives new-car notifications'),
('moderation_required', '1', 'When 1, new cars sit in the proposed list until the user clicks Publish'),
('import_user_id', '1', 'Owner user id assigned to listings created from imports'),
('libretranslate_url', 'http://localhost:5000', 'LibreTranslate self-hosted endpoint'),
('libretranslate_enabled', '0', 'When 0 the pipeline keeps original text, when 1 it translates to RO'),
('encryption_key', '', 'AES-256-CBC key for the credentials table (32 chars)'),
('sync_nocturn_hour', '2', 'Hour of the day (0-23) the nightly sync worker runs');

-- Add 'favorite' to parsing_cars status enum.
ALTER TABLE gh3sp_parsing_cars
    MODIFY COLUMN `status` ENUM('proposed', 'favorite', 'published', 'rejected', 'unavailable')
    NOT NULL DEFAULT 'proposed';

    -- Add seats and drive_type columns so parsing can store them per car.
ALTER TABLE gh3sp_parsing_cars
    ADD COLUMN `seats` INT(3) DEFAULT NULL AFTER `power_hp`,
    ADD COLUMN `drive_type` VARCHAR(20) DEFAULT NULL AFTER `seats`;

    
    -- Track pagination offset per filter so repeated runs fetch new pages from Encar.
ALTER TABLE gh3sp_parsing_filters
    ADD COLUMN `last_offset` INT(11) NOT NULL DEFAULT 0 AFTER `last_run_at`;

    -- Track whether a parsing run was triggered manually (from the admin UI) or
-- automatically (by the cron). Shown in /parsing/logs.
ALTER TABLE `gh3sp_parsing_runs`
    ADD COLUMN `run_type` ENUM('manual','cron') NOT NULL DEFAULT 'cron' AFTER `source`;

-- separatttttttttttttttttttttttttttttttttttttttttttttt
-- =====================================================================
-- Europe price tiers: delivery + commission selected by the car price.
-- =====================================================================
CREATE TABLE IF NOT EXISTS `gh3sp_parsing_eu_tiers` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `price_from` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `price_to` DECIMAL(12,2) DEFAULT NULL COMMENT 'NULL = no upper limit',
    `delivery` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `commission` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `updated_by` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `gh3sp_parsing_eu_tiers` (`price_from`, `price_to`, `delivery`, `commission`, `sort_order`) VALUES
(0,     15000, 800,  600,  1),
(15001, 24000, 1000, 800,  2),
(24001, 35000, 1200, 1000, 3),
(35001, 55000, 1500, 1300, 4),
(55001, NULL,  2000, 1600, 5);

-- =====================================================================
-- Europe fixed cost parameters (toggleable, editable amount in EUR).
-- =====================================================================
CREATE TABLE IF NOT EXISTS `gh3sp_parsing_eu_params` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `param_key` VARCHAR(100) NOT NULL,
    `value_type` ENUM('fixed','percent') NOT NULL DEFAULT 'fixed' COMMENT 'fixed = amount_eur in EUR, percent = amount_eur is a % of car price',
    `amount_eur` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `updated_by` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_param_key` (`param_key`),
    INDEX `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Only FIXED costs live here. Excise, customs duty and damage protection are
-- computed per-car by the calculator (/calculator/calc), not stored as fixed.
INSERT INTO `gh3sp_parsing_eu_params` (`param_key`, `value_type`, `amount_eur`, `enabled`, `sort_order`) VALUES
('export_declaration', 'fixed', 50,  1, 1),
('bank_commission',    'fixed', 25,  1, 2),
('auction_commission', 'fixed', 350, 1, 3),
('pollution_tax',      'fixed', 85,  1, 4),
('shipping_docs',      'fixed', 20,  1, 5);

-- =====================================================================
-- Shared sauto commission tiers, selected by car price.
-- Used by BOTH Europe and Korea (values coincide).
-- =====================================================================
CREATE TABLE IF NOT EXISTS `gh3sp_parsing_commission_tiers` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `price_from` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `price_to` DECIMAL(12,2) DEFAULT NULL COMMENT 'NULL = no upper limit',
    `commission` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `updated_by` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `gh3sp_parsing_commission_tiers` (`price_from`, `price_to`, `commission`, `sort_order`) VALUES
(0,     15000, 600,  1),
(15001, 24000, 800,  2),
(24001, 35000, 1000, 3),
(35001, 55000, 1300, 4),
(55001, NULL,  1600, 5);

-- =====================================================================
-- Korea fixed cost parameters (toggleable, editable amount in EUR).
-- value_type percent = % of car price.
-- =====================================================================
CREATE TABLE IF NOT EXISTS `gh3sp_parsing_kr_params` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `param_key` VARCHAR(100) NOT NULL,
    `value_type` ENUM('fixed','percent') NOT NULL DEFAULT 'fixed' COMMENT 'fixed = amount_eur in EUR, percent = amount_eur is a % of car price',
    `amount_eur` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `updated_by` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_param_key` (`param_key`),
    INDEX `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `gh3sp_parsing_kr_params` (`param_key`, `value_type`, `amount_eur`, `enabled`, `sort_order`) VALUES
('auction_fee_encar',  'fixed', 305,  1, 1),
('delivery_incheon',   'fixed', 240,  1, 2),
('inspection',         'fixed', 100,  1, 3),
('broker_korea',       'fixed', 50,   1, 4),
('sea_freight_roro',   'fixed', 2000, 1, 5),
('interpol_check',     'fixed', 50,   1, 6),
('recycling_tax',      'fixed', 51,   1, 7);

-- Background publish queue: clicking "Publică" enqueues a job here; a worker
-- (console/parsing_publish_worker.php, kicked off web-side + a safety cron)
-- processes them sequentially via ParsingPublisher, so the operator can leave
-- the page and the work continues server-side without freezing the browser.
CREATE TABLE IF NOT EXISTS `gh3sp_parsing_publish_queue` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `parsing_car_id` INT(11) NOT NULL,
    `target` VARCHAR(20) NOT NULL DEFAULT 'sauto',
    `status` ENUM('pending','processing','done','failed') NOT NULL DEFAULT 'pending',
    `attempts` INT(11) NOT NULL DEFAULT 0,
    `error` TEXT DEFAULT NULL,
    `car_ctlg_id` INT(11) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `started_at` DATETIME DEFAULT NULL,
    `finished_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    -- One live job per car+target: a car can't sit in the queue twice. Done/
    -- failed rows are cleared before re-enqueue (see PublishQueue::enqueue).
    UNIQUE KEY `uq_car_target` (`parsing_car_id`, `target`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Korea (Encar) price markup tiers.
-- A flat amount added ON TOP of the EUR-converted car price, by price band.
-- The markup becomes the displayed car price AND the base for the MD breakdown.
-- =====================================================================
CREATE TABLE IF NOT EXISTS `gh3sp_parsing_kr_markup_tiers` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `price_from` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `price_to` DECIMAL(12,2) DEFAULT NULL COMMENT 'NULL = no upper limit',
    `markup` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `updated_by` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `gh3sp_parsing_kr_markup_tiers` (`price_from`, `price_to`, `markup`, `sort_order`) VALUES
(0,     10000, 400,  1),
(10001, 15000, 600,  2),
(15001, 20000, 700,  3),
(20001, 30000, 800,  4),
(30001, 40000, 1000, 5),
(40001, NULL,  1500, 6);