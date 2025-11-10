-- Add offer_timer columns to car_ctlg table
-- Run this SQL to add the timer functionality for on_order cars

ALTER TABLE `gh3sp_car_ctlg` 
ADD COLUMN `offer_timer` VARCHAR(20) DEFAULT '30:00:00' COMMENT 'Timer format: DD:HH:MM' AFTER `advance_amount`,
ADD COLUMN `offer_timer_end` INT(11) DEFAULT 0 COMMENT 'Unix timestamp when offer expires' AFTER `offer_timer`;

-- Update existing on_order cars to have default timer (30 days from now)
UPDATE `gh3sp_car_ctlg` 
SET `offer_timer` = '30:00:00', 
    `offer_timer_end` = UNIX_TIMESTAMP() + (30 * 86400)
WHERE `catalog_type` = 'on_order' 
  AND (`offer_timer` IS NULL OR `offer_timer` = '');
