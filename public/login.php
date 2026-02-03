<?php
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($csrf_token)) {
        $error = 'درخواست نامعتبر. لطفاً مجدداً تلاش کنید.';
    } elseif (empty($username) || empty($password)) {
        $error = 'لطفاً تمام فیلدها را پر کنید';
    } else {
        $result = loginUser($username, $password);
        if ($result['success']) {
            header('Location: dashboard.php');
            exit();
        } else {
            $error = $result['message'];
        }
    }
}

if (isset($_GET['expired'])) {
    $error = 'جلسه شما منقضی شده است. لطفاً مجدداً وارد شوید.';
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به سیستم - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #1dd3b0;
            --primary-dark: #17b89a;
            --primary-light: #3dffc8;
            --bg-dark: #1a1d2e;
            --bg-darker: #13151f;
            --bg-card: #222638;
            --text-white: #ffffff;
            --text-gray: #9ca3af;
            --text-dark: #1f2937;
            --border-color: #2d3248;
            --shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            --shadow-lg: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        body {
            font-family: 'Vazirmatn', sans-serif;
            background: linear-gradient(135deg, #0f1114 0%, #1a1d2e 50%, #13151f 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* پس‌زمینه متحرک */
        body::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(29, 211, 176, 0.1) 0%, transparent 70%);
            top: -300px;
            right: -300px;
            border-radius: 50%;
            animation: float 10s ease-in-out infinite;
        }

        body::after {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(29, 211, 176, 0.08) 0%, transparent 70%);
            bottom: -250px;
            left: -250px;
            border-radius: 50%;
            animation: float 12s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(30px, -30px) rotate(5deg); }
            66% { transform: translate(-30px, 30px) rotate(-5deg); }
        }

        .login-container {
            background: var(--bg-card);
            border-radius: 24px;
            overflow: hidden;
            max-width: 1100px;
            width: 95%;
            margin: 20px;
            box-shadow: var(--shadow-lg);
            position: relative;
            z-index: 1;
            border: 1px solid var(--border-color);
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0;
        }

        .col-left,
        .col-right {
            flex: 0 0 50%;
            max-width: 50%;
        }

        /* بخش راست - فرم */
        .login-form-side {
            background: var(--bg-darker);
            padding: 70px 60px;
            min-height: 700px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .welcome-header {
            margin-bottom: 50px;
            text-align: right;
        }

        .welcome-header h1 {
            color: var(--text-white);
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }

        .welcome-header p {
            color: var(--text-gray);
            font-size: 1rem;
            font-weight: 400;
        }

        .form-group {
            margin-bottom: 28px;
        }

        .form-label {
            color: var(--text-white);
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 12px;
            display: block;
        }

        .input-wrapper {
            position: relative;
        }

        .form-control {
            width: 100%;
            background: var(--bg-card);
            border: 2px solid var(--border-color);
            border-radius: 14px;
            padding: 16px 20px;
            color: var(--text-white);
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            outline: none;
        }

        .form-control::placeholder {
            color: var(--text-gray);
            font-weight: 400;
        }

        .form-control:focus {
            border-color: var(--primary);
            background: rgba(29, 211, 176, 0.05);
            box-shadow: 0 0 0 4px rgba(29, 211, 176, 0.1);
        }

        .password-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
            opacity: 0.5;
            transition: opacity 0.3s;
            z-index: 2;
        }

        .password-toggle:hover {
            opacity: 1;
        }

        .password-toggle svg {
            width: 22px;
            height: 22px;
            stroke: var(--text-gray);
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-wrapper input[type="checkbox"] {
            width: 20px;
            height: 20px;
            border: 2px solid var(--border-color);
            background: var(--bg-card);
            border-radius: 6px;
            cursor: pointer;
            accent-color: var(--primary);
        }

        .checkbox-wrapper label {
            color: var(--text-gray);
            font-size: 0.9rem;
            cursor: pointer;
            user-select: none;
        }

        .forgot-link {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.3s;
        }

        .forgot-link:hover {
            color: var(--primary-light);
        }

        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            border-radius: 14px;
            padding: 18px;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(29, 211, 176, 0.3);
            letter-spacing: 0.3px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(29, 211, 176, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .version-info {
            text-align: center;
            margin-top: 30px;
            color: var(--text-gray);
            font-size: 0.85rem;
        }

        /* بخش چپ - اطلاعات */
        .login-info-side {
            background: linear-gradient(135deg, #1dd3b0 0%, #17b89a 100%);
            padding: 70px 60px;
            min-height: 700px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-info-side::before {
            content: '';
            position: absolute;
            width: 350px;
            height: 350px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            top: -120px;
            right: -120px;
        }

        .login-info-side::after {
            content: '';
            position: absolute;
            width: 250px;
            height: 250px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 50%;
            bottom: -80px;
            left: -80px;
        }

        .logo-icon {
            width: 110px;
            height: 110px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 35px;
            position: relative;
            z-index: 1;
        }

        .logo-icon svg {
            width: 55px;
            height: 55px;
            stroke: white;
            fill: none;
        }

        .info-content {
            position: relative;
            z-index: 1;
        }

        .info-title {
            color: white;
            font-size: 2.8rem;
            font-weight: 900;
            margin-bottom: 18px;
            letter-spacing: -0.5px;
        }

        .info-subtitle {
            color: rgba(255, 255, 255, 0.95);
            font-size: 1.15rem;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .info-description {
            color: rgba(255, 255, 255, 0.85);
            font-size: 1rem;
            margin-bottom: 50px;
            line-height: 1.7;
        }

        .features-list {
            list-style: none;
            padding: 0;
            margin: 0;
            width: 100%;
            max-width: 400px;
        }

        .feature-item {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 14px;
            padding: 18px 24px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 14px;
            color: white;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .feature-item:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(-5px);
        }

        .feature-item svg {
            width: 24px;
            height: 24px;
            stroke: white;
            flex-shrink: 0;
        }

        .footer-text {
            margin-top: 50px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.85rem;
            position: relative;
            z-index: 1;
        }

        /* Alert Styles */
        .alert {
            border-radius: 14px;
            border: none;
            padding: 16px 20px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.95rem;
        }

        .alert svg {
            width: 22px;
            height: 22px;
            flex-shrink: 0;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.15);
            border-right: 4px solid #ef4444;
            color: #fca5a5;
        }

        .alert-danger svg {
            stroke: #ef4444;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border-right: 4px solid #10b981;
            color: #6ee7b7;
        }

        .alert-success svg {
            stroke: #10b981;
        }

        /* Responsive */
        @media (max-width: 991px) {
            .col-left,
            .col-right {
                flex: 0 0 100%;
                max-width: 100%;
            }

            .login-info-side {
                display: none;
            }

            .login-form-side {
                padding: 50px 35px;
                min-height: auto;
            }
        }

        @media (max-width: 576px) {
            .login-container {
                margin: 10px;
                border-radius: 18px;
            }

            .login-form-side {
                padding: 40px 25px;
            }

            .welcome-header h1 {
                font-size: 1.8rem;
            }

            .form-control {
                padding: 14px 18px;
                font-size: 0.95rem;
            }

            .btn-login {
                padding: 16px;
                font-size: 1rem;
            }

            .form-options {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="row">
            <!-- فرم ورود - سمت راست -->
            <div class="col-right">
                <div class="login-form-side">
                    <div class="welcome-header">
                        <h1>خوش آمدید</h1>
                        <p>جهت ورود، اطلاعات خود را وارد کنید</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <div class="form-group">
                            <label for="username" class="form-label">نام کاربری یا ایمیل</label>
                            <div class="input-wrapper">
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="username" 
                                    name="username"
                                    placeholder="admin"
                                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                    required 
                                    autocomplete="username"
                                >
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">رمز عبور</label>
                            <div class="password-wrapper">
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="password" 
                                    name="password"
                                    placeholder="••••••••"
                                    required 
                                    autocomplete="current-password"
                                >
                                <button type="button" class="password-toggle" onclick="togglePassword()">
                                    <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="form-options">
                            <div class="checkbox-wrapper">
                                <input type="checkbox" id="remember_me" name="remember_me">
                                <label for="remember_me">مرا به خاطر بسپار</label>
                            </div>
                            <a href="#" class="forgot-link">رمز عبور فراموشی شده؟</a>
                        </div>

                        <button type="submit" name="login" class="btn-login">
                            ورود
                        </button>
                    </form>

                    <div class="version-info">
                        نسخه <?php echo APP_VERSION ?? '1.0.0'; ?>
                    </div>
                </div>
            </div>

            <!-- اطلاعات سیستم - سمت چپ -->
            <div class="col-left">
                <div class="login-info-side">
                    <div class="info-content">
                        <div class="logo-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                <path d="M9 12l2 2 4-4"></path>
                            </svg>
                        </div>
                        
                        <h1 class="info-title">سیستم CRM</h1>
                        <p class="info-subtitle">مدیریت حرفه‌ای ارتباط با مشتریان</p>
                        <p class="info-description">افزایش فروش و بهبود خدمات</p>

                        <ul class="features-list">
                            <li class="feature-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                </svg>
                                <span>امنیت سطح بالا</span>
                            </li>
                            <li class="feature-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                                </svg>
                                <span>تحلیل فروش هوشمند</span>
                            </li>
                            <li class="feature-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                                <span>مدیریت مشتریان یکجا</span>
                            </li>
                            <li class="feature-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                                </svg>
                                <span>پیگیری وظایف خودکار</span>
                            </li>
                        </ul>

                        <div class="footer-text">
                            توسعه یافته توسط تیم ردی استودیو| نسخه <?php echo APP_VERSION ?? '1.0.0'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = `
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                    <line x1="1" y1="1" x2="23" y2="23"></line>
                `;
                eyeIcon.parentElement.style.opacity = '1';
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = `
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                `;
                eyeIcon.parentElement.style.opacity = '0.5';
            }
        }

        // Auto-focus on username field
        document.addEventListener('DOMContentLoaded', function() {
            const usernameField = document.getElementById('username');
            if (usernameField && !usernameField.value) {
                usernameField.focus();
            }
        });
    </script>
</body>
</html>
