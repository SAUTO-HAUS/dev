ALTER TABLE gh3sp_car_ctlg ADD COLUMN is_at_client TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Car is with client (excluded from exports)';

ALTER TABLE gh3sp_car_ctlg ADD INDEX idx_is_at_client (is_at_client);
