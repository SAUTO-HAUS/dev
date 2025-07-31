-- Role-Based Access Control Migration
-- This script adds the necessary tables and columns for the new RBAC system

-- 1. Add new columns to existing _adm_usr table
ALTER TABLE `gh3sp_adm_usr` 
ADD COLUMN `role` ENUM('gordon', 'admin', 'publisher', 'publisher_limited') DEFAULT 'publisher' AFTER `type`,
ADD COLUMN `branch_id` INT(11) NULL DEFAULT NULL AFTER `role`,
ADD COLUMN `permissions` TEXT NULL DEFAULT NULL AFTER `branch_id`,
ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `permissions`,
ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- 2. Create branches table
CREATE TABLE IF NOT EXISTS `gh3sp_branches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `address` text,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Insert default branches
INSERT INTO `gh3sp_branches` (`name`, `code`, `address`) VALUES
('Calea Moșilor', 'calea_mosilor', 'Calea Moșilor, Chișinău'),
('Filial 2', 'filial_2', 'Adresa Filial 2');

-- 4. Create roles table
CREATE TABLE IF NOT EXISTS `gh3sp_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text,
  `permissions` text,
  `is_system` tinyint(1) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
