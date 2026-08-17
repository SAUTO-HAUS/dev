-- =====================================================================
-- B2B MODULE (sauto.md): partner accounts with Super Admin approval,
-- region + catalog permissions, audit trail, proformas, Super Admin requests,
-- and B2B pricing (global tables + full per-client price tables).
--
-- Approval by the Super Admin is the only gate: no SMS, no OTP, no 2FA.
-- Single, self-contained migration. Run ONCE per database.
--
-- DEPENDENCY: the B2B pricing section seeds from the retail parsing tables
-- (gh3sp_parsing_*), so create_parsing_tables.sql must have run first. Every
-- statement is idempotent (IF NOT EXISTS / guarded seed), so re-running is safe.
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
    -- Signup does not ask for a login: `login` mirrors the email, so it is sized
    -- like the email column. Older accounts still hold the login they picked.
    `login` VARCHAR(190) NOT NULL,
    `email` VARCHAR(190) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(190) NOT NULL,
    `phone_number` VARCHAR(32) NOT NULL,
    `role` ENUM('b2b_client') NOT NULL DEFAULT 'b2b_client',
    `status` ENUM('active','blocked') NOT NULL DEFAULT 'active',
    -- Catalog access, alongside the region permissions. 1 = may see that catalog.
    -- in_stock -> /cars, on_order -> /ordercars. Both on by default.
    `allow_in_stock` TINYINT(1) NOT NULL DEFAULT 1,
    `allow_on_order` TINYINT(1) NOT NULL DEFAULT 1,
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
    UNIQUE KEY `uniq_b2b_phone` (`phone_number`),
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
    -- 'usa' was renamed to 'canada' (see rename_b2b_region_usa_to_canada.sql).
    `region` ENUM('korea','europe','china','canada') NOT NULL,
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
    `doc_meta` JSON DEFAULT NULL,
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


-- =====================================================================
-- B2B pricing tables. Same structure as the retail parsing tables, but a
-- separate set of values shown only to logged-in partners. Covers 4 tables:
-- commission tiers, Europe delivery tiers, Europe/Korea fixed costs. Korea
-- markup, customs config and the EUR rate are NOT duplicated — B2B reads those
-- from retail.
--
-- MULTI-TENANT: each table carries a nullable `b2b_user_id`.
--   b2b_user_id IS NULL -> the GLOBAL B2B row, shared by every partner
--                          (edited on /adminsauto/b2b/pricing).
--   b2b_user_id = X      -> a per-client row that OVERRIDES the global one,
--                          edited on /adminsauto/b2b/pricing?user=X.
-- Fallback is per table: a client with any rows in a table uses them; otherwise
-- the global (NULL) rows apply. Deleting a client's rows reverts them to global.
--
-- The seed copies the current retail values (INSERT ... SELECT) into the GLOBAL
-- (NULL) set, so B2B starts identical to retail and the admin adjusts the
-- differences. This REQUIRES the retail parsing tables (create_parsing_tables.sql)
-- to exist first. The seed only runs when the B2B table is still empty.
-- =====================================================================

-- ---- Shared commission tiers (Europe + Korea) -----------------------
CREATE TABLE IF NOT EXISTS `gh3sp_b2b_commission_tiers` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `b2b_user_id` INT(11) DEFAULT NULL COMMENT 'NULL = global; else per-client override',
    `price_from` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `price_to` DECIMAL(12,2) DEFAULT NULL COMMENT 'NULL = no upper limit',
    `commission` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `updated_by` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_sort_order` (`sort_order`),
    INDEX `idx_b2b_user` (`b2b_user_id`),
    CONSTRAINT `fk_b2b_comm_user` FOREIGN KEY (`b2b_user_id`)
        REFERENCES `gh3sp_b2b_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `gh3sp_b2b_commission_tiers` (`price_from`, `price_to`, `commission`, `sort_order`)
SELECT `price_from`, `price_to`, `commission`, `sort_order`
  FROM `gh3sp_parsing_commission_tiers`
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT 1 FROM `gh3sp_b2b_commission_tiers` LIMIT 1) AS x);


