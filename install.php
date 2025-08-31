<?php
session_start();

// اگر فایل config موجود باشد، نصب قبلاً انجام شده
if (file_exists('config/config.php')) {
    require_once 'config/config.php';
    if (defined('APP_NAME')) {
        header('Location: index.php');
        exit();
    }
}

$step = (int)($_GET['step'] ?? 1);
$errors = [];
$success_messages = [];

// پردازش مراحل
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_step = (int)($_POST['step'] ?? 1);
    
    switch ($current_step) {
        case 1:
            // بررسی پیش‌نیازها
            $step = 2;
            break;
            
        case 2:
            // تنظیمات دیتابیس
            $db_host = $_POST['db_host'] ?? 'localhost';
            $db_name = $_POST['db_name'] ?? '';
            $db_user = $_POST['db_user'] ?? '';
            $db_pass = $_POST['db_pass'] ?? '';
            
            if (empty($db_name)) {
                $errors[] = 'نام دیتابیس الزامی است';
            }
            
            if (empty($errors)) {
                try {
                    // تست اتصال به دیتابیس
                    $dsn = "mysql:host=$db_host;charset=utf8mb4";
                    $pdo = new PDO($dsn, $db_user, $db_pass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                    ]);
                    
                    // ایجاد دیتابیس
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $pdo->exec("USE `$db_name`");
                    
                    // ذخیره اطلاعات دیتابیس در session
                    $_SESSION['install_db'] = [
                        'host' => $db_host,
                        'name' => $db_name,
                        'user' => $db_user,
                        'pass' => $db_pass
                    ];
                    
                    $step = 3;
                    $success_messages[] = 'اتصال به دیتابیس با موفقیت برقرار شد';
                    
                } catch (PDOException $e) {
                    $errors[] = 'خطا در اتصال به دیتابیس: ' . $e->getMessage();
                }
            }
            break;
            
        case 3:
            // ایجاد جداول
            $db_config = $_SESSION['install_db'] ?? null;
            if (!$db_config) {
                header('Location: install.php?step=2');
                exit();
            }
            
            try {
                $dsn = "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8mb4";
                $pdo = new PDO($dsn, $db_config['user'], $db_config['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
                
                // خواندن فایل schema
                $schema = file_get_contents('database/schema.sql');
                
                // تبدیل به آرایه statements
                $statements = array_filter(
                    array_map('trim', explode(';', $schema)),
                    function($stmt) { return !empty($stmt); }
                );
                
                // اجرای statements
                foreach ($statements as $statement) {
                    if (!empty(trim($statement))) {
                        $pdo->exec($statement);
                    }
                }
                
                $step = 4;
                $success_messages[] = 'جداول دیتابیس با موفقیت ایجاد شدند';
                
            } catch (PDOException $e) {
                $errors[] = 'خطا در ایجاد جداول: ' . $e->getMessage();
            }
            break;
            
        case 4:
            // ایجاد کاربر ادمین
            $admin_name = $_POST['admin_name'] ?? '';
            $admin_lastname = $_POST['admin_lastname'] ?? '';
            $admin_email = $_POST['admin_email'] ?? '';
            $admin_password = $_POST['admin_password'] ?? '';
            $admin_confirm = $_POST['admin_confirm'] ?? '';
            
            if (empty($admin_name)) $errors[] = 'نام ادمین الزامی است';
            if (empty($admin_lastname)) $errors[] = 'نام خانوادگی ادمین الزامی است';
            if (empty($admin_email)) $errors[] = 'ایمیل ادمین الزامی است';
            if (empty($admin_password)) $errors[] = 'رمز عبور الزامی است';
            if ($admin_password !== $admin_confirm) $errors[] = 'رمز عبور و تکرار آن یکسان نیستند';
            if (strlen($admin_password) < 6) $errors[] = 'رمز عبور باید حداقل 6 کاراکتر باشد';
            
            if (empty($errors)) {
                $db_config = $_SESSION['install_db'];
                
                try {
                    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8mb4";
                    $pdo = new PDO($dsn, $db_config['user'], $db_config['pass'], [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]);
                    
                    // ایجاد کاربر ادمین
                    $hashed_password = password_hash($admin_password, PASSWORD_ARGON2ID);
                    $stmt = $pdo->prepare("
                        INSERT INTO users (first_name, last_name, email, password, role, status, created_at) 
                        VALUES (?, ?, ?, ?, 'admin', 'active', NOW())
                    ");
                    $stmt->execute([$admin_name, $admin_lastname, $admin_email, $hashed_password]);
                    
                    $step = 5;
                    $success_messages[] = 'کاربر ادمین با موفقیت ایجاد شد';
                    
                } catch (PDOException $e) {
                    $errors[] = 'خطا در ایجاد کاربر ادمین: ' . $e->getMessage();
                }
            }
            break;
            
        case 5:
            // ایجاد فایل config
            $db_config = $_SESSION['install_db'];
            $app_name = $_POST['app_name'] ?? 'سیستم مدیریت ارتباط با مشتری';
            $base_url = $_POST['base_url'] ?? '';
            
            if (empty($base_url)) {
                $base_url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/';
                $base_url = rtrim($base_url, '/') . '/';
            }
            
            $encryption_key = bin2hex(random_bytes(32));
            
            $config_content = "<?php
// تنظیمات اصلی سیستم
define('APP_NAME', '" . addslashes($app_name) . "');
define('APP_VERSION', '1.0.0');
define('BASE_URL', '" . addslashes($base_url) . "');

// تنظیمات دیتابیس
define('DB_HOST', '" . addslashes($db_config['host']) . "');
define('DB_NAME', '" . addslashes($db_config['name']) . "');
define('DB_USER', '" . addslashes($db_config['user']) . "');
define('DB_PASS', '" . addslashes($db_config['pass']) . "');
define('DB_CHARSET', 'utf8mb4');

// تنظیمات امنیتی
define('ENCRYPTION_KEY', '$encryption_key');

// تنظیمات جلسه
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', " . (isset($_SERVER['HTTPS']) ? '1' : '0') . ");
ini_set('session.use_strict_mode', 1);

// تنظیمات خطا (در محیط تولید false کنید)
define('DEBUG_MODE', false);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// تنظیمات منطقه زمانی
date_default_timezone_set('Asia/Tehran');

// تنظیمات زبان
setlocale(LC_TIME, 'fa_IR.UTF-8', 'Persian_Iran.utf8', 'fa_IR', 'Persian');
?>";

            if (file_put_contents('config/config.php', $config_content)) {
                // پاک کردن session نصب
                unset($_SESSION['install_db']);
                
                $step = 6;
                $success_messages[] = 'فایل تنظیمات با موفقیت ایجاد شد';
            } else {
                $errors[] = 'خطا در ایجاد فایل تنظیمات. مجوزهای پوشه config را بررسی کنید.';
            }
            break;
    }
}

// بررسی پیش‌نیازها
function checkRequirements() {
    $requirements = [
        'PHP Version >= 8.0' => version_compare(PHP_VERSION, '8.0.0', '>='),
        'PDO Extension' => extension_loaded('pdo'),
        'PDO MySQL' => extension_loaded('pdo_mysql'),
        'mbstring Extension' => extension_loaded('mbstring'),
        'JSON Extension' => extension_loaded('json'),
        'Config Directory Writable' => is_writable('config/'),
        'Database Schema File' => file_exists('database/schema.sql'),
    ];
    
    return $requirements;
}

$requirements = checkRequirements();
$all_requirements_met = !in_array(false, $requirements, true);

?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نصب سیستم CRM</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #ff6b35;
            --primary-dark: #e55a2b;
            --primary-light: #ff8660;
            --primary-ultralight: #fff2ef;
            --text-dark: #1a1a1a;
            --text-medium: #4a4a4a;
            --text-light: #6b7280;
            --text-muted: #9ca3af;
            --border-color: #e5e7eb;
            --bg-light: #fafafa;
            --bg-card: #ffffff;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
        }
        
        body {
            font-family: 'Vazirmatn', sans-serif;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .install-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 0;
            width: 100%;
            max-width: 800px;
            overflow: hidden;
        }
        
        .install-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .install-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .install-header p {
            margin: 0;
            opacity: 0.9;
        }
        
        .install-body {
            padding: 40px;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
            position: relative;
        }
        
        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            flex: 1;
            max-width: 120px;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-bottom: 8px;
            position: relative;
            z-index: 2;
        }
        
        .step-item.active .step-number {
            background: var(--primary-color);
            color: white;
        }
        
        .step-item.completed .step-number {
            background: var(--success-color);
            color: white;
        }
        
        .step-item:not(.active):not(.completed) .step-number {
            background: var(--bg-light);
            color: var(--text-muted);
            border: 2px solid var(--border-color);
        }
        
        .step-title {
            font-size: 0.85rem;
            text-align: center;
            color: var(--text-medium);
        }
        
        .step-item.active .step-title {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .step-item.completed .step-title {
            color: var(--success-color);
        }
        
        .step-line {
            position: absolute;
            top: 20px;
            right: 60px;
            left: 60px;
            height: 2px;
            background: var(--border-color);
            z-index: 1;
        }
        
        .step-item.completed + .step-item .step-line {
            background: var(--success-color);
        }
        
        .requirement-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .requirement-item:last-child {
            border-bottom: none;
        }
        
        .requirement-status {
            font-weight: 600;
        }
        
        .status-ok {
            color: var(--success-color);
        }
        
        .status-error {
            color: var(--danger-color);
        }
        
        .alert {
            border: none;
            border-radius: 12px;
            padding: 16px 20px;
        }
        
        .alert-success {
            background: #ecfdf5;
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }
        
        .alert-danger {
            background: #fef2f2;
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }
        
        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 8px;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
        }
        
        .btn-secondary {
            background: var(--text-light);
            border-color: var(--text-light);
            color: white;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 8px;
        }
        
        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 12px 16px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(255, 107, 53, 0.25);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--text-medium);
            margin-bottom: 8px;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: var(--success-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 2rem;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <h1><i class="fas fa-rocket me-2"></i>نصب سیستم CRM</h1>
            <p>مرحله به مرحله سیستم مدیریت ارتباط با مشتری را نصب کنید</p>
        </div>
        
        <div class="install-body">
            <!-- نمایش پیام‌ها -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success_messages)): ?>
                <div class="alert alert-success">
                    <ul class="mb-0">
                        <?php foreach ($success_messages as $message): ?>
                            <li><?php echo htmlspecialchars($message); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- نمایشگر مراحل -->
            <div class="step-indicator">
                <?php
                $steps = [
                    1 => 'بررسی پیش‌نیازها',
                    2 => 'تنظیمات دیتابیس',
                    3 => 'ایجاد جداول',
                    4 => 'کاربر ادمین',
                    5 => 'تنظیمات نهایی',
                    6 => 'تکمیل نصب'
                ];
                
                foreach ($steps as $step_num => $step_title):
                    $class = '';
                    if ($step_num < $step) $class = 'completed';
                    elseif ($step_num == $step) $class = 'active';
                ?>
                    <div class="step-item <?php echo $class; ?>">
                        <?php if ($step_num < $step): ?>
                            <div class="step-line"></div>
                        <?php endif; ?>
                        <div class="step-number">
                            <?php if ($step_num < $step): ?>
                                <i class="fas fa-check"></i>
                            <?php else: ?>
                                <?php echo $step_num; ?>
                            <?php endif; ?>
                        </div>
                        <div class="step-title"><?php echo $step_title; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- محتوای مراحل -->
            <?php if ($step == 1): ?>
                <!-- مرحله 1: بررسی پیش‌نیازها -->
                <div class="text-center mb-4">
                    <h3>بررسی پیش‌نیازهای سیستم</h3>
                    <p class="text-muted">لطفاً اطمینان حاصل کنید که تمام پیش‌نیازها برآورده شده‌اند</p>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <?php foreach ($requirements as $req_name => $status): ?>
                            <div class="requirement-item">
                                <span><?php echo $req_name; ?></span>
                                <span class="requirement-status <?php echo $status ? 'status-ok' : 'status-error'; ?>">
                                    <?php if ($status): ?>
                                        <i class="fas fa-check-circle me-1"></i>موجود
                                    <?php else: ?>
                                        <i class="fas fa-times-circle me-1"></i>موجود نیست
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <?php if ($all_requirements_met): ?>
                        <form method="post">
                            <input type="hidden" name="step" value="1">
                            <button type="submit" class="btn btn-primary">
                                ادامه نصب
                                <i class="fas fa-arrow-left ms-2"></i>
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            لطفاً ابتدا تمام پیش‌نیازها را برآورده کنید
                        </div>
                    <?php endif; ?>
                </div>
                
            <?php elseif ($step == 2): ?>
                <!-- مرحله 2: تنظیمات دیتابیس -->
                <div class="text-center mb-4">
                    <h3>تنظیمات دیتابیس</h3>
                    <p class="text-muted">اطلاعات اتصال به دیتابیس MySQL را وارد کنید</p>
                </div>
                
                <form method="post">
                    <input type="hidden" name="step" value="2">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">آدرس سرور دیتابیس</label>
                            <input type="text" class="form-control" name="db_host" 
                                   value="<?php echo htmlspecialchars($_POST['db_host'] ?? 'localhost'); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">نام دیتابیس</label>
                            <input type="text" class="form-control" name="db_name" 
                                   value="<?php echo htmlspecialchars($_POST['db_name'] ?? 'crm_system'); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">نام کاربری دیتابیس</label>
                            <input type="text" class="form-control" name="db_user" 
                                   value="<?php echo htmlspecialchars($_POST['db_user'] ?? 'root'); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">رمز عبور دیتابیس</label>
                            <input type="password" class="form-control" name="db_pass" 
                                   value="<?php echo htmlspecialchars($_POST['db_pass'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">
                            تست اتصال و ادامه
                            <i class="fas fa-arrow-left ms-2"></i>
                        </button>
                    </div>
                </form>
                
            <?php elseif ($step == 3): ?>
                <!-- مرحله 3: ایجاد جداول -->
                <div class="text-center mb-4">
                    <h3>ایجاد ساختار دیتابیس</h3>
                    <p class="text-muted">جداول مورد نیاز سیستم ایجاد خواهند شد</p>
                </div>
                
                <div class="text-center">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">در حال ایجاد جداول...</span>
                    </div>
                    <p>لطفاً صبر کنید...</p>
                </div>
                
                <form method="post" id="createTablesForm">
                    <input type="hidden" name="step" value="3">
                </form>
                
                <script>
                    setTimeout(function() {
                        document.getElementById('createTablesForm').submit();
                    }, 2000);
                </script>
                
            <?php elseif ($step == 4): ?>
                <!-- مرحله 4: ایجاد کاربر ادمین -->
                <div class="text-center mb-4">
                    <h3>ایجاد کاربر مدیر</h3>
                    <p class="text-muted">حساب کاربری مدیر سیستم را ایجاد کنید</p>
                </div>
                
                <form method="post">
                    <input type="hidden" name="step" value="4">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">نام</label>
                            <input type="text" class="form-control" name="admin_name" 
                                   value="<?php echo htmlspecialchars($_POST['admin_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">نام خانوادگی</label>
                            <input type="text" class="form-control" name="admin_lastname" 
                                   value="<?php echo htmlspecialchars($_POST['admin_lastname'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label class="form-label">ایمیل</label>
                            <input type="email" class="form-control" name="admin_email" 
                                   value="<?php echo htmlspecialchars($_POST['admin_email'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">رمز عبور</label>
                            <input type="password" class="form-control" name="admin_password" required>
                            <small class="text-muted">حداقل 6 کاراکتر</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">تکرار رمز عبور</label>
                            <input type="password" class="form-control" name="admin_confirm" required>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">
                            ایجاد کاربر مدیر
                            <i class="fas fa-arrow-left ms-2"></i>
                        </button>
                    </div>
                </form>
                
            <?php elseif ($step == 5): ?>
                <!-- مرحله 5: تنظیمات نهایی -->
                <div class="text-center mb-4">
                    <h3>تنظیمات نهایی</h3>
                    <p class="text-muted">تنظیمات کلی سیستم را تکمیل کنید</p>
                </div>
                
                <form method="post">
                    <input type="hidden" name="step" value="5">
                    
                    <div class="mb-3">
                        <label class="form-label">نام سیستم</label>
                        <input type="text" class="form-control" name="app_name" 
                               value="<?php echo htmlspecialchars($_POST['app_name'] ?? 'سیستم مدیریت ارتباط با مشتری'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">آدرس وب‌سایت</label>
                        <input type="url" class="form-control" name="base_url" 
                               value="<?php echo htmlspecialchars($_POST['base_url'] ?? ''); ?>" 
                               placeholder="http://example.com/">
                        <small class="text-muted">در صورت خالی گذاشتن، به طور خودکار تشخیص داده می‌شود</small>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">
                            تکمیل نصب
                            <i class="fas fa-check ms-2"></i>
                        </button>
                    </div>
                </form>
                
            <?php elseif ($step == 6): ?>
                <!-- مرحله 6: تکمیل نصب -->
                <div class="text-center">
                    <div class="success-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <h3 class="text-success mb-3">نصب با موفقیت تکمیل شد!</h3>
                    <p class="text-muted mb-4">
                        سیستم مدیریت ارتباط با مشتری شما آماده استفاده است.
                        <br>اکنون می‌توانید وارد سیستم شوید.
                    </p>
                    
                    <div class="d-flex gap-3 justify-content-center">
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            ورود به سیستم
                        </a>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            داشبورد
                        </a>
                    </div>
                    
                    <div class="alert alert-info mt-4">
                        <strong>نکته امنیتی:</strong>
                        بعد از ورود به سیستم، فایل install.php را حذف کنید یا نام آن را تغییر دهید.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
