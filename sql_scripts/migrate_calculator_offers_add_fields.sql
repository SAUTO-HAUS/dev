-- Migration script to add new fields to existing calculator_offers table
-- Run this on existing database to update the table structure

-- Add new columns (will error if column already exists - that's OK, just skip those errors)
ALTER TABLE `gh3sp_calculator_offers` 
ADD COLUMN `bodywork` VARCHAR(50) DEFAULT NULL COMMENT 'Body type code (sdn, hbk, suv, etc.)' AFTER `year`,
ADD COLUMN `seats` INT(11) DEFAULT NULL COMMENT 'Number of seats' AFTER `bodywork`,
ADD COLUMN `mileage` INT(11) DEFAULT NULL COMMENT 'Mileage in km' AFTER `seats`,
ADD COLUMN `engine_power` INT(11) DEFAULT NULL COMMENT 'Engine power' AFTER `mileage`,
ADD COLUMN `transmission` VARCHAR(50) DEFAULT NULL COMMENT 'Transmission type code (mnl, atm, etc.)' AFTER `engine_power`,
ADD COLUMN `drive_type` VARCHAR(50) DEFAULT NULL COMMENT 'Drive type code (fr, re, 44)' AFTER `transmission`,
ADD COLUMN `color` VARCHAR(50) DEFAULT NULL COMMENT 'Color code' AFTER `drive_type`;

-- Add indexes for better performance (ignore errors if index already exists)
ALTER TABLE `gh3sp_calculator_offers`
ADD INDEX `idx_bodywork` (`bodywork`);

ALTER TABLE `gh3sp_calculator_offers`
ADD INDEX `idx_brand_model` (`brand`, `model`);
