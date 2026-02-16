<?php
$page_title = 'تنظیمات سیستم';
$breadcrumb = [
    ['title' => 'داشبورد', 'url' => 'dashboard.php'],
    ['title' => 'تنظیمات']
];

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/database.php';
require_once __DIR__ . '/../private/auth.php';
require_once __DIR__ . '/../private/functions.php';

// بررسی دسترسی
if (!hasRole('admin')) {
    setMessage('شما دسترسی لازم برای مشاهده این صفحه را ندارید', 'error');
    header('Location: dashboard.php');
    exit();
}

$errors = [];
$success = '';

// پردازش فرم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($csrf_token)) {
        $errors[] = 'درخواست نامعتبر. لطفاً مجدداً تلاش کنید.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'general_settings') {
            $settings = [
                'company_name' => sanitizeInput($_POST['company_name']),
                'company_phone' => sanitizeInput($_POST['company_phone']),
                'company_email' => sanitizeInput($_POST['company_email']),
                'company_address' => sanitizeInput($_POST['company_address']),
                'tax_rate' => (int)$_POST['tax_rate'],
                'currency' => sanitizeInput($_POST['currency']),
                'records_per_page' => (int)$_POST['records_per_page']
            ];
            
            try {
                $pdo->beginTransaction();
                
                foreach ($settings as $key => $value) {
                    $stmt = $pdo->prepare("
                        INSERT INTO settings (setting_key, setting_value) 
                        VALUES (?, ?) 
                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
                    ");
                    $stmt->execute([$key, $value]);
                }
                
                $pdo->commit();
                logActivity($_SESSION['user_id'], 'update_settings', 'settings', null);
                setMessage('تنظیمات با موفقیت بروزرسانی شد', 'success');
                
            } catch (PDOException $e) {
                $pdo->rollback();
                error_log("خطا در بروزرسانی تنظیمات: " . $e->getMessage());
                $errors[] = 'خطا در ذخیره تنظیمات';
            }
        }
        
        if ($action === 'email_settings') {
            $email_settings = [
                'mail_host' => sanitizeInput($_POST['mail_host']),
                'mail_port' => (int)$_POST['mail_port'],
                'mail_username' => sanitizeInput($_POST['mail_username']),
                'mail_password' => $_POST['mail_password'],
                'mail_from_email' => sanitizeInput($_POST['mail_from_email']),
                'mail_from_name' => sanitizeInput($_POST['mail_from_name']),
                'mail_encryption' => sanitizeInput($_POST['mail_encryption'])
            ];
            
            try {
                $pdo->beginTransaction();
                
                foreach ($email_settings as $key => $value) {
                    $stmt = $pdo->prepare("
                        INSERT INTO settings (setting_key, setting_value) 
                        VALUES (?, ?) 
                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
                    ");
                    $stmt->execute([$key, $value]);
                }
                
                $pdo->commit();
                logActivity($_SESSION['user_id'], 'update_email_settings', 'settings', null);
                setMessage('تنظیمات ایمیل با موفقیت بروزرسانی شد', 'success');
                
            } catch (PDOException $e) {
                $pdo->rollback();
                error_log("خطا در بروزرسانی تنظیمات ایمیل: " . $e->getMessage());
                $errors[] = 'خطا در ذخیره تنظیمات ایمیل';
            }
        }
        
                if ($action === 'msgway_settings') {
            $msgway_settings = [
                'msgway_api_key' => sanitizeInput($_POST['msgway_api_key']),
                'msgway_line_number' => sanitizeInput($_POST['msgway_line_number']),
                'msgway_otp_length' => (int)$_POST['msgway_otp_length'],
                'msgway_otp_expire_time' => (int)$_POST['msgway_otp_expire_time'],
                'msgway_send_method' => sanitizeInput($_POST['msgway_send_method']),
                'msgway_otp_template_id' => (int)$_POST['msgway_otp_template_id'],
                'msgway_resend_delay' => (int)$_POST['msgway_resend_delay'],
                'msgway_mobile_format' => sanitizeInput($_POST['msgway_mobile_format']),
                'msgway_enabled' => isset($_POST['msgway_enabled']) ? 1 : 0
            ];

            try {
                $pdo->beginTransaction();

                foreach ($msgway_settings as $key => $value) {
                    $stmt = $pdo->prepare("
                        INSERT INTO settings (setting_key, setting_value)
                        VALUES (?, ?)
                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
                    ");
                    $stmt->execute([$key, $value]);
                }

                $pdo->commit();
                logActivity($_SESSION['user_id'], 'update_msgway_settings', 'settings', null);
                setMessage('تنظیمات راه پیام با موفقیت بروزرسانی شد', 'success');

            } catch (PDOException $e) {
                $pdo->rollback();
                error_log("خطا در بروزرسانی تنظیمات راه پیام: " . $e->getMessage());
                $errors[] = 'خطا در ذخیره تنظیمات راه پیام';
            }
        }


        if ($action === 'backup_database') {
            try {
                $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
                $backup_path = __DIR__ . '/../backups/' . $backup_file;
                
                if (!is_dir(__DIR__ . '/../backups')) {
                    mkdir(__DIR__ . '/../backups', 0755, true);
                }
                
                
                // پشتیبان‌گیری با PHP (مستقل از سیستم عامل)
                $backup_content = "-- Database Backup\n";
                $backup_content .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
                $backup_content .= "-- Database: " . DB_NAME . "\n\n";
                $backup_content .= "SET FOREIGN_KEY_CHECKS=0;\n";
                $backup_content .= "SET SQL_MODE=\"NO_AUTO_VALUE_ON_ZERO\";\n";
                $backup_content .= "SET AUTOCOMMIT=0;\n";
                $backup_content .= "START TRANSACTION;\n";
                $backup_content .= "SET time_zone=\"+00:00\";\n\n";
                
                // دریافت لیست جداول
                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($tables as $table) {
                    $backup_content .= "-- --------------------------------------------------------\n";
                    $backup_content .= "-- Table structure for table `$table`\n";
                    $backup_content .= "-- --------------------------------------------------------\n\n";
                    $backup_content .= "DROP TABLE IF EXISTS `$table`;\n";
                    
                    $create_table = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
                    $backup_content .= $create_table['Create Table'] . ";\n\n";
                    
                    // داده‌های جدول
                    $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (!empty($rows)) {
                        $backup_content .= "-- Dumping data for table `$table`\n";
                        $backup_content .= "-- --------------------------------------------------------\n\n";
                        
                        $columns = array_keys($rows[0]);
                        $backup_content .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES\n";
                        
                        $values = [];
                        foreach ($rows as $row) {
                            $row_values = [];
                            foreach ($row as $value) {
                                if ($value === null) {
                                    $row_values[] = 'NULL';
                                } elseif (is_numeric($value)) {
                                    $row_values[] = $value;
                                } else {
                                    $row_values[] = "'" . addslashes($value) . "'";
                                }
                            }
                            $values[] = "(" . implode(', ', $row_values) . ")";
                        }
                        
                        $backup_content .= implode(",\n", $values) . ";\n\n";
                    }
                }
                
                $backup_content .= "-- --------------------------------------------------------\n";
                $backup_content .= "COMMIT;\n";
                $backup_content .= "SET FOREIGN_KEY_CHECKS=1;\n";
                $backup_content .= "SET AUTOCOMMIT=1;\n";
                
                if (file_put_contents($backup_path, $backup_content)) {
                    logActivity($_SESSION['user_id'], 'database_backup', null, null);
                    setMessage('پشتیبان‌گیری با موفقیت انجام شد: ' . $backup_file . ' | <a href="download_backup.php?file=' . urlencode($backup_file) . '" class="btn btn-sm btn-primary">دانلود فایل</a>', 'success');
                } else {
                    throw new Exception('Failed to write backup file');
                }
                
            } catch (Exception $e) {
                error_log("خطا در پشتیبان‌گیری: " . $e->getMessage());
                $errors[] = 'خطا در پشتیبان‌گیری از دیتابیس: ' . $e->getMessage();
            }
        }
        
        if ($action === 'clear_logs') {
            try {
                $days = (int)$_POST['days_to_keep'];
                $stmt = $pdo->prepare("DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
                $stmt->execute([$days]);
                
                $deleted_count = $stmt->rowCount();
                logActivity($_SESSION['user_id'], 'clear_logs', null, null);
                setMessage("$deleted_count رکورد لاگ حذف شد", 'success');
                
            } catch (PDOException $e) {
                error_log("خطا در پاک کردن لاگ‌ها: " . $e->getMessage());
                $errors[] = 'خطا در پاک کردن لاگ‌ها';
            }
        }
    }
}

// دریافت تنظیمات فعلی
try {
    $settings_result = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
    $current_settings = [];
    foreach ($settings_result as $setting) {
        $current_settings[$setting['setting_key']] = $setting['setting_value'];
    }
} catch (PDOException $e) {
    error_log("خطا در دریافت تنظیمات: " . $e->getMessage());
    $current_settings = [];
}

// آمار سیستم
try {
    $system_stats = [
        'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'total_customers' => $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn(),
        'total_leads' => $pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn(),
        'total_tasks' => $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn(),
        'total_sales' => $pdo->query("SELECT COUNT(*) FROM sales")->fetchColumn(),
        'database_size' => $pdo->query("
            SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size 
            FROM information_schema.tables 
            WHERE table_schema = '" . DB_NAME . "'
        ")->fetchColumn(),
        'log_count' => $pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn()
    ];
} catch (PDOException $e) {
    error_log("خطا در دریافت آمار سیستم: " . $e->getMessage());
    $system_stats = array_fill_keys(['total_users', 'total_customers', 'total_leads', 'total_tasks', 'total_sales', 'database_size', 'log_count'], 0);
}

$csrf_token = generateCSRFToken();

include __DIR__ . '/../private/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">تنظیمات سیستم</h4>
        <p class="text-muted mb-0">مدیریت تنظیمات کلی سیستم</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row">
    <!-- تنظیمات عمومی -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-cog me-2 text-primary"></i>
                    تنظیمات عمومی
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="general_settings">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="company_name" class="form-label">نام شرکت</label>
                            <input type="text" class="form-control" id="company_name" name="company_name" 
                                   value="<?php echo htmlspecialchars($current_settings['company_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="company_phone" class="form-label">تلفن شرکت</label>
                            <input type="text" class="form-control" id="company_phone" name="company_phone" 
                                   value="<?php echo htmlspecialchars($current_settings['company_phone'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="company_email" class="form-label">ایمیل شرکت</label>
                            <input type="email" class="form-control" id="company_email" name="company_email" 
                                   value="<?php echo htmlspecialchars($current_settings['company_email'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="currency" class="form-label">واحد پول</label>
                            <input type="text" class="form-control" id="currency" name="currency" 
                                   value="<?php echo htmlspecialchars($current_settings['currency'] ?? 'تومان'); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="company_address" class="form-label">آدرس شرکت</label>
                        <textarea class="form-control" id="company_address" name="company_address" rows="3"><?php echo htmlspecialchars($current_settings['company_address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="tax_rate" class="form-label">نرخ مالیات (%)</label>
                            <input type="number" class="form-control" id="tax_rate" name="tax_rate" 
                                   value="<?php echo htmlspecialchars($current_settings['tax_rate'] ?? '9'); ?>" min="0" max="100">
                        </div>
                        <div class="col-md-6">
                            <label for="records_per_page" class="form-label">تعداد رکورد در صفحه</label>
                            <select class="form-select" id="records_per_page" name="records_per_page">
                                <option value="10" <?php echo ($current_settings['records_per_page'] ?? '20') == '10' ? 'selected' : ''; ?>>10</option>
                                <option value="20" <?php echo ($current_settings['records_per_page'] ?? '20') == '20' ? 'selected' : ''; ?>>20</option>
                                <option value="50" <?php echo ($current_settings['records_per_page'] ?? '20') == '50' ? 'selected' : ''; ?>>50</option>
                                <option value="100" <?php echo ($current_settings['records_per_page'] ?? '20') == '100' ? 'selected' : ''; ?>>100</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        ذخیره تنظیمات
                    </button>
                </form>
            </div>
        </div>
                <!-- تنظیمات راه پیام (پیامک) -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-sms me-2 text-primary"></i>
                    تنظیمات راه پیام (سیستم پیامکی)
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="msgway_settings">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="msgway_enabled" name="msgway_enabled"
                                   <?php echo ($current_settings['msgway_enabled'] ?? '0') == '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="msgway_enabled">
                                فعال‌سازی سیستم پیامکی راه پیام
                            </label>
                        </div>
                        <small class="text-muted">با فعال کردن این گزینه، سیستم پیامکی در کل سامانه فعال می‌شود</small>
                    </div>
                                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="msgway_api_key" class="form-label">
                                کلید API (apiKey) <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="msgway_api_key" name="msgway_api_key"
                                   value="<?php echo htmlspecialchars($current_settings['msgway_api_key'] ?? ''); ?>"
                                   placeholder="کلید API از پنل راه پیام">
                            <small class="text-muted">این کلید را از پنل کاربری راه پیام دریافت کنید</small>
                        </div>
                        <div class="col-md-6">
                            <label for="msgway_line_number" class="form-label">
                                شماره خط ارسال
                            </label>
                            <input type="text" class="form-control" id="msgway_line_number" name="msgway_line_number"
                                   value="<?php echo htmlspecialchars($current_settings['msgway_line_number'] ?? ''); ?>"
                                   placeholder="مثال: 30001234567890">
                            <small class="text-muted">شماره خطی که پیامک‌ها از آن ارسال می‌شود</small>
                        </div>
                    </div>
                                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="msgway_otp_template_id" class="form-label">
                                کد پترن OTP <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="msgway_otp_template_id" name="msgway_otp_template_id"
                                   value="<?php echo htmlspecialchars($current_settings['msgway_otp_template_id'] ?? ''); ?>"
                                   placeholder="مثال: 123456">
                            <small class="text-muted">شناسه الگوی OTP در پنل راه پیام</small>
                        </div>
                        <div class="col-md-4">
                            <label for="msgway_otp_length" class="form-label">
                                طول کد تایید (OTP)
                            </label>
                            <select class="form-select" id="msgway_otp_length" name="msgway_otp_length">
                                <option value="4" <?php echo ($current_settings['msgway_otp_length'] ?? '6') == '4' ? 'selected' : ''; ?>>4 رقم</option>
                                <option value="5" <?php echo ($current_settings['msgway_otp_length'] ?? '6') == '5' ? 'selected' : ''; ?>>5 رقم</option>
                                <option value="6" <?php echo ($current_settings['msgway_otp_length'] ?? '6') == '6' ? 'selected' : ''; ?>>6 رقم</option>
                                <option value="8" <?php echo ($current_settings['msgway_otp_length'] ?? '6') == '8' ? 'selected' : ''; ?>>8 رقم</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="msgway_otp_expire_time" class="form-label">
                                زمان انقضای کد (ثانیه)
                            </label>
                            <input type="number" class="form-control" id="msgway_otp_expire_time" name="msgway_otp_expire_time"
                                   value="<?php echo htmlspecialchars($current_settings['msgway_otp_expire_time'] ?? '120'); ?>"
                                   min="60" max="600">
                            <small class="text-muted">معمولاً 120 ثانیه (2 دقیقه)</small>
                        </div>
                    </div>
                                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="msgway_send_method" class="form-label">
                                روش ارسال پیش‌فرض
                            </label>
                            <select class="form-select" id="msgway_send_method" name="msgway_send_method">
                                <option value="sms" <?php echo ($current_settings['msgway_send_method'] ?? 'sms') == 'sms' ? 'selected' : ''; ?>>پیامک (SMS)</option>
                                <option value="ivr" <?php echo ($current_settings['msgway_send_method'] ?? 'sms') == 'ivr' ? 'selected' : ''; ?>>تماس صوتی (IVR)</option>
                                <option value="messenger" <?php echo ($current_settings['msgway_send_method'] ?? 'sms') == 'messenger' ? 'selected' : ''; ?>>پیام‌رسان داخلی</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="msgway_resend_delay" class="form-label">
                                تاخیر ارسال مجدد (ثانیه)
                            </label>
                            <input type="number" class="form-control" id="msgway_resend_delay" name="msgway_resend_delay"
                                   value="<?php echo htmlspecialchars($current_settings['msgway_resend_delay'] ?? '60'); ?>"
                                   min="30" max="300">
                            <small class="text-muted">حداقل فاصله بین دو ارسال متوالی</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="msgway_mobile_format" class="form-label">
                            فرمت شماره موبایل ورودی
                        </label>
                        <select class="form-select" id="msgway_mobile_format" name="msgway_mobile_format">
                            <option value="09" <?php echo ($current_settings['msgway_mobile_format'] ?? '09') == '09' ? 'selected' : ''; ?>>09xxxxxxxxx (فرمت ایرانی)</option>
                            <option value="+98" <?php echo ($current_settings['msgway_mobile_format'] ?? '09') == '+98' ? 'selected' : ''; ?>>+989xxxxxxxxx (فرمت بین‌المللی)</option>
                            <option value="98" <?php echo ($current_settings['msgway_mobile_format'] ?? '09') == '98' ? 'selected' : ''; ?>>989xxxxxxxxx (بدون +)</option>
                        </select>
                        <small class="text-muted">نحوه قبول و نمایش شماره‌های موبایل در سیستم</small>
                    </div>
                                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>راهنما:</strong>
                        <ul class="mb-0 mt-2">
                            <li>برای دریافت API Key به <a href="https://msgway.com" target="_blank">پنل راه پیام</a> مراجعه کنید</li>
                            <li>الگوهای پیامکی باید از قبل در پنل راه پیام ثبت و تایید شده باشند</li>
                            <li>برای تست سرویس، ابتدا از بخش "الگوهای پیامک" یک الگوی تست ایجاد کنید</li>
                            <li>مستندات کامل: <a href="https://msgway.com/doc" target="_blank">msgway.com/doc</a></li>
                        </ul>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        ذخیره تنظیمات راه پیام
                    </button>
                    
                    <a href="sms/templates.php" class="btn btn-secondary">
                        <i class="fas fa-file-alt me-2"></i>
                        مدیریت الگوهای پیامک
                    </a>
                </form>
            </div>
        </div>


        <!-- تنظیمات ایمیل -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-envelope me-2 text-primary"></i>
                    تنظیمات ایمیل
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="email_settings">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="mail_host" class="form-label">سرور SMTP</label>
                            <input type="text" class="form-control" id="mail_host" name="mail_host" 
                                   value="<?php echo htmlspecialchars($current_settings['mail_host'] ?? ''); ?>"
                                   placeholder="smtp.gmail.com">
                        </div>
                        <div class="col-md-6">
                            <label for="mail_port" class="form-label">پورت</label>
                            <input type="number" class="form-control" id="mail_port" name="mail_port" 
                                   value="<?php echo htmlspecialchars($current_settings['mail_port'] ?? '587'); ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="mail_username" class="form-label">نام کاربری</label>
                            <input type="text" class="form-control" id="mail_username" name="mail_username" 
                                   value="<?php echo htmlspecialchars($current_settings['mail_username'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="mail_password" class="form-label">رمز عبور</label>
                            <input type="password" class="form-control" id="mail_password" name="mail_password" 
                                   value="<?php echo htmlspecialchars($current_settings['mail_password'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="mail_from_email" class="form-label">ایمیل فرستنده</label>
                            <input type="email" class="form-control" id="mail_from_email" name="mail_from_email" 
                                   value="<?php echo htmlspecialchars($current_settings['mail_from_email'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="mail_from_name" class="form-label">نام فرستنده</label>
                            <input type="text" class="form-control" id="mail_from_name" name="mail_from_name" 
                                   value="<?php echo htmlspecialchars($current_settings['mail_from_name'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="mail_encryption" class="form-label">نوع رمزگذاری</label>
                        <select class="form-select" id="mail_encryption" name="mail_encryption">
                            <option value="tls" <?php echo ($current_settings['mail_encryption'] ?? 'tls') == 'tls' ? 'selected' : ''; ?>>TLS</option>
                            <option value="ssl" <?php echo ($current_settings['mail_encryption'] ?? 'tls') == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                            <option value="" <?php echo ($current_settings['mail_encryption'] ?? 'tls') == '' ? 'selected' : ''; ?>>بدون رمزگذاری</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        ذخیره تنظیمات ایمیل
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- آمار سیستم و ابزارها -->
    <div class="col-lg-4">
        <!-- آمار سیستم -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-bar me-2 text-primary"></i>
                    آمار سیستم
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="h4 text-primary"><?php echo number_format($system_stats['total_users']); ?></div>
                        <small class="text-muted">کاربران</small>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="h4 text-success"><?php echo number_format($system_stats['total_customers']); ?></div>
                        <small class="text-muted">مشتریان</small>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="h4 text-warning"><?php echo number_format($system_stats['total_leads']); ?></div>
                        <small class="text-muted">لیدها</small>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="h4 text-info"><?php echo number_format($system_stats['total_tasks']); ?></div>
                        <small class="text-muted">وظایف</small>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="h4 text-danger"><?php echo number_format($system_stats['total_sales']); ?></div>
                        <small class="text-muted">فروش‌ها</small>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="h4 text-secondary"><?php echo $system_stats['database_size']; ?> MB</div>
                        <small class="text-muted">حجم دیتابیس</small>
                    </div>
                </div>
                
                <div class="mt-3 pt-3 border-top">
                    <div class="d-flex justify-content-between">
                        <span>تعداد لاگ‌ها:</span>
                        <span class="fw-bold"><?php echo number_format($system_stats['log_count']); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ابزارهای سیستم -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-tools me-2 text-primary"></i>
                    ابزارهای سیستم
                </h5>
            </div>
            <div class="card-body">
                <!-- پشتیبان‌گیری -->
                <form method="POST" class="mb-3">
                    <input type="hidden" name="action" value="backup_database">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <button type="submit" class="btn btn-success w-100" onclick="return confirm('آیا از پشتیبان‌گیری مطمئن هستید؟')">
                        <i class="fas fa-download me-2"></i>
                        پشتیبان‌گیری از دیتابیس
                    </button>
                </form>
                
                <!-- پاک کردن لاگ‌ها -->
                <form method="POST" class="mb-3">
                    <input type="hidden" name="action" value="clear_logs">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="mb-2">
                        <label for="days_to_keep" class="form-label">حفظ لاگ‌های</label>
                        <select class="form-select" id="days_to_keep" name="days_to_keep">
                            <option value="30">30 روز اخیر</option>
                            <option value="60">60 روز اخیر</option>
                            <option value="90">90 روز اخیر</option>
                            <option value="180">180 روز اخیر</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-warning w-100" onclick="return confirm('آیا از پاک کردن لاگ‌های قدیمی مطمئن هستید؟')">
                        <i class="fas fa-trash me-2"></i>
                        پاک کردن لاگ‌های قدیمی
                    </button>
                </form>
                
                <!-- مشاهده لاگ‌ها -->
                <a href="activity_logs.php" class="btn btn-info w-100">
                    <i class="fas fa-list me-2"></i>
                    مشاهده لاگ‌های فعالیت
                </a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../private/footer.php'; ?>
