
<?php
/**
 * نمونه‌ی هوک لاگین پس از تایید OTP
 * این فایل را در مسیر مناسب پروژه‌تان قرار دهید و از طریق autoload یا include فراخوانی کنید.
 * سپس در سیستم احراز هویت خود، کاربر را بر اساس شماره موبایل وارد کنید.
 */
function onOtpLoginSuccess($mobile, $pdo) {
    // 1) نرمال‌سازی شماره مطابق DB شما
    $m = preg_replace('/\s+/', '', (string)$mobile);
    // 2) کاربر را بر اساس شماره موبایل پیدا کنید
    //    مثال (جدول/فیلدها را با ساختار واقعی شما جایگزین کنید):
    /*
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE mobile = :m LIMIT 1");
    $stmt->execute([':m' => $m]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        // ایجاد کاربر جدید یا خطا
        return false;
    }
    // 3) سشن/کوکی ورود واقعی را تنظیم کنید
    $_SESSION['user_id'] = $user['id'];
    */
    // TODO: طبق سیستم واقعی شما پیاده‌سازی شود
    return true;
}
