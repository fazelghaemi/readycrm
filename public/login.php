<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════════
 * ReadyCRM V3.0 - صفحه ورود به سیستم
 * ═══════════════════════════════════════════════════════════════════════════════
 * بازطراحی شده طبق دیزاین جدید
 * @version 3.2.0
 * @author Ready Studio
 * ═══════════════════════════════════════════════════════════════════════════════
 */

session_start();
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/database.php';
require_once __DIR__ . '/../private/auth.php';
require_once __DIR__ . '/../private/functions.php';

// تعریف مسیر پایه استت‌ها در صورت عدم وجود در کانفیگ
if (!defined('ASSETS_URL')) {
    define('ASSETS_URL', '/assets'); // مسیر پیش‌فرض
}

// ═══════════════════════════════════════════════════════════════════════════════
// REDIRECT IF ALREADY LOGGED IN
// ═══════════════════════════════════════════════════════════════════════════════
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// ═══════════════════════════════════════════════════════════════════════════════
// VARIABLES
// ═══════════════════════════════════════════════════════════════════════════════
$error = '';
$success = '';
$identifier_value = ''; // تغییر نام متغیر از email به identifier

// ═══════════════════════════════════════════════════════════════════════════════
// FORM PROCESSING
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // دریافت ورودی به عنوان شناسه (ایمیل یا نام کاربری)
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    $identifier_value = htmlspecialchars($identifier);

    // ─── Validation ─────────────────────────────────────────────────────
    if (!verifyCSRFToken($csrf_token)) {
        $error = 'درخواست نامعتبر است. لطفاً صفحه را تازه‌سازی کنید.';
    } elseif (empty($identifier) || empty($password)) {
        $error = 'ایمیل/نام کاربری و رمز عبور الزامی است.';
    } else {
        // ─── Login Attempt ──────────────────────────────────────────────
        try {
            // جستجو بر اساس ایمیل یا نام کاربری
            // فرض بر این است که ستونی به نام username در جدول users وجود دارد
            // اگر ستون نام کاربری ندارید، این کوئری را به حالت قبل برگردانید
            $stmt = $pdo->prepare("SELECT * FROM users WHERE (email = ? OR username = ?) AND status = 'active'");
            $stmt->execute([$identifier, $identifier]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // ✅ Successful login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();

                // Update last login
                $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);

                // Log activity
                logActivity($user['id'], 'login', 'users', $user['id'], 'ورود موفقیت‌آمیز به سیستم');

                // Redirect
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'نام کاربری یا رمز عبور اشتباه است.';
                
                if ($user) {
                    logActivity($user['id'], 'login_failed', 'users', $user['id'], 'تلاش ناموفق برای ورود');
                }
            }
        } catch (PDOException $e) {
            error_log("Login Error: " . $e->getMessage());
            // جهت امنیت بیشتر، جزئیات خطا را به کاربر نشان نمی‌دهیم اما لاگ می‌کنیم
            // اگر خطای "Column not found: username" دریافت کردید، یعنی دیتابیس ستون username ندارد
            $error = 'خطای سیستمی. لطفاً دوباره تلاش کنید.';
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// SYSTEM MESSAGES
// ═══════════════════════════════════════════════════════════════════════════════
if (isset($_GET['expired'])) {
    $error = 'جلسه شما منقضی شده است. لطفاً مجدداً وارد شوید.';
}
if (isset($_GET['logout'])) {
    $success = 'شما با موفقیت از سیستم خارج شدید.';
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#00bfa5">
    <meta name="description" content="سیستم مدیریت کسب‌وکار ReadyCRM">
    <title>ورود به سیستم | <?php echo APP_NAME ?? 'ReadyCRM'; ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo ASSETS_URL; ?>/img/favicon.png">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        /* ─── FONTS ───────────────────────────────────────────────────────── */
        @font-face {
            font-family: 'YekanBakh';
            src: url('<?php echo ASSETS_URL; ?>/YekanBakhFaNum-VF.ttf') format('truetype-variations');
            font-weight: 100 900;
            font-display: swap;
        }

        /* ─── VARIABLES ───────────────────────────────────────────────────── */
        :root {
            --primary: #00bfa5;
            --primary-hover: #009688;
            --text-dark: #1f2937;
            --text-gray: #6b7280;
            --text-light: #9ca3af;
            --bg-light: #ffffff;
            --border-color: #e5e7eb;
            --radius: 12px;
            --font-main: 'YekanBakh', sans-serif;
        }

        /* ─── RESET ───────────────────────────────────────────────────────── */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: var(--font-main);
            background: #fff;
            color: var(--text-dark);
            height: 100vh;
            overflow: hidden;
        }

        a { text-decoration: none; color: inherit; transition: 0.3s; }
        
        /* ─── LAYOUT ──────────────────────────────────────────────────────── */
        .wrapper {
            display: flex;
            height: 100vh;
            width: 100%;
        }

        /* ─── LEFT SIDE (IMAGE) ───────────────────────────────────────────── */
        .side-image {
            flex: 1;
            background-image: url('/assets/img/login-photo.jpg');
            background-size: cover;
            background-position: center;
            position: relative;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            color: white;
            border-radius: 0;
            /* حذف اورلی تیره برای نمایش پررنگ تصویر */
        }

        /* اورلی قبلی حذف شد تا تصویر 100% پررنگ باشد */
        /* .side-image::before { ... } */

        .side-content {
            position: relative;
            z-index: 2;
            max-width: 600px;
            margin-top: 60px;
            /* تغییر جهت متن به چپ */
            text-align: left;
            direction: ltr;
        }

        .side-content h1 {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 20px;
        }

        .side-content p {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 300;
            letter-spacing: 0.5px;
        }

        /* ─── RIGHT SIDE (FORM) ───────────────────────────────────────────── */
        .side-form {
            flex: 1;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 30px 50px;
            overflow-y: auto;
        }

        /* هدر پنل راست */
        .form-header-nav {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .website-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .website-btn .pill {
            padding: 6px 16px;
            border: 1px solid var(--border-color);
            border-radius: 20px;
            font-size: 0.8rem;
            color: var(--text-gray);
        }

        .website-btn:hover .pill {
            border-color: var(--primary);
            color: var(--primary);
        }

        /* محتوای وسط (فرم) */
        .form-container {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
            text-align: center;
        }

        .brand-logo {
            width: 60px;
            height: 60px;
            margin-bottom: 20px;
            border-radius: 12px;
            object-fit: contain;
        }

        .welcome-text h2 {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 8px;
            color: var(--text-dark);
        }

        .welcome-text p {
            color: var(--text-gray);
            font-size: 1rem;
            margin-bottom: 40px;
        }

        /* دکمه گوگل */
        .btn-google {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 14px;
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            font-family: inherit;
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--text-dark);
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-google:hover {
            background: #f9fafb;
            border-color: #d1d5db;
        }

        .btn-google img {
            width: 20px;
            height: 20px;
        }

        /* جداکننده "یا" */
        .divider {
            position: relative;
            text-align: center;
            margin: 25px 0;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--border-color);
            z-index: 1;
        }

        .divider span {
            background: #fff;
            padding: 0 15px;
            color: var(--text-light);
            position: relative;
            z-index: 2;
            font-size: 0.9rem;
        }

        /* فرم */
        .input-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 16px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            font-family: inherit;
            font-size: 0.95rem;
            background: #fafafa;
            color: var(--text-dark);
            transition: 0.2s;
            text-align: right;
        }

        .form-control:focus {
            outline: none;
            background: #fff;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 191, 165, 0.1);
        }

        .form-control::placeholder {
            color: var(--text-light);
        }

        .password-wrapper {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            cursor: pointer;
            border: none;
            background: none;
            padding: 5px;
        }

        .btn-submit {
            width: 100%;
            padding: 16px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-family: inherit;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 10px;
            transition: 0.2s;
        }

        .btn-submit:hover {
            background: var(--primary-hover);
        }

        .forgot-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
            color: var(--text-gray);
            font-weight: 500;
        }

        .forgot-link:hover {
            color: var(--primary);
        }

        /* فوتر پنل راست */
        .form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: var(--text-light);
            border-top: 1px solid transparent; /* فضا نگهدار */
            padding-top: 20px;
        }

        .form-footer a {
            color: var(--text-gray);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* پیام‌های خطا */
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert-success { background: #d1fae5; color: #047857; border: 1px solid #a7f3d0; }

        /* ─── RESPONSIVE ──────────────────────────────────────────────────── */
        @media (max-width: 900px) {
            .side-image { display: none; }
            .wrapper { justify-content: center; }
            .side-form { flex: auto; max-width: 100%; padding: 20px; }
            .form-container { max-width: 100%; }
        }

        @media (min-width: 901px) and (max-width: 1200px) {
            .side-content h1 { font-size: 2.5rem; }
        }
    </style>
</head>
<body>

    <div class="wrapper">
        
        <!-- سمت راست: فرم -->
        <div class="side-form">
            
            <!-- هدر بالا -->
            <div class="form-header-nav">
                <a href="https://readycrm.ir" target="_blank" class="website-btn">
                    <span class="pill">readycrm.ir <i class="fas fa-external-link-alt" style="font-size:10px; margin-right:4px;"></i></span>
                    <div style="text-align: left; margin-left: 10px;">
                        <span style="display:block; font-weight:700; color:#000;">Website</span>
                        <span style="display:block; font-size:0.75rem; color:#999;">Visit Our Website</span>
                    </div>
                </a>
            </div>

            <!-- محتوای وسط -->
            <div class="form-container">
                
                <!-- لوگو -->
                <img src="<?php echo ASSETS_URL; ?>/img/favicon.png" alt="ReadyCRM" class="brand-logo">

                <!-- عناوین -->
                <div class="welcome-text">
                    <h2>خوش آمدید</h2>
                    <p>سیستم مدیریت کسب‌وکار</p>
                </div>

                <!-- پیام‌های سیستم -->
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo $success; ?></span>
                    </div>
                <?php endif; ?>

                <!-- دکمه گوگل -->
                <button type="button" class="btn-google">
                    <img src="/assets/img/google-icon.png" alt="Google">
                    <span>ورود با گوگل</span>
                </button>

                <!-- جداکننده -->
                <div class="divider">
                    <span>یا</span>
                </div>

                <!-- فرم ورود -->
                <form method="POST" action="" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="input-group">
                        <!-- تغییر به identifier و نوع text برای پشتیبانی از ایمیل و یوزرنیم -->
                        <input type="text" name="identifier" class="form-control" placeholder="ایمیل یا نام کاربری" value="<?php echo $identifier_value; ?>" required>
                    </div>

                    <div class="input-group password-wrapper">
                        <input type="password" name="password" id="password" class="form-control" placeholder="رمز عبور" required>
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="far fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>

                    <button type="submit" name="login" class="btn-submit" id="loginBtn">
                        ورود به سامانه
                    </button>

                    <a href="forgot_password.php" class="forgot-link">رمز عبور خود را فراموش کردید؟</a>
                </form>

            </div>

            <!-- فوتر پایین -->
            <div class="form-footer">
                <span>©ReadyStudio</span>
                <a href="mailto:hi@readycrm.ir">
                    <i class="far fa-envelope"></i> hi@readycrm.ir
                </a>
            </div>
        </div>

        <!-- سمت چپ: تصویر -->
        <div class="side-image">
            <div class="side-content">
                <h1>Anything You Can<br>Imagine In Business<br>Manage</h1>
                <p>Generate your ideas in to reality fast & quick !</p>
            </div>
        </div>

    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>