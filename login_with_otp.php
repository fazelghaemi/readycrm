<?php
// session_start() باید در ابتدای تمام فایل‌هایی که با سشن کار می‌کنند، فراخوانی شود
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// اگر کاربر از قبل لاگین کرده، به داشبورد هدایت شود
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

require_once('sms_client.php'); // فایل ارسال کننده پیامک

$step = 1; // مرحله اول: دریافت شماره موبایل
$message = '';
$mobile_number_display = '';

// بخش اول: ارسال کد تایید
if (isset($_POST['send_otp'])) {
    $mobile = trim($_POST['mobile']);
    $mobile_number_display = htmlspecialchars($mobile);

    // اعتبارسنجی اولیه شماره موبایل
    if (!preg_match('/^09[0-9]{9}$/', $mobile)) {
        $message = '<p class="message error">فرمت شماره موبایل صحیح نیست.</p>';
    } else {
        $otp_code = rand(100000, 999999);

        // ذخیره اطلاعات لازم در سشن
        $_SESSION['otp_code'] = $otp_code;
        $_SESSION['otp_mobile'] = $mobile;
        $_SESSION['otp_time'] = time();

        if (send_otp_message($mobile, $otp_code)) {
            $message = '<p class="message success">کد تایید به شماره شما ارسال شد.</p>';
            $step = 2; // برو به مرحله دوم
        } else {
            $message = '<p class="message error">خطا در ارسال پیامک. لطفاً از تنظیمات درگاه اطمینان حاصل کنید.</p>';
        }
    }
}

// بخش دوم: تایید کد و ورود کاربر
if (isset($_POST['verify_code'])) {
    $entered_code = trim($_POST['otp_code']);
    $mobile_number_display = htmlspecialchars($_SESSION['otp_mobile'] ?? '');

    if (empty($_SESSION['otp_code']) || $entered_code != $_SESSION['otp_code']) {
        $message = '<p class="message error">کد وارد شده صحیح نیست.</p>';
        $step = 2;
    } elseif (time() - $_SESSION['otp_time'] > 120) { // انقضای ۲ دقیقه‌ای
        $message = '<p class="message error">کد منقضی شده است. لطفاً مجدداً درخواست کد دهید.</p>';
        $step = 1;
        unset($_SESSION['otp_code'], $_SESSION['otp_mobile'], $_SESSION['otp_time']);
    } else {
        // --- شروع منطق اصلی ورود کاربر ---
        require_once('config/database.php'); // اتصال به دیتابیس
        
        try {
            $mobile_to_login = $_SESSION['otp_mobile'];
            $stmt = $pdo->prepare("SELECT * FROM users WHERE mobile = :mobile AND status = 'active' LIMIT 1");
            $stmt->execute(['mobile' => $mobile_to_login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // کاربر پیدا شد و فعال است -> اطلاعات کاربر را در سشن ذخیره می‌کنیم
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // آپدیت کردن آخرین زمان ورود کاربر
                $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
                $updateStmt->execute(['id' => $user['id']]);

                // پاک کردن اطلاعات یکبار مصرف از سشن
                unset($_SESSION['otp_code'], $_SESSION['otp_mobile'], $_SESSION['otp_time']);

                // هدایت کاربر به داشبورد
                header('Location: dashboard.php');
                exit();

            } else {
                // کاربری با این شماره موبایل پیدا نشد یا غیرفعال است
                $message = '<p class="message error">کاربری با این شماره موبایل یافت نشد یا حساب شما غیرفعال است.</p>';
                $step = 1; // بازگشت به مرحله اول
                unset($_SESSION['otp_code'], $_SESSION['otp_mobile'], $_SESSION['otp_time']);
            }
        } catch (PDOException $e) {
            $message = '<p class="message error">خطای سیستمی رخ داد. لطفاً بعداً تلاش کنید.</p>';
            $step = 1;
        }
        // --- پایان منطق اصلی ورود کاربر ---
    }
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود با کد یکبار مصرف</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{--primary:#00b0a4;--primary-dark:#098b82;--midnight:#0f172a;--white:#ffffff;--text:#0b1020;--muted:#334155;--border:#e6f3f1;--radius-xl:28px;--radius-lg:20px;--radius-pill:999px;--ring:0 0 0 6px rgba(0,176,164,.10);--shadow:0 18px 60px rgba(0,176,164,.22);}
        body{font-family:'Vazirmatn',sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background-color:#f6f9fa;color:#1b1f2b;margin:0;background:radial-gradient(1100px 800px at 10% -10%,rgba(0,176,164,.30) 0,rgba(0,176,164,.06) 45%,transparent 60%),linear-gradient(135deg,#00b0a4 0%, #f3f7f8 100%);}
        .login-container{max-width:450px;width:100%;padding:40px;background-color:#fff;border-radius:var(--radius-xl);text-align:center;box-shadow:var(--shadow);border:1px solid rgba(0,176,164,.08);}
        h2{margin-top:0;font-weight:800;color:var(--midnight);}
        p{color:#586069;}
        input[type="text"],input[type="tel"]{width:100%;padding:14px;margin-bottom:15px;box-sizing:border-box;text-align:center;font-size:18px;border:1px solid var(--border);border-radius:var(--radius-lg);letter-spacing:3px;}
        input:focus{border-color:var(--primary);box-shadow:var(--ring);outline:0;}
        button{width:100%;padding:14px;background:linear-gradient(180deg,var(--primary),var(--primary-dark));color:white;border:none;cursor:pointer;border-radius:var(--radius-pill);font-size:16px;font-weight:700;box-shadow:0 14px 30px rgba(0,176,164,.25);}
        button:hover{filter:saturate(1.02);}
        .message{padding:12px;margin-bottom:20px;border-radius:18px;font-weight:600;border-left:5px solid;}
        .message.success{background:#ecfdf5;color:#10b981;border-color:#10b981;}
        .message.error{background:#fff1f2;color:#e11d48;border-color:#e11d48;}
        .back-to-login{margin-top:20px;font-size:14px;}
        .back-to-login a{color:var(--primary-dark);text-decoration:none;font-weight:600;}
    </style>
</head>
<body>
<div class="login-container">
    <h2>ورود با کد تایید</h2>
    <?php echo $message; ?>
    <?php if ($step == 1): ?>
        <form method="POST" action="">
            <p>شماره موبایل خود را برای دریافت کد وارد کنید:</p>
            <input type="tel" name="mobile" placeholder="09123456789" required autofocus>
            <button type="submit" name="send_otp">ارسال کد</button>
        </form>
    <?php endif; ?>
    <?php if ($step == 2): ?>
        <form method="POST" action="">
            <p>کد ۶ رقمی ارسال شده به شماره <?php echo $mobile_number_display; ?> را وارد کنید:</p>
            <input type="text" name="otp_code" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" required autofocus>
            <button type="submit" name="verify_code">تایید و ورود</button>
        </form>
    <?php endif; ?>
    <div class="back-to-login">
        <a href="login.php"><i class="fas fa-arrow-right ms-1"></i> بازگشت به صفحه ورود اصلی</a>
    </div>
</div>
</body>
</html>