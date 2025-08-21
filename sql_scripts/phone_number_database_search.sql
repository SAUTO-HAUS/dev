-- SQL Script to search ALL phone numbers in database as requested by client
-- Generated on 2025-08-21 17:50:00
-- This script searches for ALL phone numbers including unauthorized ones

-- First, check what columns exist in the tables
SHOW COLUMNS FROM gh3sp_car_ctlg LIKE '%phone%';
SHOW COLUMNS FROM gh3sp_adverts LIKE '%phone%';
SHOW COLUMNS FROM gh3sp_car_list LIKE '%phone%';

-- Search in ALL columns for phone patterns (since we don't know exact column names)
SELECT 'TABLE_STRUCTURE' as info_type, 'gh3sp_car_ctlg' as table_name, COLUMN_NAME, DATA_TYPE
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gh3sp_car_ctlg'
AND (COLUMN_NAME LIKE '%phone%' OR COLUMN_NAME LIKE '%tel%' OR COLUMN_NAME LIKE '%contact%');

SELECT 'TABLE_STRUCTURE' as info_type, 'gh3sp_adverts' as table_name, COLUMN_NAME, DATA_TYPE
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gh3sp_adverts'
AND (COLUMN_NAME LIKE '%phone%' OR COLUMN_NAME LIKE '%tel%' OR COLUMN_NAME LIKE '%contact%');

-- Check if tables exist first
SELECT 'TABLE_EXISTS' as check_type, 'gh3sp_info' as table_name, COUNT(*) as exists_count
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gh3sp_info';

SELECT 'TABLE_EXISTS' as check_type, 'gh3sp_settings' as table_name, COUNT(*) as exists_count
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gh3sp_settings';

SELECT 'TABLE_EXISTS' as check_type, 'gh3sp_seo' as table_name, COUNT(*) as exists_count
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gh3sp_seo';

-- Show ALL tables in database to see what exists
SELECT 'ALL_TABLES' as info_type, TABLE_NAME 
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
ORDER BY TABLE_NAME;

-- Search in ALL text columns for phone patterns (comprehensive search)
SELECT 'COMPREHENSIVE_SEARCH' as search_type, 
       TABLE_NAME, 
       COLUMN_NAME,
       'Check this column manually for phone numbers' as note
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND DATA_TYPE IN ('varchar', 'text', 'longtext', 'mediumtext', 'char')
  AND (COLUMN_NAME LIKE '%phone%' 
   OR COLUMN_NAME LIKE '%tel%'
   OR COLUMN_NAME LIKE '%contact%'
   OR COLUMN_NAME LIKE '%content%'
   OR COLUMN_NAME LIKE '%description%'
   OR COLUMN_NAME LIKE '%text%'
   OR COLUMN_NAME LIKE '%value%'
   OR COLUMN_NAME LIKE '%data%');

-- APPROVED NUMBERS CHECK - verify these are the ONLY allowed numbers
SELECT 'APPROVED_NUMBERS_CHECK' as verification_type,
       'These should be the ONLY phone numbers allowed:' as note,
       '+37379600747 (Prunkul), +37379600386 (Stock), +37379500735 (La Comanda), +37379600361 (General)' as approved_numbers;

-- CRITICAL: Check for hardcoded numbers in code files (manual check required)
SELECT 'MANUAL_CODE_CHECK_REQUIRED' as alert_type,
       'Check these files manually:' as instruction,
       'scripts/phone_replacement_script.php, admin AJAX handlers, PhoneReplacementService.php' as files_to_check;

-- UPDATE cars SET phone = '+37379600361' WHERE phone IN ('+37368689995', '+37369977674', '+37379977674', '+37379954375', '+37379600446', '+37368500573');
-- UPDATE adverts SET phone = '+37379600361' WHERE phone IN ('+37368689995', '+37369977674', '+37379977674', '+37379954375', '+37379600446', '+37368500573');
-- UPDATE pages SET content = REPLACE(content, '+37368689995', '+37379600361');
-- UPDATE pages SET content = REPLACE(content, '+37369977674', '+37379600361');
-- UPDATE pages SET content = REPLACE(content, '+37379977674', '+37379600361');
-- UPDATE pages SET content = REPLACE(content, '+37379954375', '+37379600361');
-- UPDATE pages SET content = REPLACE(content, '+37379600446', '+37379600361');
-- UPDATE pages SET content = REPLACE(content, '+37368500573', '+37379600361');

-- Search for phone patterns with various separators (spaces, dashes, dots, brackets)
-- SELECT * FROM table_name WHERE column_name REGEXP '\+373[\s\-\.\(\)]*[0-9]{2}[\s\-\.\(\)]*[0-9]{3}[\s\-\.\(\)]*[0-9]{3}';
