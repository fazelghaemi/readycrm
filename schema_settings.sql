
-- جدول تنظیمات ساده برای ذخیره کلیدها/مقادیر
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key`   varchar(191) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
