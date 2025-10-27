-- Insert publication settings into existing gh3sp_settings table
-- Table already exists with structure: id, name, value

INSERT INTO `gh3sp_settings` (`name`, `value`) VALUES
('regular_telegram_bot_token', '7459955785:AAGTMPvUkh2Fktar7ZpNlHBFsq43FH_DPsY'),
('regular_telegram_chat_id', '-1002605369940'),
('regular_facebook_page_id', '482777831588669'),
('auto_publish_regular', '1'),
('auto_publish_order', '0'),
('order_telegram_bot_token', ''),
('order_telegram_chat_id', ''),
('order_facebook_page_id', '482777831588669'),
('regular_999md_account', ''),
('regular_999md_token', ''),
('order_999md_account', ''),
('order_999md_token', ''),
('regular_facebook_token', ''),
('order_facebook_token', '')
AS new_values
ON DUPLICATE KEY UPDATE value = new_values.value;
