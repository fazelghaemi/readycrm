<?php
// session_start() باید در ابتدای تمام فایل‌هایی که با سشن کار می‌کنند، فراخوانی شود
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// فایل کلاینت msgway را اضافه می‌کنیم
require_once('sms_client.php');

$step = 1; // مرحله اول: دریافت شماره موبایل
$message = '';
$mobile_number_display = '';

// بررسی اینکه آیا فرم شماره موبایل ارسال شده است
if (isset($_POST['send_otp'])) {
    $mobile = trim($_POST['mobile']);
    $mobile_number_display = htmlspecialchars($mobile);

    // TODO: در اینجا بهتر است شماره موبایل را اعتبارسنجی کنید (مثلا با Regex)

    // 1. یک کد رندوم 6 رقمی بساز
    $otp_code = rand(100000, 999999);

    // 2. کد را در سشن کاربر ذخیره کن تا بعدا آن را تایید کنیم
    $_SESSION['otp_code'] = $otp_code;
    $_SESSION['otp_mobile'] = $mobile;
    $_SESSION['otp_time'] = time(); // زمان ارسال کد را برای محاسبه انقضا ذخیره می‌کنیم

    // 3. پیامک را با استفاده از تابعی که در قدم دوم ساختیم، ارسال کن
    if (send_otp_message($mobile, $otp_code)) {
        $message = '<p class="message success">کد تایید به شماره شما ارسال شد.</p>';
        $step = 2; // برو به مرحله دوم: دریافت کد تایید
    } else {
        $message = '<p class="message error">خطا در ارسال پیامک. لطفاً از صحیح بودن تنظیمات در پنل ادمین اطمینان حاصل کنید.</p>';
        $step = 1;
    }
}

// بررسی اینکه آیا فرم کد تایید ارسال شده است
if (isset($_POST['verify_code'])) {
    $entered_code = trim($_POST['otp_code']);
    $mobile_number_display = htmlspecialchars($_SESSION['otp_mobile'] ?? '');

    // بررسی صحت کد وارد شده
    if (isset($_SESSION['otp_code']) && $entered_code == $_SESSION['otp_code']) {
        
        // بررسی زمان انقضای کد (مثلا 2 دقیقه یا 120 ثانیه)
        if (time() - $_SESSION['otp_time'] < 120) {
            
            $message = '<p class="message success">ورود موفقیت‌آمیز بود! در حال انتقال به پنل کاربری...</p>';
            
            // !!! مهم: اینجا باید منطق لاگین کردن کاربر در CRM خودتان را پیاده‌سازی کنید
            // 1. با استفاده از شماره موبایل (`$_SESSION['otp_mobile']`) کاربر را در جدول `users` پیدا کنید.
            // 2. سشن‌های مربوط به لاگین کاربر (مثلا `$_SESSION['user_id']` و `$_SESSION['role']`) را ست کنید.
            // 3. کاربر را به صفحه داشبورد هدایت کنید.
            
            // پاک کردن سشن‌های otp پس از استفاده
            unset($_SESSION['otp_code'], $_SESSION['otp_mobile'], $_SESSION['otp_time']);
            
            // مثال برای هدایت کاربر:
            // header('Location: dashboard.php');
            // exit();
            
        } else {
            $message = '<p class="message error">کد وارد شده منقضی شده است. لطفاً مجدداً درخواست کد دهید.</p>';
            $step = 1; // بازگشت به مرحله اول
        }

    } else {
        $message = '<p class="message error">کد وارد شده صحیح نیست.</p>';
        $step = 2; // در مرحله تایید کد باقی بمان
    }
}

?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ورود با کد یکبار مصرف</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; background-color: #f6f9fa; color: #1b1f2b; margin: 0; }
        .login-container { max-width: 400px; width: 100%; padding: 30px; background-color: #fff; border: 1px solid #e1e4e8; border-radius: 6px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        h2 { margin-top: 0; }
        p { color: #586069; }
        input[type="text"], input[type="tel"] { width: 100%; padding: 12px; margin-bottom: 15px; box-sizing: border-box; text-align: center; font-size: 18px; border: 1px solid #ccc; border-radius: 5px; letter-spacing: 3px; }
        button { width: 100%; padding: 12px; background-color: #28a745; color: white; border: none; cursor: pointer; border-radius: 5px; font-size: 16px; font-weight: 600; }
        button:hover { background-color: #218838; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 5px; font-weight: 500; }
        .success { background-color: #e6ffed; color: #28a745; border: 1px solid #a3d3ab; }
        .error { background-color: #ffebe9; color: #d73a49; border: 1px solid #f5b9c0; }
    </style>
</head>
<body>

<div class="login-container">
    <h2>ورود به سامانه</h2>
    <?php echo $message; ?>

    <?php if ($step == 1): ?>
        <form method="POST" action="">
            <p>لطفاً شماره موبایل خود را برای دریافت کد تایید وارد کنید:</p>
            <input type="tel" name="mobile" placeholder="09123456789" required autofocus>
            <button type="submit" name="send_otp">ارسال کد تایید</button>
        </form>
    <?php endif; ?>

    <?php if ($step == 2): ?>
        <form method="POST" action="">
            <p>کد ۶ رقمی ارسال شده به شماره <?php echo $mobile_number_display; ?> را وارد کنید:</p>
            <input type="text" name="otp_code" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" required autofocus>
            <button type="submit" name="verify_code">تایید و ورود</button>
        </form>
    <?php endif; ?>

</div>

</body>
</html>