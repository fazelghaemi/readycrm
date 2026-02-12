<?php
/**
 * CRM V2 - Login Page
 * صفحه ورود به سیستم با طراحی مدرن و امنیت بالا
 * 
 * @version 2.0.0
 * @author Ready Studio
 */

session_start();
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/database.php';
require_once __DIR__ . '/../private/auth.php';
require_once __DIR__ . '/../private/functions.php';

// اگر کاربر لاگین کرده است، به داشبورد هدایت شود
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

// پردازش فرم لاگین
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']);
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($csrf_token)) {
        $error = 'درخواست نامعتبر است. لطفاً دوباره تلاش کنید.';
    } elseif (empty($username) || empty($password)) {
        $error = 'نام کاربری و رمز عبور الزامی است.';
    } else {
        $result = loginUser($username, $password, $remember_me);
        if ($result['success']) {
            // ثبت log موفقیت‌آمیز
            logActivity($result['user_id'], 'auth', 'login', 'ورود موفقیت‌آمیز به سیستم');
            
            header('Location: dashboard.php');
            exit();
        } else {
            $error = $result['message'];
        }
    }
}

// پیام‌های سیستمی
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
    <meta name="theme-color" content="#00b0a4">
    <title>ورود به سیستم - <?php echo APP_NAME; ?></title>
    
