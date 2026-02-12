<?php
// ═══════════════════════════════════════════════════════════════════════════════
// تنظیمات اصلی سیستم CRM
// ═══════════════════════════════════════════════════════════════════════════════

define('APP_NAME', 'سیستم مدیریت ارتباط با مشتری');
define('APP_VERSION', '2.0.0');
define('BASE_URL', 'https://app.readycrm.ir/');
define('ASSETS_URL', BASE_URL . 'assets');

// ═══════════════════════════════════════════════════════════════════════════════
// تنظیمات دیتابیس
// ═══════════════════════════════════════════════════════════════════════════════

define('DB_HOST', 'localhost');
define('DB_NAME', 'readycr_readycrm');
define('DB_USER', 'readycr_readycrm');
define('DB_PASS', 'fmwkHWCOMXrwRW0782');
define('DB_CHARSET', 'utf8mb4');

// ═══════════════════════════════════════════════════════════════════════════════
// تنظیمات امنیتی
// ═══════════════════════════════════════════════════════════════════════════════

define('ENCRYPTION_KEY', 'CRM_SECRET_KEY_2024_SECURE_RANDOM_STRING_HERE_CHANGE_THIS');
define('SESSION_TIMEOUT', 3600); // 1 ساعت
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 دقیقه
define('PASSWORD_RESET_TOKEN_EXPIRY', 3600); // 1 ساعت (به ثانیه)
define('PASSWORD_RESET_URL', BASE_URL . 'public/reset_password.php');

// ═══════════════════════════════════════════════════════════════════════════════
// تنظیمات فایل آپلود
// ═══════════════════════════════════════════════════════════════════════════════

define('UPLOAD_MAX_SIZE', 5242880); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'webp']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', BASE_URL . 'uploads/');

// ═══════════════════════════════════════════════════════════════════════════════
// تنظیمات ایمیل (SMTP)
// ═══════════════════════════════════════════════════════════════════════════════

define('MAIL_HOST', 'mail.entekhabhome.ir');
define('MAIL_PORT', 465);
define('MAIL_ENCRYPTION', 'ssl');
define('MAIL_USERNAME', 'crm@entekhabhome.ir');
define('MAIL_PASSWORD', 'FAdI21mR63qa8nvNb1');
define('MAIL_FROM_EMAIL', 'crm@entekhabhome.ir');
define('MAIL_FROM_NAME', 'سیستم CRM گروه انتخاب');
define('MAIL_CHARSET', 'UTF-8');
define('MAIL_TIMEOUT', 30);

// ═══════════════════════════════════════════════════════════════════════════════
// تنظیمات متفرقه
// ═══════════════════════════════════════════════════════════════════════════════

define('DEFAULT_TIMEZONE', 'Asia/Tehran');
define('RECORDS_PER_PAGE', 20);
define('CURRENCY', 'تومان');
define('DATE_FORMAT', 'Y/m/d H:i');

// ═══════════════════════════════════════════════════════════════════════════════
// تنظیمات لاگ
// ═══════════════════════════════════════════════════════════════════════════════

define('LOG_PATH', __DIR__ . '/../logs/');
define('ERROR_LOG_FILE', LOG_PATH . 'error.log');
define('ACTIVITY_LOG_FILE', LOG_PATH . 'activity.log');
define('EMAIL_LOG_FILE', LOG_PATH . 'email.log');

// ═══════════════════════════════════════════════════════════════════════════════
// اعمال تنظیمات
// ═══════════════════════════════════════════════════════════════════════════════

date_default_timezone_set(DEFAULT_TIMEZONE);

// تنظیمات خطا
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', ERROR_LOG_FILE);
ini_set('error_log', __DIR__ . '/../logs/error.log');


// تنظیمات PHP
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');
ini_set('upload_max_filesize', '5M');
ini_set('post_max_size', '8M');

// شروع بافر خروجی
ob_start();

// ═══════════════════════════════════════════════════════════════════════════════
// توابع کمکی برای دسترسی سریع
// ═══════════════════════════════════════════════════════════════════════════════

function url($path = '') {
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

function asset($path = '') {
    return rtrim(ASSETS_URL, '/') . '/' . ltrim($path, '/');
}

function uploadPath($filename = '') {
    return rtrim(UPLOAD_PATH, '/') . '/' . ltrim($filename, '/');
}

function uploadUrl($filename = '') {
    return rtrim(UPLOAD_URL, '/') . '/' . ltrim($filename, '/');
}

function isDevelopment() {
    return defined('ENVIRONMENT') && ENVIRONMENT === 'development';
}

// ═══════════════════════════════════════════════════════════════════════════════
// ایجاد دایرکتوری‌های مورد نیاز
// ═══════════════════════════════════════════════════════════════════════════════

$required_dirs = [
    UPLOAD_PATH,
    LOG_PATH,
];

foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}
?>
