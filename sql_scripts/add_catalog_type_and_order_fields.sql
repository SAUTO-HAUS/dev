ALTER TABLE gh3sp_car_ctlg ADD COLUMN catalog_type ENUM('in_stock', 'on_order') DEFAULT 'in_stock' NOT NULL;

-- Add order-specific fields for on_order cars
ALTER TABLE gh3sp_car_ctlg ADD COLUMN delivery_time INT DEFAULT 14 COMMENT 'Delivery time in days for orders';
ALTER TABLE gh3sp_car_ctlg ADD COLUMN advance_amount DECIMAL(10,2) NULL COMMENT 'Required advance payment amount (70% of price)';
ALTER TABLE gh3sp_car_ctlg ADD COLUMN order_conditions TEXT NULL COMMENT 'Payment conditions and terms';
ALTER TABLE gh3sp_car_ctlg ADD COLUMN customization_notes TEXT NULL COMMENT 'Available customization options';

-- Add index for catalog_type for better performance
ALTER TABLE gh3sp_car_ctlg ADD INDEX idx_catalog_type (catalog_type);

-- Add index for combined catalog_type and visibility for filtering
ALTER TABLE gh3sp_car_ctlg ADD INDEX idx_catalog_vis_act (catalog_type, vis, act);

-- Update existing cars to be marked as in_stock
UPDATE gh3sp_car_ctlg SET catalog_type = 'in_stock' WHERE catalog_type IS NULL OR catalog_type = '';

-- Create a view for easy access to in_stock cars (maintains backward compatibility)
CREATE OR REPLACE VIEW gh3sp_car_ctlg_in_stock AS 
SELECT * FROM gh3sp_car_ctlg WHERE catalog_type = 'in_stock';

-- Create a view for easy access to on_order cars
CREATE OR REPLACE VIEW gh3sp_car_ctlg_on_order AS 
SELECT * FROM gh3sp_car_ctlg WHERE catalog_type = 'on_order';

-- Add logging table for catalog_type changes (for Point 8 - Журналирование)
CREATE TABLE IF NOT EXISTS gh3sp_car_catalog_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    car_id INT NOT NULL,
    old_catalog_type ENUM('in_stock', 'on_order') NULL,
    new_catalog_type ENUM('in_stock', 'on_order') NOT NULL,
    changed_by INT NULL COMMENT 'User ID who made the change',
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT NULL,
    FOREIGN KEY (car_id) REFERENCES gh3sp_car_ctlg(id) ON DELETE CASCADE,
    INDEX idx_car_id (car_id),
    INDEX idx_changed_at (changed_at)
);

-- Add trigger to log catalog_type changes
DELIMITER $$
CREATE TRIGGER tr_catalog_type_change 
AFTER UPDATE ON gh3sp_car_ctlg
FOR EACH ROW
BEGIN
    IF OLD.catalog_type != NEW.catalog_type THEN
        INSERT INTO gh3sp_car_catalog_log (car_id, old_catalog_type, new_catalog_type, notes)
        VALUES (NEW.id, OLD.catalog_type, NEW.catalog_type, 
                CONCAT('Catalog type changed from ', OLD.catalog_type, ' to ', NEW.catalog_type));
    END IF;
END$$
DELIMITER ;

