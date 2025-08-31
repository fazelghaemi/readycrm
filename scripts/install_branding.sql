
-- scripts/install_branding.sql
CREATE TABLE IF NOT EXISTS settings (
  `key` VARCHAR(64) NOT NULL PRIMARY KEY,
  `value` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (`key`, `value`) VALUES
('brand_name', 'Ready Studio CRM'),
('brand_logo_url', ''),
('brand_primary_color', '#00b0a4'),
('brand_secondary_color', '#098b82'),
('brand_midnight_color', '#181c24'),
('brand_bg_color', '#f6f9fa'),
('brand_text_color', '#1b1f2b')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
