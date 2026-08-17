CREATE TABLE IF NOT EXISTS `gh3sp_cookie_consent_logs` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `consent_id` VARCHAR(36) NOT NULL,
    `action_timestamp` DATETIME NOT NULL,
    `global_status` ENUM('accept_all','reject_all','custom_selection') NOT NULL,
    `tracker_analytics` TINYINT(1) NOT NULL DEFAULT 0,
    `tracker_marketing` TINYINT(1) NOT NULL DEFAULT 0,
    `policy_version` VARCHAR(16) NOT NULL DEFAULT 'v1.0',

    PRIMARY KEY (`id`),
    KEY `idx_consent_id` (`consent_id`),
    KEY `idx_timestamp` (`action_timestamp`),
    KEY `idx_status` (`global_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Marketing opt-in columns (Legea 195/2024, Cap. 4).
--
-- Sending future offers is a purpose separate from answering the enquiry, so
-- it needs its own consent — captured by an optional, never pre-ticked box.
-- The timestamp is the proof of when that consent was given.
--
-- MariaDB/MySQL has no "ADD COLUMN IF NOT EXISTS" everywhere, so each block is
-- guarded through information_schema. Safe to re-run.
-- =====================================================================

-- --- contact / order forms (gh3sp_mail) ------------------------------------

SET @s := (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gh3sp_mail' AND COLUMN_NAME = 'marketing_optin') > 0,
    'SELECT 1',
    'ALTER TABLE `gh3sp_mail` ADD COLUMN `marketing_optin` TINYINT(1) NOT NULL DEFAULT 0'
));
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s := (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gh3sp_mail' AND COLUMN_NAME = 'marketing_optin_at') > 0,
    'SELECT 1',
    'ALTER TABLE `gh3sp_mail` ADD COLUMN `marketing_optin_at` DATETIME NULL DEFAULT NULL'
));
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- --- B2B account signup (gh3sp_b2b_users) ----------------------------------

SET @s := (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gh3sp_b2b_users' AND COLUMN_NAME = 'marketing_accepted') > 0,
    'SELECT 1',
    'ALTER TABLE `gh3sp_b2b_users` ADD COLUMN `marketing_accepted` TINYINT(1) NOT NULL DEFAULT 0'
));
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s := (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gh3sp_b2b_users' AND COLUMN_NAME = 'marketing_accepted_at') > 0,
    'SELECT 1',
    'ALTER TABLE `gh3sp_b2b_users` ADD COLUMN `marketing_accepted_at` DATETIME NULL DEFAULT NULL'
));
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

