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

<!-- ========== SVG Sprite (hidden) ========== -->
<svg style="display:none;" width="0" height="0">
    <symbol id="svg-settings" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="3"/><path d="M12 1v6m0 6v6m-9-9h6m6 0h6"/>
        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
    </symbol>
    <symbol id="svg-mail" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
    </symbol>
    <symbol id="svg-chart-bar" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
    </symbol>
    <symbol id="svg-tool" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
    </symbol>
    <symbol id="svg-save" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
    </symbol>
    <symbol id="svg-download" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
    </symbol>
    <symbol id="svg-trash" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
    </symbol>
    <symbol id="svg-list" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
    </symbol>
    <symbol id="svg-users" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
    </symbol>
    <symbol id="svg-user-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17 11 19 13 23 9"/>
    </symbol>
    <symbol id="svg-briefcase" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
    </symbol>
    <symbol id="svg-clipboard" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
    </symbol>
    <symbol id="svg-shopping-cart" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
    </symbol>
    <symbol id="svg-database" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
    </symbol>
</svg>

<!-- ========== Settings Page Styles ========== -->
<style>
:root {
    --teal:        #14b8a6;
    --teal-light:  #5eead4;
    --teal-dark:   #0d9488;
    --teal-bg:     #ccfbf1;
    --teal-50:     #f0fdfa;
    --page-bg:     #f8fafb;
    --card-bg:     #ffffff;
    --text-1:      #0f172a;
    --text-2:      #475569;
    --text-3:      #64748b;
    --text-muted:  #94a3b8;
    --border:      #e2e8f0;
    --border-mid:  #cbd5e1;
    --shadow-sm:   0 1px 3px  rgba(0,0,0,.06);
    --shadow-md:   0 4px 12px rgba(0,0,0,.08);
    --shadow-lg:   0 8px 24px rgba(0,0,0,.10);
    --r-xl:  20px;
    --r-lg:  16px;
    --r-md:  12px;
    --r-sm:  8px;
    --ease: cubic-bezier(.4,0,.2,1);
}

.settings-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 28px;
    flex-wrap: wrap;
    gap: 16px;
}

.settings-page-header h4 {
    font-size: 22px;
    font-weight: 700;
    color: var(--text-1);
    margin: 0 0 4px;
}

.settings-page-header p {
    font-size: 14px;
    color: var(--text-3);
    margin: 0;
}

.settings-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: var(--r-xl);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    margin-bottom: 24px;
}

.settings-card-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 20px 24px;
    background: var(--page-bg);
    border-bottom: 1px solid var(--border);
}

.settings-card-header h5 {
    font-size: 16px;
    font-weight: 700;
    color: var(--text-1);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.settings-card-header h5 svg { color: var(--teal); }

.settings-card-body {
    padding: 24px;
}

.form-label-2026 {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-2);
    margin-bottom: 6px;
}

.form-control-2026,
.form-select-2026 {
    width: 100%;
    border: 1.5px solid var(--border);
    border-radius: var(--r-md);
    padding: 9px 14px;
    font-size: 14px;
    font-family: 'Vazirmatn', sans-serif;
    color: var(--text-1);
    background: var(--page-bg);
    transition: border-color .2s, box-shadow .2s, background .2s;
    outline: none;
}

.form-control-2026:focus,
.form-select-2026:focus {
    border-color: var(--teal);
    box-shadow: 0 0 0 3px rgba(20,184,166,.18);
    background: #fff;
}

.btn-save-settings {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--teal);
    color: #fff;
    border: none;
    border-radius: var(--r-md);
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 600;
    font-family: 'Vazirmatn', sans-serif;
    cursor: pointer;
    transition: background .2s var(--ease), transform .15s var(--ease), box-shadow .2s var(--ease);
    box-shadow: var(--shadow-sm);
}

.btn-save-settings:hover {
    background: var(--teal-dark);
    color: #fff;
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.stat-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: var(--r-lg);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    margin-bottom: 24px;
}

.stat-card-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 18px 20px;
    background: var(--page-bg);
    border-bottom: 1px solid var(--border);
}

.stat-card-header h5 {
    font-size: 15px;
    font-weight: 700;
    color: var(--text-1);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.stat-card-header h5 svg { color: var(--teal); }

.stat-card-body {
    padding: 20px;
}

.stat-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px;
    text-align: center;
}

.stat-item {
    padding: 12px;
    background: var(--teal-50);
    border-radius: var(--r-md);
    transition: transform .2s var(--ease), box-shadow .2s var(--ease);
}

.stat-item:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 12px;
    font-weight: 500;
    color: var(--text-3);
}

.stat-value.text-teal   { color: var(--teal); }
.stat-value.text-green  { color: #10b981; }
.stat-value.text-amber  { color: #f59e0b; }
.stat-value.text-blue   { color: #3b82f6; }
.stat-value.text-red    { color: #ef4444; }
.stat-value.text-gray   { color: var(--text-2); }

.log-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--border);
    font-size: 14px;
}

.log-info-label {
    color: var(--text-3);
    font-weight: 500;
}

.log-info-value {
    color: var(--text-1);
    font-weight: 700;
}

.tools-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: var(--r-lg);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}

.tools-card-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 18px 20px;
    background: var(--page-bg);
    border-bottom: 1px solid var(--border);
}

