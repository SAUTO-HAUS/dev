USE sautom_db;

-- Show all tables in database (alternative method)
SHOW TABLES;


-- Create configuration table for PhoneReplacementService
CREATE TABLE IF NOT EXISTS phone_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    context VARCHAR(50) NOT NULL UNIQUE,
    phone_number VARCHAR(20) NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert approved configuration (ONLY these 4 numbers allowed)
INSERT INTO phone_config (context, phone_number, description) VALUES
('prunkul', '+37379600747', 'Prunkul Branch - Pietrăriei 3'),
('stock_website', '+37379600386', 'Website-Stock group'),
('on_order', '+37379500735', 'Website-On-Order (in transit + on order + /services/order page)'),
('website_all', '+37379600361', 'Website-All (general number for headers, footers, etc.)')
ON DUPLICATE KEY UPDATE 
    phone_number = VALUES(phone_number),
    description = VALUES(description),
    updated_at = CURRENT_TIMESTAMP;

-- Verify configuration was created
SELECT * FROM phone_config;

-- Search for any phone numbers in gh3sp_car_ctlg (broader search)
SELECT id, 
       SUBSTRING(inf, LOCATE('+373', inf), 13) as phone_in_inf,
       SUBSTRING(txt, LOCATE('+373', txt), 13) as phone_in_txt
FROM gh3sp_car_ctlg 
WHERE inf LIKE '%+373%' OR txt LIKE '%+373%'
LIMIT 10;

-- Count total phone numbers in database
SELECT 'gh3sp_car_ctlg' as table_name, COUNT(*) as total_phones FROM gh3sp_car_ctlg 
WHERE inf REGEXP '\\+373[0-9]{8}' OR txt REGEXP '\\+373[0-9]{8}';

-- Search for specific unauthorized numbers with different patterns
SELECT id, inf, txt FROM gh3sp_car_ctlg 
WHERE inf LIKE '%68689995%' OR inf LIKE '%69977674%' OR inf LIKE '%79977674%' 
   OR inf LIKE '%79954375%' OR inf LIKE '%79600446%' OR inf LIKE '%68500573%'
   OR txt LIKE '%68689995%' OR txt LIKE '%69977674%' OR txt LIKE '%79977674%'
   OR txt LIKE '%79954375%' OR txt LIKE '%79600446%' OR txt LIKE '%68500573%'
LIMIT 10;

-- Check other important tables for phone numbers
DESCRIBE gh3sp_adverts;
DESCRIBE gh3sp_car_list;

-- First check what columns exist in gh3sp_adverts
DESCRIBE gh3sp_adverts;

-- First check what columns exist in gh3sp_car_list  
DESCRIBE gh3sp_car_list;

-- Simple search in gh3sp_adverts (will update after seeing columns)
SELECT id FROM gh3sp_adverts LIMIT 1;

-- Simple search in gh3sp_car_list (will update after seeing columns)
SELECT id FROM gh3sp_car_list LIMIT 1;

