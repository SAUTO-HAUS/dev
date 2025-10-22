-- Add delivery_time field
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'gh3sp_car_ctlg' 
    AND COLUMN_NAME = 'delivery_time');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE gh3sp_car_ctlg ADD COLUMN delivery_time INT DEFAULT 0 COMMENT ''Delivery time in days for order cars''', 
    'SELECT ''Column delivery_time already exists'' as message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add advance_amount field
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'gh3sp_car_ctlg' 
    AND COLUMN_NAME = 'advance_amount');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE gh3sp_car_ctlg ADD COLUMN advance_amount DECIMAL(10,2) DEFAULT 0.00 COMMENT ''Advance amount for order cars (usually 70% of price)''', 
    'SELECT ''Column advance_amount already exists'' as message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add indexes
SET @index_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'gh3sp_car_ctlg' 
    AND INDEX_NAME = 'idx_car_ctlg_delivery_time');

SET @sql_index = IF(@index_exists = 0, 
    'CREATE INDEX idx_car_ctlg_delivery_time ON gh3sp_car_ctlg(delivery_time)', 
    'SELECT ''Index idx_car_ctlg_delivery_time already exists'' as message');

PREPARE stmt_index FROM @sql_index;
EXECUTE stmt_index;
DEALLOCATE PREPARE stmt_index;

SET @index_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'gh3sp_car_ctlg' 
    AND INDEX_NAME = 'idx_car_ctlg_advance_amount');

SET @sql_index = IF(@index_exists = 0, 
    'CREATE INDEX idx_car_ctlg_advance_amount ON gh3sp_car_ctlg(advance_amount)', 
    'SELECT ''Index idx_car_ctlg_advance_amount already exists'' as message');

PREPARE stmt_index FROM @sql_index;
EXECUTE stmt_index;
DEALLOCATE PREPARE stmt_index;
