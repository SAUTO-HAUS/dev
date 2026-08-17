ALTER TABLE gh3sp_car_ctlg ADD COLUMN status_changed_at DATETIME NULL COMMENT 'Last time n_a status was changed (0↔1)';

UPDATE gh3sp_car_ctlg SET status_changed_at = NOW() WHERE status_changed_at IS NULL;

ALTER TABLE gh3sp_car_ctlg ADD INDEX idx_status_changed_at (status_changed_at);

DROP TRIGGER IF EXISTS tr_n_a_status_change;

DELIMITER $$
CREATE TRIGGER tr_n_a_status_change 
BEFORE UPDATE ON gh3sp_car_ctlg
FOR EACH ROW
BEGIN
    IF OLD.n_a != NEW.n_a THEN
        SET NEW.status_changed_at = NOW();
    END IF;
END$$
DELIMITER ;
