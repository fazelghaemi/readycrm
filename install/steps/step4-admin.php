<?php
/**
 * مرحله 4: ایجاد کاربر مدیر اولیه
 */

if (!isset($_SESSION['migrations_done']) || !$_SESSION['migrations_done']) {
    header('Location: ?step=3');
    exit;
}

$db_config = $_SESSION['db_config'];
$error = '';
$success = '';

// پردازش فرم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    
    // اعتبارسنجی
    if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $error = 'لطفاً تمام فیلدها را پر کنید.';
    } elseif ($password !== $confirm_password) {
        $error = 'رمز عبور و تکرار آن یکسان نیستند.';
    } elseif (strlen($password) < 6) {
        $error = 'رمز عبور باید حداقل 6 کاراکتر باشد.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'فرمت ایمیل نامعتبر است.';
    } else {
        try {
            $pdo = new PDO(
                "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8mb4",
                $db_config['user'],
                $db_config['pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            // بررسی تکراری بودن
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetch()) {
                $error = 'نام کاربری یا ایمیل قبلاً استفاده شده است.';
            } else {
                // ایجاد کاربر مدیر
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                
                $stmt = $pdo->prepare("
                    INSERT INTO users 
                    (username, email, password, first_name, last_name, role, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, 'admin', 'active', NOW())
                ");
                
                $stmt->execute([$username, $email, $hashed_password, $first_name, $last_name]);
                
                $_SESSION['admin_created'] = true;
                header('Location: ?step=5');
                exit;
            }
            
        } catch (PDOException $e) {
            $error = 'خطا در ایجاد کاربر: ' . $e->getMessage();
        }
    }
}
?>

<h2 class="section-title">👤 ایجاد کاربر مدیر</h2>

<p class="section-description">
    لطفاً اطلاعات کاربر مدیر اولیه سیستم را وارد کنید. این کاربر دسترسی کامل به تمام بخش‌های CRM خواهد داشت.
</p>

<?php if ($error): ?>
<div class="alert alert-error">
    <strong>❌ خطا!</strong><br>
    <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<div class="alert alert-warning">
    <strong>🔐 امنیت:</strong><br>
    حتماً یک رمز عبور قوی انتخاب کنید و آن را در جای امنی ذخیره کنید.
</div>

<form method="POST" action="">
    <div class="form-group">
        <label for="first_name">نام *</label>
        <input type="text" 
               id="first_name" 
               name="first_name" 
               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" 
               required 
               placeholder="نام مدیر">
    </div>

    <div class="form-group">
        <label for="last_name">نام خانوادگی *</label>
        <input type="text" 
               id="last_name" 
               name="last_name" 
               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" 
               required 
               placeholder="نام خانوادگی مدیر">
    </div>

    <div class="form-group">
        <label for="username">نام کاربری *</label>
        <input type="text" 
               id="username" 
               name="username" 
               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
               required 
               placeholder="admin">
        <div class="form-hint">فقط از حروف انگلیسی، اعداد و _ استفاده کنید</div>
    </div>

    <div class="form-group">
        <label for="email">ایمیل *</label>
        <input type="email" 
               id="email" 
               name="email" 
               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
               required 
               placeholder="admin@yourcompany.com">
    </div>

    <div class="form-group">
        <label for="password">رمز عبور *</label>
        <input type="password" 
               id="password" 
               name="password" 
               required 
               placeholder="حداقل 6 کاراکتر">
    </div>

    <div class="form-group">
        <label for="confirm_password">تکرار رمز عبور *</label>
        <input type="password" 
               id="confirm_password" 
               name="confirm_password" 
               required 
               placeholder="تکرار رمز عبور">
    </div>

    <div class="btn-group">
        <a href="?step=3" class="btn btn-secondary">
            ➡️ قبلی
        </a>
        
        <button type="submit" class="btn btn-primary">
            ایجاد کاربر مدیر
            ⬅️
        </button>
    </div>
</form>
