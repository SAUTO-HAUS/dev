-- =====================================================================
-- Korea costs: sea freight choice (RoRo / Container) + polish & chemical
-- cleaning.
--
--  * sea_freight_container — the alternative to sea_freight_roro. Both are
--    part of the CUSTOMS BASE, never printed as an extra line. RoRo stays the
--    canonical one: the price stored on the car (car_ctlg.prc) and everything
--    that derives from it are unchanged. Container is offered on the public
--    breakdown as a temporary "what if", recomputed in the browser.
--  * polish_cleaning — a normal fixed cost, added after customs, which the
--    visitor can untick to see the price without it.
--
-- polish_cleaning is seeded DISABLED on purpose. Enabling it raises the landed
-- cost of every Korean car, and the stored card price (car_ctlg.prc) only
-- catches up when the resync runs — so seeding it enabled would leave the site
-- showing one price at the top of the car page and another in the breakdown
-- until someone saved the settings. Tick it in /adminsauto/parsing/settings and
-- press Save: that one action enables it AND resyncs the cards, so both figures
-- move together.
--
-- The admin forms (/adminsauto/parsing/settings, /adminsauto/b2b/pricing,
-- /adminsauto/b2b/user?id=) build their rows from these tables, so both
-- parameters appear there by themselves once this has run.
--
-- Safe to re-run: every statement skips what already exists, and none of them
-- touches an amount somebody has already edited.
-- =====================================================================

-- --- retail (public prices) -------------------------------------------------
-- uq_param_key is UNIQUE on param_key, so IGNORE is enough here.
INSERT IGNORE INTO `gh3sp_parsing_kr_params`
    (`param_key`, `value_type`, `amount_eur`, `enabled`, `sort_order`) VALUES
('sea_freight_container', 'fixed', 2400, 1, 5),   -- sorts right after RoRo (also 5)
('polish_cleaning',       'fixed',  250, 0, 8);   -- disabled: see the note above

-- --- B2B global rows (b2b_user_id IS NULL) ----------------------------------
-- A UNIQUE index does NOT dedupe NULLs in MySQL, so INSERT IGNORE would add a
-- second global row on every run: guard each one explicitly. The nested derived
-- table is what lets a statement read the table it inserts into.
INSERT INTO `gh3sp_b2b_kr_params`
    (`b2b_user_id`, `param_key`, `value_type`, `amount_eur`, `enabled`, `sort_order`)
SELECT NULL, 'sea_freight_container', 'fixed', 2400, 1, 5
 WHERE NOT EXISTS (SELECT 1 FROM (
        SELECT 1 FROM `gh3sp_b2b_kr_params`
         WHERE `b2b_user_id` IS NULL AND `param_key` = 'sea_freight_container' LIMIT 1) AS x);

INSERT INTO `gh3sp_b2b_kr_params`
    (`b2b_user_id`, `param_key`, `value_type`, `amount_eur`, `enabled`, `sort_order`)
SELECT NULL, 'polish_cleaning', 'fixed', 250, 0, 8
 WHERE NOT EXISTS (SELECT 1 FROM (
        SELECT 1 FROM `gh3sp_b2b_kr_params`
         WHERE `b2b_user_id` IS NULL AND `param_key` = 'polish_cleaning' LIMIT 1) AS x);

-- --- B2B per-client rows ----------------------------------------------------
-- A client with his own KR set REPLACES the global one entirely (see
-- parsing_pricing_payload), so without these two he would silently lose the
-- container option and the polish line. Seeded at the same amounts; the admin
-- can then price them per client like any other line.
INSERT IGNORE INTO `gh3sp_b2b_kr_params`
    (`b2b_user_id`, `param_key`, `value_type`, `amount_eur`, `enabled`, `sort_order`)
SELECT `b2b_user_id`, 'sea_freight_container', 'fixed', 2400, 1, 5
  FROM (SELECT DISTINCT `b2b_user_id` FROM `gh3sp_b2b_kr_params`
         WHERE `b2b_user_id` IS NOT NULL) AS clients;

INSERT IGNORE INTO `gh3sp_b2b_kr_params`
    (`b2b_user_id`, `param_key`, `value_type`, `amount_eur`, `enabled`, `sort_order`)
SELECT `b2b_user_id`, 'polish_cleaning', 'fixed', 250, 0, 8
  FROM (SELECT DISTINCT `b2b_user_id` FROM `gh3sp_b2b_kr_params`
         WHERE `b2b_user_id` IS NOT NULL) AS clients;
