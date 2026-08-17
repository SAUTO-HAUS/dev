SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'gh3sp_car_ctlg' 
    AND COLUMN_NAME = 'vin');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE gh3sp_car_ctlg ADD COLUMN vin VARCHAR(255) NULL COMMENT ''Manual VIN code entered by user (separate from 999.md integration)''', 
    'SELECT ''Column vin already exists'' as message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'gh3sp_car_ctlg' 
    AND INDEX_NAME = 'idx_car_ctlg_vin');

SET @sql_index = IF(@index_exists = 0, 
    'CREATE INDEX idx_car_ctlg_vin ON gh3sp_car_ctlg(vin)', 
    'SELECT ''Index idx_car_ctlg_vin already exists'' as message');

PREPARE stmt_index FROM @sql_index;
EXECUTE stmt_index;
DEALLOCATE PREPARE stmt_index;