<!-- Favicon -->
<link rel="icon" type="image/png" href="<?php echo ASSETS_URL; ?>/favicon.png">
<link rel="apple-touch-icon" href="<?php echo ASSETS_URL; ?>/favicon.png">


    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        /* ==================== FONT FACE ==================== */
        @font-face {
    font-family: 'YekanBakh';
    src: url('<?php echo ASSETS_URL; ?>/YekanBakhFaNum-VF.ttf') format('truetype-variations');
    font-weight: 100 900;
    font-display: swap;
}

        /* ==================== CSS VARIABLES ==================== */
        :root {
            /* Brand Colors - طبق استاندارد CRM V2 */
            --brand-primary: #00b0a4;
            --brand-primary-dark: #008c82;
            --brand-primary-light: #00d4c5;
            --brand-black: #000000;

            /* Gradients */
            --gradient-primary: linear-gradient(135deg, #00b0a4 0%, #00d4c5 100%);
            --gradient-dark: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
            
            /* Neutral Colors */
            --gray-50: #fafafa;
            --gray-100: #f5f5f5;
            --gray-200: #eeeeee;
            --gray-300: #e0e0e0;
            --gray-500: #9e9e9e;
            --gray-700: #616161;
            --gray-900: #212121;

            /* Semantic Colors */
            --success: #00c853;
            --danger: #f44336;
            --warning: #ffc107;

            /* Shadows */
            --shadow-sm: 0 2px 4px 0 rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 8px 0 rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 8px 16px 0 rgba(0, 0, 0, 0.08);
            --shadow-xl: 0 12px 24px 0 rgba(0, 0, 0, 0.1);
            --shadow-brand: 0 8px 24px 0 rgba(0, 176, 164, 0.2);

            /* Border Radius */
            --radius-sm: 12px;
            --radius-md: 16px;
            --radius-lg: 20px;
            --radius-xl: 24px;

            /* Transitions */
            --transition-base: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* ==================== RESET & BASE ==================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'YekanBakh', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--gradient-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* ==================== ANIMATED BACKGROUND ==================== */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 50%, rgba(0, 212, 197, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(0, 176, 164, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(0, 140, 130, 0.1) 0%, transparent 50%);
            animation: pulse 15s ease-in-out infinite;
            z-index: 0;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        /* ==================== LOGIN CONTAINER ==================== */
        .login-container {
            position: relative;
            z-index: 1;
            background: white;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-brand);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 600px;
        }

        /* ==================== LEFT PANEL (INFO) ==================== */
        .login-info {
            background: var(--gradient-dark);
            padding: 60px 50px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .login-info::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: var(--gradient-primary);
            opacity: 0.1;
            transform: rotate(45deg);
        }

        .login-info-content {
            position: relative;
            z-index: 1;
        }

        .logo-section {
            margin-bottom: 40px;
        }

        .logo-icon {
            width: 64px;
            height: 64px;
            background: var(--gradient-primary);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: var(--shadow-lg);
        }

        .logo-icon i {
            font-size: 32px;
            color: white;
        }

        .logo-section h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo-section p {
            font-size: 1.1rem;
            color: var(--gray-300);
            line-height: 1.6;
        }

        .features-list {
            list-style: none;
            margin-top: 40px;
        }

        .features-list li {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            font-size: 1rem;
            color: var(--gray-200);
        }

        .features-list li i {
            width: 40px;
            height: 40px;
            background: rgba(0, 176, 164, 0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 15px;
            color: var(--brand-primary-light);
        }

        /* ==================== RIGHT PANEL (FORM) ==================== */
        .login-form-wrapper {
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header {
            margin-bottom: 40px;
        }

        .form-header h2 {
            font-size: 2rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 10px;
        }

        .form-header p {
            color: var(--gray-500);
            font-size: 1rem;
        }

        /* ==================== FORM ELEMENTS ==================== */
        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            color: var(--gray-700);
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-500);
            font-size: 18px;
            pointer-events: none;
            transition: var(--transition-base);
        }

        .form-control {
            width: 100%;
            padding: 14px 50px 14px 16px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-sm);
            font-size: 1rem;
            font-family: inherit;
            transition: var(--transition-base);
            background: var(--gray-50);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--brand-primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(0, 176, 164, 0.1);
        }

        .form-control:focus + .input-icon {
            color: var(--brand-primary);
        }

        /* ==================== CHECKBOX ==================== */
        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 24px;
        }

        .form-check-input {
            width: 20px;
            height: 20px;
            margin-left: 10px;
            border: 2px solid var(--gray-300);
            border-radius: 6px;
            cursor: pointer;
            transition: var(--transition-base);
        }

        .form-check-input:checked {
            background-color: var(--brand-primary);
            border-color: var(--brand-primary);
        }

        .form-check-label {
            color: var(--gray-700);
            font-size: 0.95rem;
            cursor: pointer;
            user-select: none;
        }

        /* ==================== BUTTON ==================== */
        .btn-login {
            width: 100%;
            padding: 16px;
            background: var(--gradient-primary);
            border: none;
            border-radius: var(--radius-sm);
            color: white;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition-base);
            box-shadow: var(--shadow-md);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login i {
            font-size: 18px;
        }

        /* ==================== ALERTS ==================== */
        .alert {
            padding: 16px 20px;
            border-radius: var(--radius-sm);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: none;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
        }

        .alert i {
            font-size: 20px;
        }

        /* ==================== FOOTER LINKS ==================== */
        .form-footer {
            margin-top: 24px;
            text-align: center;
        }

        .forgot-link {
            color: var(--brand-primary);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: var(--transition-base);
        }

        .forgot-link:hover {
            color: var(--brand-primary-dark);
        }

        .version-info {
            margin-top: 24px;
            color: var(--gray-500);
            font-size: 0.85rem;
        }

        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 768px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 450px;
            }

            .login-info {
                display: none;
            }

            .login-form-wrapper {
                padding: 40px 30px;
            }

            .form-header h2 {
                font-size: 1.75rem;
            }

            body {
                padding: 15px;
            }
        }

        @media (max-width: 480px) {
            .login-form-wrapper {
                padding: 30px 20px;
            }

            .form-header h2 {
                font-size: 1.5rem;
            }

            .logo-section h1 {
                font-size: 2rem;
            }
        }

        /* ==================== LOADING STATE ==================== */
        .btn-login.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .btn-login.loading::after {
            content: '';
            width: 20px;
            height: 20px;
            border: 3px solid white;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left Panel - Info -->
        <div class="login-info">
            <div class="login-info-content">
                <div class="logo-section">
                    <div class="logo-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h1><?php echo APP_NAME; ?></h1>
                    <p>سیستم مدیریت ارتباط با مشتریان</p>
                </div>

                <ul class="features-list">
                    <li>
                        <i class="fas fa-shield-alt"></i>
                        <span>امنیت بالا و رمزنگاری پیشرفته</span>
                    </li>
                    <li>
                        <i class="fas fa-chart-bar"></i>
                        <span>گزارش‌گیری هوشمند و تحلیل داده</span>
                    </li>
                    <li>
                        <i class="fas fa-users"></i>
                        <span>مدیریت کامل مشتریان و فروش</span>
                    </li>
                    <li>
                        <i class="fas fa-plug"></i>
                        <span>ادغام با WooCommerce و سیستم‌های دیگر</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Right Panel - Form -->
        <div class="login-form-wrapper">
            <div class="form-header">
                <h2>خوش آمدید</h2>
                <p>برای ادامه، وارد حساب کاربری خود شوید</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $success; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div class="form-group">
                    <label for="username" class="form-label">نام کاربری یا ایمیل</label>
                    <div class="input-wrapper">
                        <input 
                            type="text" 
                            class="form-control" 
                            id="username" 
                            name="username"
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                            required 
                            autocomplete="username"
                            placeholder="username@example.com"
                        >
                        <i class="fas fa-user input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">رمز عبور</label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            class="form-control" 
                            id="password" 
                            name="password"
                            required 
                            autocomplete="current-password"
                            placeholder="••••••••"
                        >
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                </div>

                <div class="form-check">
                    <input 
                        class="form-check-input" 
                        type="checkbox" 
                        id="remember_me" 
                        name="remember_me"
                    >
                    <label class="form-check-label" for="remember_me">
                        مرا به خاطر بسپار
                    </label>
                </div>

                <button type="submit" name="login" class="btn-login" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>ورود به سیستم</span>
                </button>
            </form>

            <div class="form-footer">
                <a href="#" class="forgot-link">رمز عبور خود را فراموش کرده‌اید؟</a>
                <div class="version-info">
                    نسخه <?php echo APP_VERSION; ?> | طراحی و توسعه: <strong>Ready Studio</strong>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Loading state on submit
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            btn.querySelector('span').textContent = 'در حال ورود...';
        });

        // Auto-hide alerts
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(function() {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });

        // Enter key submit
        document.querySelectorAll('.form-control').forEach(function(input) {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('loginForm').dispatchEvent(new Event('submit'));
                }
            });
        });
    </script>
</body>
</html>