-- ---- Europe delivery tiers ------------------------------------------
CREATE TABLE IF NOT EXISTS `gh3sp_b2b_eu_tiers` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `b2b_user_id` INT(11) DEFAULT NULL COMMENT 'NULL = global; else per-client override',
    `price_from` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `price_to` DECIMAL(12,2) DEFAULT NULL COMMENT 'NULL = no upper limit',
    `delivery` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `commission` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `updated_by` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_sort_order` (`sort_order`),
    INDEX `idx_b2b_user` (`b2b_user_id`),
    CONSTRAINT `fk_b2b_eutier_user` FOREIGN KEY (`b2b_user_id`)
        REFERENCES `gh3sp_b2b_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `gh3sp_b2b_eu_tiers` (`price_from`, `price_to`, `delivery`, `commission`, `sort_order`)
SELECT `price_from`, `price_to`, `delivery`, `commission`, `sort_order`
  FROM `gh3sp_parsing_eu_tiers`
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT 1 FROM `gh3sp_b2b_eu_tiers` LIMIT 1) AS x);


-- ---- Europe fixed costs ---------------------------------------------
CREATE TABLE IF NOT EXISTS `gh3sp_b2b_eu_params` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `b2b_user_id` INT(11) DEFAULT NULL COMMENT 'NULL = global; else per-client override',
    `param_key` VARCHAR(100) NOT NULL,
    `value_type` ENUM('fixed','percent') NOT NULL DEFAULT 'fixed',
    `amount_eur` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `updated_by` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_param_key` (`b2b_user_id`, `param_key`),
    INDEX `idx_sort_order` (`sort_order`),
    CONSTRAINT `fk_b2b_euparam_user` FOREIGN KEY (`b2b_user_id`)
        REFERENCES `gh3sp_b2b_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `gh3sp_b2b_eu_params` (`param_key`, `value_type`, `amount_eur`, `enabled`, `sort_order`)
SELECT `param_key`, `value_type`, `amount_eur`, `enabled`, `sort_order`
  FROM `gh3sp_parsing_eu_params`
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT 1 FROM `gh3sp_b2b_eu_params` LIMIT 1) AS x);


-- ---- Korea fixed costs ----------------------------------------------
CREATE TABLE IF NOT EXISTS `gh3sp_b2b_kr_params` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `b2b_user_id` INT(11) DEFAULT NULL COMMENT 'NULL = global; else per-client override',
    `param_key` VARCHAR(100) NOT NULL,
    `value_type` ENUM('fixed','percent') NOT NULL DEFAULT 'fixed',
    `amount_eur` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `updated_by` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_param_key` (`b2b_user_id`, `param_key`),
    INDEX `idx_sort_order` (`sort_order`),
    CONSTRAINT `fk_b2b_krparam_user` FOREIGN KEY (`b2b_user_id`)
        REFERENCES `gh3sp_b2b_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `gh3sp_b2b_kr_params` (`param_key`, `value_type`, `amount_eur`, `enabled`, `sort_order`)
SELECT `param_key`, `value_type`, `amount_eur`, `enabled`, `sort_order`
  FROM `gh3sp_parsing_kr_params`
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT 1 FROM `gh3sp_b2b_kr_params` LIMIT 1) AS x);


-- ---------------------------------------------------------------------
-- Self-service password reset: one-time, hashed, expiring tokens.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gh3sp_b2b_password_resets` (
    `id`          INT(11)     NOT NULL AUTO_INCREMENT,
    `b2b_user_id` INT(11)     NOT NULL,
    `token_hash`  CHAR(64)    NOT NULL,
    `expires_at`  DATETIME    NOT NULL,
    `used_at`     DATETIME    DEFAULT NULL,
    `ip_address`  VARCHAR(45) DEFAULT NULL,
    `created_at`  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_b2b_reset_token` (`token_hash`),
    KEY `idx_b2b_reset_user` (`b2b_user_id`),
    CONSTRAINT `fk_b2b_reset_user` FOREIGN KEY (`b2b_user_id`)
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
              SELECT 'b2b_advance_mode'     AS `name`, 'fixed' AS `value`
    UNION ALL SELECT 'b2b_advance_default',         '1000'
    UNION ALL SELECT 'b2b_advance_percent',         '10'
    UNION ALL SELECT 'b2b_advance_max',             '0'
) AS t
WHERE t.`name` NOT IN (SELECT `name` FROM (SELECT `name` FROM `gh3sp_settings`) AS s);

