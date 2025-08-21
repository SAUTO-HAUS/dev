USE sautom_test_db;

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

-- Check what columns exist in common tables (run these one by one)
-- DESCRIBE gh3sp_car_ctlg;
-- DESCRIBE gh3sp_car_list; 
-- DESCRIBE gh3sp_adverts;

