INSERT INTO `gh3sp_settings` (`name`, `value`) VALUES
-- Telegram settings (using working values from old code)
('regular_telegram_bot_token', '7459955785:AAGTMPvUkh2Fktar7ZpNlHBFsq43FH_DPsY'),
('regular_telegram_chat_id', '-1002605369940'),
('order_telegram_bot_token', '7459955785:AAGTMPvUkh2Fktar7ZpNlHBFsq43FH_DPsY'),
('order_telegram_chat_id', '-1002605369940'),

-- Facebook settings (location-based)
-- str. Calea Moşilor 11 (location ID: 1)
('regular_facebook_page_id', '482777831588669'),
('regular_facebook_token', 'EAAPYJ3JWk0UBPhHoFrglY8vNF9Jrm40RdjCvuPkYB0mO226K3yqF5qQrZAUasvkmAidLqK87dTZCCRyVwpMReuR5EKscMKwJjoAFZAiTUwjMSjLdstz15BmWr6QQfJR8YBKZAkBy0ksMHXwdvL8vzZAUpoF5K7osglWrLVQZB91xbtFJnUkw2MZCMhZAB6YRMATSJc46'),
-- str. Pietrăriei 3 (location ID: 2)
('order_facebook_page_id', '100064116546695'),
('order_facebook_token', 'EAAPYJ3JWk0UBPsgxBX8CZAarZAbDkllOe5rkXFZAfW29EnDKf7S68aVZC4Y4zvyswEGiLns1JMkp2iNPRYm5ZCoTgUFUyz2k6cfnlGNzHFAWAhRtYcYAZC8BlkBxKbpNj1cPU4jSdeXLeeRDwEoLXySRidMrUQVz2TrtR8gIe1AelIQWfqYOVPowDqosS10Y2GJjCO'),

-- 999.md API settings
('regular_999md_account', 'SAUTO-HAUS'),
('regular_999md_token', 'I_SKyGEvvG5Rfm7lRZeiOTwk7r_F'),
('order_999md_account', 'Sauto-stock-extern'),
('order_999md_token', 'jMEsHjO0FhoRZm0KSsONLpkGLMIK'),

-- Auto-publication settings
('auto_publish_regular', '1'),
('auto_publish_order', '1')
AS new_values
ON DUPLICATE KEY UPDATE value = new_values.value;
