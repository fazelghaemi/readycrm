<?php
/**
 * مرحله 2: اطلاعات دیتابیس
 */

$error = '';
$success = '';

// پردازش فرم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = trim($_POST['db_host'] ?? '');
    $db_name = trim($_POST['db_name'] ?? '');
    $db_user = trim($_POST['db_user'] ?? '');
    $db_pass = $_POST['db_pass'] ?? '';
    
    // اعتبارسنجی
    if (empty($db_host) || empty($db_name) || empty($db_user)) {
        $error = 'لطفاً تمام فیلدهای الزامی را پر کنید.';
    } else {
        // تست اتصال
        $test = test_database_connection($db_host, $db_user, $db_pass, $db_name);
        
        if (!$test['success']) {
            $error = 'خطا در اتصال به دیتابیس: ' . htmlspecialchars($test['error']);
        } else {
            // ذخیره در session
            $_SESSION['db_config'] = [
                'host' => $db_host,
                'name' => $db_name,
                'user' => $db_user,
                'pass' => $db_pass
            ];
            
            // ایجاد فایل config
            $config_result = create_config_file($db_host, $db_name, $db_user, $db_pass);
            
            if ($config_result['success']) {
                header('Location: ?step=3');
                exit;
            } else {
                $error = 'خطا در ایجاد فایل تنظیمات: ' . $config_result['error'];
            }
        }
    }
}

// مقادیر پیش‌فرض
$db_host = $_POST['db_host'] ?? 'localhost';
$db_name = $_POST['db_name'] ?? 'crm_v2';
$db_user = $_POST['db_user'] ?? 'root';
$db_pass = $_POST['db_pass'] ?? '';
?>

<h2 class="section-title">🗄️ اطلاعات دیتابیس</h2>

<p class="section-description">
    لطفاً اطلاعات اتصال به پایگاه داده MySQL خود را وارد کنید. این اطلاعات از طریق میزبان وب شما در دسترس است.
</p>

<?php if ($error): ?>
<div class="alert alert-error">
    <strong>❌ خطا!</strong><br>
    <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<div class="alert alert-info">
    <strong>💡 نکته مهم:</strong><br>
    قبل از ادامه، مطمئن شوید که یک دیتابیس MySQL با کاراکتر ست <code>utf8mb4</code> ایجاد کرده‌اید.
</div>

<form method="POST" action="">
    <div class="form-group">
        <label for="db_host">آدرس سرور دیتابیس *</label>
        <input type="text" 
               id="db_host" 
               name="db_host" 
               value="<?php echo htmlspecialchars($db_host); ?>" 
               required 
               placeholder="localhost">
        <div class="form-hint">معمولاً <code>localhost</code> است</div>
    </div>

    <div class="form-group">
        <label for="db_name">نام دیتابیس *</label>
        <input type="text" 
               id="db_name" 
               name="db_name" 
               value="<?php echo htmlspecialchars($db_name); ?>" 
               required 
               placeholder="crm_v2">
        <div class="form-hint">نام دیتابیسی که قبلاً ایجاد کرده‌اید</div>
    </div>

    <div class="form-group">
        <label for="db_user">نام کاربری دیتابیس *</label>
        <input type="text" 
               id="db_user" 
               name="db_user" 
               value="<?php echo htmlspecialchars($db_user); ?>" 
               required 
               placeholder="root">
        <div class="form-hint">کاربری که دسترسی به دیتابیس دارد</div>
    </div>

    <div class="form-group">
        <label for="db_pass">رمز عبور دیتابیس</label>
        <input type="password" 
               id="db_pass" 
               name="db_pass" 
               value="<?php echo htmlspecialchars($db_pass); ?>" 
               placeholder="رمز عبور (اختیاری)">
        <div class="form-hint">در محیط لوکال ممکن است خالی باشد</div>
    </div>

    <div class="btn-group">
        <a href="?step=1" class="btn btn-secondary">
            ➡️ قبلی
        </a>
        
        <button type="submit" class="btn btn-primary">
            تست اتصال و ادامه
            ⬅️
        </button>
    </div>
</form>
