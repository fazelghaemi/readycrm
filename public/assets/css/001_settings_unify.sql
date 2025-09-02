-- migrations/001_settings_unify.sql
-- Standardize `settings` table to (setting_key, setting_value) and seed brand/OTP keys.
CREATE TABLE IF NOT EXISTS settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(64) NOT NULL UNIQUE,
  setting_value TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Brand keys
INSERT INTO settings (setting_key, setting_value) VALUES
('brand_name', 'Ready Studio CRM'),
('brand_logo_url', ''),
('brand_primary_color', '#00b0a4'),
('brand_secondary_color', '#098b82'),
('brand_midnight_color', '#181c24'),
('brand_bg_color', '#f6f9fa'),
('brand_text_color', '#1b1f2b')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- MSGWAY / OTP keys
INSERT INTO settings (setting_key, setting_value) VALUES
('msgway_api_key',''),
('msgway_template_code','116'),
('msgway_lineNumber',''),
('msgway_otp_length','6'),
('msgway_resend_time','90'),
('msgway_mobile_format','^09[0-9]{9}$')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
