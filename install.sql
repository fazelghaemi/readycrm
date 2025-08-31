
-- msgway_otp_module/install.sql

-- Settings table (key/value) if not exists
CREATE TABLE IF NOT EXISTS settings (
  `key` VARCHAR(64) NOT NULL PRIMARY KEY,
  `value` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- MSGway keys
INSERT INTO settings (`setting_key`, `setting_value`) VALUES
('msgway_api_key', ''),
('msgway_template_id', '1'),
('msgway_otp_length', '5'),
('msgway_otp_expiry', '180'),
('msgway_resend_after', '45')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- OTP table
CREATE TABLE IF NOT EXISTS user_otps (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  mobile VARCHAR(20) NOT NULL,
  code_hash VARCHAR(255) NOT NULL,
  reference_id VARCHAR(64) DEFAULT NULL,
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  expires_at DATETIME NOT NULL,
  ip VARCHAR(45) DEFAULT NULL,
  status ENUM('PENDING','VERIFIED','EXPIRED','BLOCKED') NOT NULL DEFAULT 'PENDING',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_mobile_expires (mobile, expires_at),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
