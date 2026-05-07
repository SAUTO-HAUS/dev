ALTER TABLE gh3sp_car_ctlg ADD INDEX idx_car_ctlg_vis_act (vis, act);
ALTER TABLE gh3sp_car_ctlg ADD INDEX idx_car_ctlg_catalog_type (catalog_type);
ALTER TABLE gh3sp_car_ctlg ADD INDEX idx_car_ctlg_br_mo (br, mo);
ALTER TABLE gh3sp_car_ctlg ADD INDEX idx_car_ctlg_br (br);
ALTER TABLE gh3sp_car_ctlg ADD INDEX idx_car_ctlg_mo (mo);
ALTER TABLE gh3sp_car_ctlg ADD INDEX idx_car_ctlg_gr (gr);
ALTER TABLE gh3sp_car_ctlg ADD INDEX idx_car_ctlg_bt (bt);
ALTER TABLE gh3sp_car_ctlg ADD INDEX idx_car_ctlg_fl (fl);
ALTER TABLE gh3sp_car_ctlg ADD INDEX idx_car_ctlg_tra (tra);
ALTER TABLE gh3sp_car_ctlg ADD INDEX idx_car_ctlg_wd (wd);
ALTER TABLE gh3sp_car_ctlg ADD INDEX idx_car_ctlg_clr (clr);
ALTER TABLE gh3sp_car_ctlg ADD INDEX idx_car_ctlg_yr (yr);
ALTER TABLE gh3sp_car_ctlg ADD INDEX idx_car_ctlg_prc (prc);
ALTER TABLE gh3sp_car_ctlg ADD INDEX idx_car_ctlg_mlg (mlg);
ALTER TABLE gh3sp_car_ctlg ADD INDEX idx_car_ctlg_vol (vol);
ALTER TABLE gh3sp_car_ctlg ADD INDEX idx_car_ctlg_sts (sts);
ALTER TABLE gh3sp_car_ctlg ADD INDEX idx_car_ctlg_facet_base (vis, act, catalog_type, br, mo);

-- test deployment