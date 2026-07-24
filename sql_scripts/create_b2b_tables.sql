-- =====================================================================
-- B2B MODULE (sauto.md): partner accounts with Super Admin approval,
-- region permissions, audit trail, proformas and Super Admin requests.
--
-- Approval by the Super Admin is the only gate: no SMS, no OTP, no 2FA.
-- Idempotent: safe to run more than once.
-- =====================================================================


-- ---------------------------------------------------------------------
-- B2B accounts.
-- A dedicated table, NOT an extension of gh3sp_adm_usr: that one serves the
-- admin panel with different role semantics, and mixing the two would turn any
-- flaw in the public B2B flow into a path to admin.
--
-- Signup collects: person type, email, phone, full name, login, password.
-- `login` is the sign-in identifier; the phone is Moldova-only (+373 + 8 digits).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gh3sp_b2b_users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `person_type` ENUM('company','individual') NOT NULL DEFAULT 'company',
    `login` VARCHAR(64) NOT NULL,
    `email` VARCHAR(190) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(190) NOT NULL,
    `phone_number` VARCHAR(32) NOT NULL,
    `role` ENUM('b2b_client') NOT NULL DEFAULT 'b2b_client',
    `status` ENUM('pending','active','blocked') NOT NULL DEFAULT 'pending',
    -- Not asked at signup. The admin fills these in when a proforma has to
    -- carry company details, hence nullable.
    `company_name` VARCHAR(190) DEFAULT NULL,
    `legal_address` VARCHAR(255) DEFAULT NULL,
    `bank_name` VARCHAR(190) DEFAULT NULL,
    `bank_iban` VARCHAR(64) DEFAULT NULL,
    `vat_code` VARCHAR(32) DEFAULT NULL,
    `admin_note` TEXT DEFAULT NULL,
    `last_login_at` DATETIME DEFAULT NULL,
    `last_login_ip` VARCHAR(45) DEFAULT NULL,
    -- Login brute-force guard.
    `failed_attempts` INT(11) NOT NULL DEFAULT 0,
    `locked_until` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_b2b_login` (`login`),
    UNIQUE KEY `uniq_b2b_email` (`email`),
    KEY `idx_b2b_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- B2B sessions (the spec's JWT, adapted to a server-rendered stack).
-- Selector/validator scheme: the cookie holds `selector.validator` while only a
-- hash of the validator is stored, so a table dump cannot be replayed.
-- Created only for an account the Super Admin has activated.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gh3sp_b2b_sessions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `b2b_user_id` INT(11) NOT NULL,
    `selector` CHAR(32) NOT NULL,
    `validator_hash` CHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_b2b_selector` (`selector`),
    KEY `idx_b2b_sess_user` (`b2b_user_id`),
    KEY `idx_b2b_sess_exp` (`expires_at`),
    CONSTRAINT `fk_b2b_sess_user` FOREIGN KEY (`b2b_user_id`)
        REFERENCES `gh3sp_b2b_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- Car-origin permissions (spec 4.2 / 3.2). No row means no access.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gh3sp_b2b_permissions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `b2b_user_id` INT(11) NOT NULL,
    `region` ENUM('korea','europe','china','usa') NOT NULL,
    `is_allowed` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_b2b_perm` (`b2b_user_id`, `region`),
    CONSTRAINT `fk_b2b_perm_user` FOREIGN KEY (`b2b_user_id`)
        REFERENCES `gh3sp_b2b_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- Audit trail (spec 4.3 / 3.3).
-- action_type: login, login_failed, logout, page_view, generate_invoice,
--              send_to_admin, region_denied, save_car, unsave_car, register,
--              password_reset, status_changed, permissions_changed
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gh3sp_b2b_activity_logs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `b2b_user_id` INT(11) NOT NULL,
    `action_type` VARCHAR(50) NOT NULL,
    `details` JSON DEFAULT NULL,
    `target_id` INT(11) DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_b2b_log_user` (`b2b_user_id`, `created_at`),
    KEY `idx_b2b_log_action` (`action_type`),
    CONSTRAINT `fk_b2b_log_user` FOREIGN KEY (`b2b_user_id`)
        REFERENCES `gh3sp_b2b_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- Advance-payment proformas (spec 4.4 / 2.3.2).
-- `access_key` signs the public URL so documents are not enumerable by id.
-- `car_snapshot` freezes the car data at issue time: listings change, an issued
-- document must not.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gh3sp_b2b_invoices` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `b2b_user_id` INT(11) NOT NULL,
    `car_id` INT(11) NOT NULL,
    `invoice_no` VARCHAR(40) NOT NULL,
    `advance_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'EUR',
    `access_key` CHAR(40) NOT NULL,
    `car_snapshot` JSON DEFAULT NULL,
    `status` ENUM('issued','sent','paid','cancelled') NOT NULL DEFAULT 'issued',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_b2b_inv_no` (`invoice_no`),
    KEY `idx_b2b_inv_user` (`b2b_user_id`, `created_at`),
    KEY `idx_b2b_inv_car` (`car_id`),
    CONSTRAINT `fk_b2b_inv_user` FOREIGN KEY (`b2b_user_id`)
        REFERENCES `gh3sp_b2b_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- "Send to Super Admin" requests (spec 2.3.3).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gh3sp_b2b_requests` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `b2b_user_id` INT(11) NOT NULL,
    `car_id` INT(11) NOT NULL,
    `invoice_id` INT(11) DEFAULT NULL,
    `comment` TEXT DEFAULT NULL,
    `status` ENUM('new','seen','approved','rejected') NOT NULL DEFAULT 'new',
    `admin_note` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_b2b_req_user` (`b2b_user_id`, `created_at`),
    KEY `idx_b2b_req_status` (`status`),
    CONSTRAINT `fk_b2b_req_user` FOREIGN KEY (`b2b_user_id`)
        REFERENCES `gh3sp_b2b_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- Saved / watched cars, cabinet tab 1 (spec 2.3.1).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gh3sp_b2b_saved_cars` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `b2b_user_id` INT(11) NOT NULL,
    `car_id` INT(11) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_b2b_saved` (`b2b_user_id`, `car_id`),
    CONSTRAINT `fk_b2b_saved_user` FOREIGN KEY (`b2b_user_id`)
        REFERENCES `gh3sp_b2b_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- Module settings, reusing the existing gh3sp_settings key-value store.
-- The nested sub-select is deliberate: MySQL refuses to read the target table
-- of an INSERT directly in the WHERE clause, so re-running stays safe whether
-- or not gh3sp_settings has a unique index on `name`.
-- ---------------------------------------------------------------------
INSERT INTO `gh3sp_settings` (`name`, `value`)
SELECT t.`name`, t.`value` FROM (
              SELECT 'b2b_whatsapp_driver'   AS `name`, 'walink' AS `value`
    UNION ALL SELECT 'b2b_whatsapp_phone_id',       ''
    UNION ALL SELECT 'b2b_whatsapp_token',          ''
    UNION ALL SELECT 'b2b_whatsapp_template',       ''
    UNION ALL SELECT 'b2b_superadmin_phone',        ''
    UNION ALL SELECT 'b2b_superadmin_email',        ''
    UNION ALL SELECT 'b2b_advance_mode',            'fixed'
    UNION ALL SELECT 'b2b_advance_default',         '1000'
    UNION ALL SELECT 'b2b_advance_percent',         '10'
    UNION ALL SELECT 'b2b_advance_max',             '0'
) AS t
WHERE t.`name` NOT IN (SELECT `name` FROM (SELECT `name` FROM `gh3sp_settings`) AS s);
-- test deploy