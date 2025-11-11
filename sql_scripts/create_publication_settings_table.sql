INSERT INTO `gh3sp_settings` (`name`, `value`) VALUES
-- Telegram settings (using working values from old code)
('regular_telegram_bot_token', '7459955785:AAGTMPvUkh2Fktar7ZpNlHBFsq43FH_DPsY'),
('regular_telegram_chat_id', '-1002605369940'),
('order_telegram_bot_token', '8169302156:AAEe1j7AASXegfKRdWB-rSiaKY-PSgqkGgo'),
('order_telegram_chat_id', '-1002605369940'),

-- Facebook settings (location-based - ONLY by address, not catalog type)
-- str. Calea Moşilor 11 (location ID: 1) - Subdomain (corrected mapping)
('location_1_facebook_page_id', '725963964220309'),
('location_1_facebook_token', 'EAAPYJ3JWk0UBPsgxBX8CZAarZAbDkllOe5rkXFZAfW29EnDKf7S68aVZC4Y4zvyswEGiLns1JMkp2iNPRYm5ZCoTgUFUyz2k6cfnlGNzHFAWAhRtYcYAZC8BlkBxKbpNj1cPU4jSdeXLeeRDwEoLXySRidMrUQVz2TrtR8gIe1AelIQWfqYOVPowDqosS10Y2GJjCO'),
-- str. Pietrăriei 3 (location ID: 2) - Main Domain (corrected mapping)
('location_2_facebook_page_id', '482777831588669'),
('location_2_facebook_token', 'EAAPYJ3JWk0UBPhHoFrglY8vNF9Jrm40RdjCvuPkYB0mO226K3yqF5qQrZAUasvkmAidLqK87dTZCCRyVwpMReuR5EKscMKwJjoAFZAiTUwjMSjLdstz15BmWr6QQfJR8YBKZAkBy0ksMHXwdvL8vzZAUpoF5K7osglWrLVQZB91xbtFJnUkw2MZCMhZAB6YRMATSJc46'),

-- 999.md API settings
-- Account 1: Sauto-auto-comerciale (pentru mașini in_stock - promovare personalizată)
('regular_999md_account', 'Sauto-auto-comerciale'),
('regular_999md_token', 'EeKkPqGFjEhJZIK3S5KWh59w8jNG'),
-- Account 2: SAUTO-HAUS (backup, dacă este necesar)
('comerciale_999md_account', 'SAUTO-HAUS'),
('comerciale_999md_token', 'I_SKyGEvvG5Rfm7lRZeiOTwk7r_F'),
-- Account 3: Sauto-stock-extern (pentru mașini on_order)
('order_999md_account', 'Sauto-stock-extern'),
('order_999md_token', 'jMEsHjO0FhoRZm0KSsONLpkGLMIK'),

-- Auto-publication settings
('auto_publish_regular', '1'),
('auto_publish_comerciale', '1'),
('auto_publish_order', '1')
AS new_values
ON DUPLICATE KEY UPDATE value = new_values.value;
