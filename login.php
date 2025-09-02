<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();

//
// NOTE: Make sure these file paths are correct for your project structure.
//
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// If the user is already logged in, redirect them to the dashboard.
if (function_exists('isLoggedIn') && isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

// Handle the standard login form submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username   = function_exists('sanitizeInput') ? sanitizeInput($_POST['username'] ?? '') : htmlspecialchars($_POST['username'] ?? '');
    $password   = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!function_exists('verifyCSRFToken') || !verifyCSRFToken($csrf_token)) {
        $error = 'Invalid request. Please refresh the page and try again.';
    } elseif (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // The loginUser function is expected to be in your 'auth.php'
        $result = loginUser($username, $password);
        if ($result['status'] === true) {
            $_SESSION['user_id'] = $result['user']['id'];
            $_SESSION['username'] = $result['user']['username'];
            header('Location: dashboard.php');
            exit();
        } else {
            $error = $result['message'] ?? 'Incorrect username or password.';
        }
    }
}

// Display a message if the session has expired.
if (isset($_GET['expired'])) {
    $error = 'Your session has expired. Please log in again.';
}
if (isset($_GET['reset'])) {
    $success = 'Your password has been reset successfully. You can now log in.';
}


// Generate a CSRF token for form security.
$csrf_token = function_exists('generateCSRFToken') ? generateCSRFToken() : 'csrf_placeholder';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود | ReadyCRM</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --brand-primary: #00b0a4; --brand-success: #10b981; --brand-danger: #ef4444; --brand-info: #3b82f6;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Vazirmatn', sans-serif; }
        html, body { height: 100%; overflow: hidden; }
        body { background: #0a0a0a; display: flex; align-items: center; justify-content: center; position: relative; }
        .bg-circle { position: absolute; border-radius: 50%; filter: blur(100px); opacity: 0.5; z-index: 1; }
        .bg-circle-1 { width: 450px; height: 450px; background: linear-gradient(45deg, #00d4ff, #0099cc); top: -120px; left: -150px; animation: float 8s ease-in-out infinite; }
        .bg-circle-2 { width: 400px; height: 400px; background: linear-gradient(45deg, #ff6b35, #ff8c42); bottom: -100px; right: -120px; animation: float 10s ease-in-out infinite reverse; }
        @keyframes float { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-30px); } }
        .login-container { background: rgba(30, 30, 30, 0.35); backdrop-filter: blur(25px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 25px; padding: 45px 40px; width: 100%; max-width: 420px; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4); position: relative; z-index: 10; animation: fadeInUp 0.8s ease-out; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .login-title { background: linear-gradient(135deg, #fff, #e0e0e0); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-size: 32px; font-weight: 700; text-align: center; margin-bottom: 35px; }
        .alerts { margin-bottom: 18px; font-size: 14px; padding: 12px 15px; border-radius: 12px; text-align: center; color: #fff; border: 1px solid; }
        .alert-danger { background: rgba(220, 38, 38, 0.3); border-color: rgba(220, 38, 38, 0.5); }
        .alert-success { background: rgba(5, 150, 105, 0.3); border-color: rgba(5, 150, 105, 0.5); }
        .form-group { margin-bottom: 22px; position: relative; }
        .form-label { display: block; color: rgba(255, 255, 255, 0.9); font-size: 15px; margin-bottom: 8px; font-weight: 500; }
        .form-input { width: 100%; padding: 16px 20px; background: rgba(20, 20, 20, 0.7); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 15px; color: white; font-size: 16px; transition: all 0.3s ease; }
        .form-input::placeholder { color: rgba(255, 255, 255, 0.4); }
        .form-input:focus { outline: none; border-color: rgba(0, 212, 255, 0.6); box-shadow: 0 0 20px rgba(0, 212, 255, 0.25); background: rgba(25, 25, 25, 0.9); }
        .login-btn { width: 100%; padding: 16px; background: #fff; border: none; border-radius: 15px; color: #0a0a0a; font-size: 18px; font-weight: 700; cursor: pointer; transition: all 0.2s ease; margin-top: 10px; }
        .login-btn:disabled { opacity: .7; cursor: not-allowed; }
        .forgot-password { text-align: center; margin-top: 20px; }
        .forgot-password-link { color: rgba(255, 255, 255, 0.7); font-size: 14px; text-decoration: none; cursor: pointer; transition: color 0.2s; }
        .forgot-password-link:hover { color: #fff; }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(10px); display: flex; align-items: center; justify-content: center; z-index: 1000; opacity: 0; visibility: hidden; transition: opacity 0.3s ease, visibility 0.3s; }
        .modal-overlay.visible { opacity: 1; visibility: visible; }
        #otp-modal { animation: fadeInUp 0.5s ease-out; }
        .modal-subtitle { text-align:center; color: rgba(255,255,255,0.7); font-size:14px; margin-top:-20px; margin-bottom:20px; }
        .modal-msg { font-size: 14px; min-height: 20px; margin-top: 15px; text-align: center; }
        .modal-msg.success { color: var(--brand-success); } .modal-msg.error { color: var(--brand-danger); } .modal-msg.info { color: var(--brand-info); }
        .modal-actions { display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap; }
        .btn { flex-grow: 1; height: 44px; padding: 0 16px; border-radius: 12px; border: 1px solid transparent; cursor: pointer; font-weight: 700; font-family: 'Vazirmatn', sans-serif; font-size: 15px; transition: transform 0.1s ease; }
        .btn:active { transform: scale(0.97); }
        .btn-primary { background: var(--brand-primary); color: #fff; } .btn-success { background: var(--brand-success); color: #fff; } .btn-outline { background: transparent; border-color: rgba(255,255,255,0.2); color: #fff; }
        .btn:disabled { opacity: .6; cursor: not-allowed; transform: scale(1); }
        #btn-close-modal { position: absolute; top: 15px; left: 20px; background: none; border: none; color: #fff; font-size: 24px; cursor: pointer; padding: 5px; line-height: 1; }
    </style>
</head>
<body>
    <div class="bg-circle bg-circle-1"></div>
    <div class="bg-circle bg-circle-2"></div>

    <div class="login-container" id="login-panel">
        <h1 class="login-title">ورود به سیستم</h1>
        
        <?php if(!empty($error)): ?>
            <div class="alerts alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if(!empty($success)): ?>
            <div class="alerts alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="post" action="login.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-group">
                <label for="username" class="form-label">نام کاربری</label>
                <input type="text" id="username" name="username" class="form-input" placeholder="ایمیل یا شماره تلفن" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password" class="form-label">رمز عبور</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="••••••••" required autocomplete="current-password">
            </div>
            <button type="submit" name="login" value="1" class="login-btn" id="submitBtn">ورود</button>
        </form>
        
        <div class="forgot-password">
            <a id="forgot-pass-link" class="forgot-password-link">رمز عبور خود را فراموش کرده‌اید؟</a>
        </div>
    </div>

    <div class="modal-overlay" id="otp-modal-overlay">
        <div class="login-container" id="otp-modal">
            <button id="btn-close-modal" aria-label="بستن">&times;</button>
            <h1 class="login-title">بازیابی رمز عبور</h1>
            <p class="modal-subtitle">شماره موبایل خود را برای دریافت کد وارد کنید.</p>
            <form id="otp-form" onsubmit="return false;" novalidate>
                <input type="hidden" id="otp_csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-group">
                    <label for="mobile" class="form-label">موبایل</label>
                    <input id="mobile" name="mobile" type="tel" class="form-input" placeholder="مثال: 0912xxxxxxx" inputmode="numeric" autocomplete="tel" required>
                </div>
                <div id="code-row" class="form-group" style="display:none">
                    <label for="code" class="form-label">کد تایید</label>
                    <input id="code" name="code" type="text" class="form-input" inputmode="numeric" pattern="[0-9]{4,8}" maxlength="8" placeholder="کد دریافتی را وارد کنید" autocomplete="one-time-code">
                </div>
                <div class="modal-actions">
                    <button id="btn-send"   class="btn btn-primary" type="button">ارسال کد</button>
                    <button id="btn-verify" class="btn btn-success" type="button" style="display:none">تایید کد</button>
                    <button id="btn-resend" class="btn btn-outline" type="button" style="display:none" disabled>ارسال مجدد</button>
                </div>
                <div id="modal-msg" class="modal-msg" role="alert"></div>
            </form>
        </div>
    </div>

    <script>
    (function() {
        'use strict';
        const $ = (sel) => document.querySelector(sel);
        
        // --- UI Animations ---
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() { this.style.transform = 'scale(1.03)'; });
            input.addEventListener('blur', function() { this.style.transform = 'scale(1)'; });
        });

        $('form[method="post"]').addEventListener('submit', function() {
            $('#submitBtn')?.setAttribute('disabled', 'disabled');
            if($('#submitBtn')) $('#submitBtn').textContent = 'در حال ورود…';
        });
        
        // --- OTP Modal Logic ---
        const forgotPassLink = $('#forgot-pass-link');
        const modalOverlay = $('#otp-modal-overlay');
        const closeModalBtn = $('#btn-close-modal');
        const mobileEl = $('#mobile');
        const codeEl   = $('#code');
        const csrfEl   = $('#otp_csrf_token');
        const sendBtn  = $('#btn-send');
        const verifyBtn= $('#btn-verify');
        const resendBtn= $('#btn-resend');
        const codeRow  = $('#code-row');
        const msg      = $('#modal-msg');

        const CFG = { ajaxUrl: 'ajax_handler.php', resendSeconds: 90 };
        let timer = null;

        // --- Modal Visibility Controls ---
        forgotPassLink?.addEventListener('click', () => modalOverlay.classList.add('visible'));
        closeModalBtn?.addEventListener('click', () => modalOverlay.classList.remove('visible'));
        modalOverlay?.addEventListener('click', (e) => {
            if (e.target === modalOverlay) modalOverlay.classList.remove('visible');
        });

        // --- Core Functions ---
        const setMsg = (text, type = '') => {
            if(!msg) return;
            msg.textContent = text || '';
            msg.className = 'modal-msg ' + type;
        };

        const post = async (action, data) => {
            try {
                const body = new URLSearchParams(data);
                body.append('action', action);
                const res = await fetch(CFG.ajaxUrl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                    body: body.toString()
                });
                if (!res.ok) return { success: false, message: `خطای سرور: ${res.status}` };
                return await res.json();
            } catch (err) {
                console.error("Fetch Error:", err);
                return { success: false, message: 'خطا در ارتباط با سرور.' };
            }
        };

        const startResendTimer = (seconds) => {
            let s = Number(seconds || CFG.resendSeconds);
            resendBtn.disabled = true;
            if (timer) clearInterval(timer);
            
            const updateTimer = () => {
                if (s <= 0) {
                    clearInterval(timer);
                    resendBtn.disabled = false;
                    resendBtn.textContent = 'ارسال مجدد';
                } else {
                    resendBtn.innerHTML = `ارسال مجدد (<span>${s}</span>)`;
                    s--;
                }
            };
            updateTimer();
            timer = setInterval(updateTimer, 1000);
        };

        const handleSendCode = async () => {
            const mobile = (mobileEl.value || '').trim();
            if (!/^09[0-9]{9}$/.test(mobile)) {
                setMsg('لطفا شماره موبایل معتبر وارد کنید (مثال: 09xxxxxxxxx)', 'error');
                return;
            }
            setMsg('در حال ارسال کد...', 'info');
            sendBtn.disabled = true;
            resendBtn.style.display = 'inline-block';

            const resp = await post('send_otp', { mobile, csrf_token: csrfEl.value });
            
            sendBtn.disabled = false;
            if (resp && resp.success) {
                setMsg(resp.message || 'کد با موفقیت ارسال شد.', 'success');
                codeRow.style.display = 'block';
                verifyBtn.style.display = 'inline-block';
                sendBtn.style.display = 'none';
                startResendTimer(resp.resend_after);
            } else {
                setMsg(resp.message || 'ارسال کد ناموفق بود.', 'error');
            }
        };

        const handleVerifyCode = async () => {
            const mobile = (mobileEl.value || '').trim();
            const code = (codeEl.value || '').trim();
            if (!/^[0-9]{4,8}$/.test(code)) {
                setMsg('کد تایید باید بین 4 تا 8 رقم باشد.', 'error');
                return;
            }
            setMsg('در حال تایید کد...', 'info');
            verifyBtn.disabled = true;

            const resp = await post('verify_otp', { mobile, code, csrf_token: csrfEl.value });
            
            verifyBtn.disabled = false;
            if (resp && resp.success && resp.redirect_url) {
                setMsg('کد تایید شد! در حال انتقال...', 'success');
                window.location.href = resp.redirect_url;
            } else {
                setMsg(resp.message || 'کد وارد شده نامعتبر است.', 'error');
            }
        };

        // --- Event Listeners ---
        sendBtn?.addEventListener('click', handleSendCode);
        resendBtn?.addEventListener('click', handleSendCode);
        verifyBtn?.addEventListener('click', handleVerifyCode);
        codeEl?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') handleVerifyCode();
        });
    })();
    </script>
</body>
</html>