-- =====================================================================
-- Offer expiry (timer) for the B2B price tables.
--
-- One row per PRICING SCOPE, mirroring the convention of the four price
-- tables above:
--   b2b_user_id IS NULL  -> the general offer, valid for every partner;
--   b2b_user_id = X      -> that client's own offer, set on their pricing tab.
--
-- Cascade on expiry (see App\Services\B2b\B2bOffer):
--   a client's own offer expires  -> they fall back to the GLOBAL B2B prices;
--   the global offer expires      -> every partner falls back to the PUBLIC
--                                    (guest) prices.
-- expires_at NULL, or no row at all, means "no time limit".
-- =====================================================================
CREATE TABLE IF NOT EXISTS `gh3sp_b2b_price_expiry` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `b2b_user_id` INT(11) DEFAULT NULL COMMENT 'NULL = general offer; else per-client',
    `expires_at` DATETIME DEFAULT NULL COMMENT 'NULL = no limit',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `updated_by` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_b2b_expiry_user` (`b2b_user_id`),
    CONSTRAINT `fk_b2b_expiry_user` FOREIGN KEY (`b2b_user_id`)
        REFERENCES `gh3sp_b2b_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- Gifts granted to a partner (spec: free polishing / dry cleaning).
--
-- An EVENT, not an account attribute: the same client can receive several
-- over time and each keeps its own date. `seen_at` NULL is what drives the
-- notification dot in the header — once the partner opens the Gifts tab the
-- gift stops being "new" and becomes a normal history row.
--
-- `revoked_at` is a soft delete: a mistake can be withdrawn without erasing
-- the record of it having been granted.
-- =====================================================================
CREATE TABLE IF NOT EXISTS `gh3sp_b2b_gifts` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `b2b_user_id` INT(11) NOT NULL,
    `items` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'comma-separated service codes',
    `note` VARCHAR(500) NOT NULL DEFAULT '',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_by` INT(11) DEFAULT NULL COMMENT 'admin user id',
    `seen_at` DATETIME DEFAULT NULL COMMENT 'NULL = not opened by the partner yet',
    `revoked_at` DATETIME DEFAULT NULL COMMENT 'NULL = active',
    `revoked_by` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_b2b_gift_user` (`b2b_user_id`, `revoked_at`),
    INDEX `idx_b2b_gift_unseen` (`b2b_user_id`, `seen_at`),
    CONSTRAINT `fk_b2b_gift_user` FOREIGN KEY (`b2b_user_id`)
        REFERENCES `gh3sp_b2b_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- Saved searches with an alert (spec: the partner looks for an Opel Astra,
-- finds nothing, saves the search, and is told when matching cars appear).
--
-- Two timestamps carry the whole feature:
--   created_at  — the cut-off. Only cars published AFTER it ever match, so a
--                 fresh filter never lists the stock that was already there.
--   last_seen_at — when the partner last opened the section. Cars newer than
--                 this are the "new" ones that light up the dot; the list still
--                 shows everything since created_at.
--
-- `criteria` is JSON so the filter can gain fields without a migration.
-- =====================================================================
CREATE TABLE IF NOT EXISTS `gh3sp_b2b_saved_filters` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `b2b_user_id` INT(11) NOT NULL,
    `name` VARCHAR(120) NOT NULL DEFAULT '',
    `criteria` TEXT NOT NULL COMMENT 'JSON: br, mo, yr_from, yr_to, prc_from, prc_to',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'cut-off: only newer cars match',
    `last_seen_at` DATETIME DEFAULT NULL COMMENT 'NULL = never opened',
    `notified_at` DATETIME DEFAULT NULL COMMENT 'last email alert; NULL = never mailed',
    PRIMARY KEY (`id`),
    INDEX `idx_b2b_filter_user` (`b2b_user_id`),
    CONSTRAINT `fk_b2b_filter_user` FOREIGN KEY (`b2b_user_id`)
        REFERENCES `gh3sp_b2b_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
