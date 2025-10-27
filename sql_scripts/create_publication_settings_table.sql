-- Insert publication settings into existing gh3sp_settings table
-- Table already exists with structure: id, name, value

INSERT INTO `gh3sp_settings` (`name`, `value`) VALUES
('regular_telegram_bot_token', '1398519511:AAHGNlpbTutAS4QAnT7Z-eCf7_z80hJ4N2g'),
('regular_telegram_chat_id', '564183869'),
('regular_facebook_page_id', '482777831588669'),
('auto_publish_regular', '1'),
('auto_publish_order', '0'),
('order_telegram_bot_token', '1398519511:AAHGNlpbTutAS4QAnT7Z-eCf7_z80hJ4N2g'),
('order_telegram_chat_id', '564183869'),
('order_facebook_page_id', '482777831588669'),
('regular_999md_account', 'SAUTO-HAUS'),
('regular_999md_token', 'I_SKyGEvvG5Rfm7lRZeiOTwk7r_F'),
('order_999md_account', 'Sauto-auto-comerciale'),
('order_999md_token', 'EeKkPqGFjEhJZIK3S5KWh59w8jNG'),
('regular_facebook_token', ''),
('order_facebook_token', '')
AS new_values
ON DUPLICATE KEY UPDATE value = new_values.value;
