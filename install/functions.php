<?php
/**
 * توابع کمکی نصب
 */

function get_step_title($step) {
    $titles = [
        1 => 'بررسی الزامات',
        2 => 'اطلاعات دیتابیس',
        3 => 'ساخت جداول',
        4 => 'ایجاد کاربر مدیر',
        5 => 'اتمام نصب'
    ];
    return $titles[$step] ?? 'نامشخص';
}

function get_step_slug($step) {
    $slugs = [
        1 => 'requirements',
        2 => 'database',
        3 => 'migrations',
        4 => 'admin',
        5 => 'finish'
    ];
    return $slugs[$step] ?? '';
}

function check_php_version() {
    return version_compare(PHP_VERSION, '8.0.0', '>=');
}

function check_extensions() {
    $required = ['mysqli', 'pdo', 'pdo_mysql', 'json', 'mbstring', 'curl'];
    $missing = [];
    
    foreach ($required as $ext) {
        if (!extension_loaded($ext)) {
            $missing[] = $ext;
        }
    }
    
    return $missing;
}

function check_write_permissions() {
    $paths = [
        __DIR__ . '/../private',
        __DIR__ . '/../uploads',
        __DIR__ . '/../database/migrations'
    ];
    
    $not_writable = [];
    
    foreach ($paths as $path) {
        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }
        
        if (!is_writable($path)) {
            $not_writable[] = $path;
        }
    }
    
    return $not_writable;
}

function test_database_connection($host, $username, $password, $database) {
    try {
        $conn = new mysqli($host, $username, $password, $database);
        
        if ($conn->connect_error) {
            return ['success' => false, 'error' => $conn->connect_error];
        }
        
        // بررسی کاراکتر ست
        $result = $conn->query("SHOW VARIABLES LIKE 'character_set_database'");
        $row = $result->fetch_assoc();
        
        $conn->close();
        
        return [
            'success' => true,
            'charset' => $row['Value'] ?? 'unknown'
        ];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function create_config_file($db_host, $db_name, $db_user, $db_pass) {
    $config_sample = __DIR__ . '/../private/config.sample.php';
    $config_file = __DIR__ . '/../private/config.php';
    
    if (!file_exists($config_sample)) {
        return ['success' => false, 'error' => 'فایل config.sample.php پیدا نشد'];
    }
    
    $content = file_get_contents($config_sample);
    
    // جایگزینی مقادیر
    $replacements = [
        "'localhost'" => "'" . $db_host . "'",
        "'crm_database'" => "'" . $db_name . "'",
        "'root'" => "'" . $db_user . "'",
        "'password'" => "'" . $db_pass . "'",
    ];
    
    $content = str_replace(array_keys($replacements), array_values($replacements), $content);
    
    if (file_put_contents($config_file, $content) === false) {
        return ['success' => false, 'error' => 'نمی‌توان فایل config.php را ایجاد کرد'];
    }
    
    return ['success' => true];
}

function format_file_size($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

function get_ini_value($key) {
    $value = ini_get($key);
    if ($value === false || $value === '') {
        return 'نامشخص';
    }
    return $value;
}

function create_install_lock() {
    $lock_file = __DIR__ . '/../.install.lock';
    $content = "# نصب در تاریخ: " . date('Y-m-d H:i:s') . "\n";
    $content .= "# این فایل برای جلوگیری از نصب مجدد ایجاد شده است.\n";
    $content .= "# برای نصب مجدد، این فایل را حذف کنید (همه داده‌ها پاک می‌شود!).\n";
    
    return file_put_contents($lock_file, $content) !== false;
}