.tools-card-header h5 {
    font-size: 15px;
    font-weight: 700;
    color: var(--text-1);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tools-card-header h5 svg { color: var(--teal); }

.tools-card-body {
    padding: 20px;
}

.btn-tool {
    width: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: none;
    border-radius: var(--r-md);
    padding: 10px 18px;
    font-size: 14px;
    font-weight: 600;
    font-family: 'Vazirmatn', sans-serif;
    cursor: pointer;
    transition: all .2s var(--ease);
    box-shadow: var(--shadow-sm);
    text-decoration: none;
    margin-bottom: 12px;
}

.btn-tool:last-child { margin-bottom: 0; }

.btn-tool-success {
    background: #10b981;
    color: #fff;
}

.btn-tool-success:hover {
    background: #059669;
    color: #fff;
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.btn-tool-warning {
    background: #f59e0b;
    color: #fff;
}

.btn-tool-warning:hover {
    background: #d97706;
    color: #fff;
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.btn-tool-info {
    background: #3b82f6;
    color: #fff;
}

.btn-tool-info:hover {
    background: #2563eb;
    color: #fff;
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

@media (max-width: 768px) {
    .stat-grid { grid-template-columns: 1fr; }
}
</style>

<!-- ========== Page Header ========== -->
<div class="settings-page-header">
    <div>
        <h4>تنظیمات سیستم</h4>
        <p>مدیریت تنظیمات کلی سیستم</p>
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
        <div class="settings-card">
            <div class="settings-card-header">
                <h5>
                    <svg width="20" height="20"><use href="#svg-settings"/></svg>
                    تنظیمات عمومی
                </h5>
            </div>
            <div class="settings-card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="general_settings">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="company_name" class="form-label-2026">نام شرکت</label>
                            <input type="text" class="form-control-2026" id="company_name" name="company_name"
                                   value="<?php echo htmlspecialchars($current_settings['company_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="company_phone" class="form-label-2026">تلفن شرکت</label>
                            <input type="text" class="form-control-2026" id="company_phone" name="company_phone"
                                   value="<?php echo htmlspecialchars($current_settings['company_phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="company_email" class="form-label-2026">ایمیل شرکت</label>
                            <input type="email" class="form-control-2026" id="company_email" name="company_email"
                                   value="<?php echo htmlspecialchars($current_settings['company_email'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="currency" class="form-label-2026">واحد پول</label>
                            <input type="text" class="form-control-2026" id="currency" name="currency"
                                   value="<?php echo htmlspecialchars($current_settings['currency'] ?? 'تومان'); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="company_address" class="form-label-2026">آدرس شرکت</label>
                        <textarea class="form-control-2026" id="company_address" name="company_address" rows="3"><?php echo htmlspecialchars($current_settings['company_address'] ?? ''); ?></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="tax_rate" class="form-label-2026">نرخ مالیات (%)</label>
                            <input type="number" class="form-control-2026" id="tax_rate" name="tax_rate"
                                   min="0" max="100" value="<?php echo $current_settings['tax_rate'] ?? 9; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="records_per_page" class="form-label-2026">تعداد رکورد در صفحه</label>
                            <select class="form-select-2026" id="records_per_page" name="records_per_page">
                                <option value="10" <?php echo ($current_settings['records_per_page'] ?? 20) == 10 ? 'selected' : ''; ?>>10</option>
                                <option value="20" <?php echo ($current_settings['records_per_page'] ?? 20) == 20 ? 'selected' : ''; ?>>20</option>
                                <option value="50" <?php echo ($current_settings['records_per_page'] ?? 20) == 50 ? 'selected' : ''; ?>>50</option>
                                <option value="100" <?php echo ($current_settings['records_per_page'] ?? 20) == 100 ? 'selected' : ''; ?>>100</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn-save-settings">
                        <svg width="16" height="16"><use href="#svg-save"/></svg>
                        ذخیره تنظیمات
                    </button>
                </form>
            </div>
        </div>

        <!-- تنظیمات ایمیل -->
        <div class="settings-card">
            <div class="settings-card-header">
                <h5>
                    <svg width="20" height="20"><use href="#svg-mail"/></svg>
                    تنظیمات ایمیل
                </h5>
            </div>
            <div class="settings-card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="email_settings">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="mail_host" class="form-label-2026">سرور SMTP</label>
                            <input type="text" class="form-control-2026" id="mail_host" name="mail_host"
                                   value="<?php echo htmlspecialchars($current_settings['mail_host'] ?? ''); ?>"
                                   placeholder="smtp.gmail.com">
                        </div>
                        <div class="col-md-4">
                            <label for="mail_port" class="form-label-2026">پورت</label>
                            <input type="number" class="form-control-2026" id="mail_port" name="mail_port"
                                   value="<?php echo $current_settings['mail_port'] ?? 587; ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="mail_username" class="form-label-2026">نام کاربری</label>
                            <input type="text" class="form-control-2026" id="mail_username" name="mail_username"
                                   value="<?php echo htmlspecialchars($current_settings['mail_username'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="mail_password" class="form-label-2026">رمز عبور</label>
                            <input type="password" class="form-control-2026" id="mail_password" name="mail_password"
                                   value="<?php echo htmlspecialchars($current_settings['mail_password'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="mail_from_email" class="form-label-2026">ایمیل فرستنده</label>
                            <input type="email" class="form-control-2026" id="mail_from_email" name="mail_from_email"
                                   value="<?php echo htmlspecialchars($current_settings['mail_from_email'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="mail_from_name" class="form-label-2026">نام فرستنده</label>
                            <input type="text" class="form-control-2026" id="mail_from_name" name="mail_from_name"
                                   value="<?php echo htmlspecialchars($current_settings['mail_from_name'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="mail_encryption" class="form-label-2026">نوع رمزگذاری</label>
                        <select class="form-select-2026" id="mail_encryption" name="mail_encryption">
                            <option value="tls" <?php echo ($current_settings['mail_encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                            <option value="ssl" <?php echo ($current_settings['mail_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                            <option value="" <?php echo empty($current_settings['mail_encryption']) ? 'selected' : ''; ?>>بدون رمزگذاری</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-save-settings">
                        <svg width="16" height="16"><use href="#svg-save"/></svg>
                        ذخیره تنظیمات ایمیل
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- آمار سیستم و ابزارها -->
    <div class="col-lg-4">
        <!-- آمار سیستم -->
        <div class="stat-card">
            <div class="stat-card-header">
                <h5>
                    <svg width="18" height="18"><use href="#svg-chart-bar"/></svg>
                    آمار سیستم
                </h5>
            </div>
            <div class="stat-card-body">
                <div class="stat-grid">
                    <div class="stat-item">
                        <div class="stat-value text-teal"><?php echo number_format($system_stats['total_users']); ?></div>
                        <div class="stat-label">کاربران</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value text-green"><?php echo number_format($system_stats['total_customers']); ?></div>
                        <div class="stat-label">مشتریان</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value text-amber"><?php echo number_format($system_stats['total_leads']); ?></div>
                        <div class="stat-label">لیدها</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value text-blue"><?php echo number_format($system_stats['total_tasks']); ?></div>
                        <div class="stat-label">وظایف</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value text-red"><?php echo number_format($system_stats['total_sales']); ?></div>
                        <div class="stat-label">فروش‌ها</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value text-gray"><?php echo $system_stats['database_size']; ?> MB</div>
                        <div class="stat-label">حجم دیتابیس</div>
                    </div>
                </div>

                <div class="log-info">
                    <span class="log-info-label">تعداد لاگ‌ها:</span>
                    <span class="log-info-value"><?php echo number_format($system_stats['log_count']); ?></span>
                </div>
            </div>
        </div>

        <!-- ابزارهای سیستم -->
        <div class="tools-card">
            <div class="tools-card-header">
                <h5>
                    <svg width="18" height="18"><use href="#svg-tool"/></svg>
                    ابزارهای سیستم
                </h5>
            </div>
            <div class="tools-card-body">
                <!-- پشتیبان‌گیری -->
                <form method="POST" style="margin-bottom:12px;">
                    <input type="hidden" name="action" value="backup_database">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <button type="submit" class="btn-tool btn-tool-success" onclick="return confirm('آیا از پشتیبان‌گیری مطمئن هستید؟')">
                        <svg width="16" height="16"><use href="#svg-download"/></svg>
                        پشتیبان‌گیری از دیتابیس
                    </button>
                </form>

                <!-- پاک کردن لاگ‌ها -->
                <form method="POST" style="margin-bottom:12px;">
                    <input type="hidden" name="action" value="clear_logs">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div style="margin-bottom:10px;">
                        <label for="days_to_keep" class="form-label-2026">حفظ لاگ‌های</label>
                        <select class="form-select-2026" id="days_to_keep" name="days_to_keep">
                            <option value="30">30 روز اخیر</option>
                            <option value="60">60 روز اخیر</option>
                            <option value="90">90 روز اخیر</option>
                            <option value="180">180 روز اخیر</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-tool btn-tool-warning" onclick="return confirm('آیا از پاک کردن لاگ‌های قدیمی مطمئن هستید؟')">
                        <svg width="16" height="16"><use href="#svg-trash"/></svg>
                        پاک کردن لاگ‌های قدیمی
                    </button>
                </form>

                <!-- مشاهده لاگ‌ها -->
                <a href="activity_logs.php" class="btn-tool btn-tool-info">
                    <svg width="16" height="16"><use href="#svg-list"/></svg>
                    مشاهده لاگ‌های فعالیت
                </a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../private/footer.php'; ?>
