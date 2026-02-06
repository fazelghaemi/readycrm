<?php
/**
 * CRM V2 Installer
 * Wizard نصب 5 مرحله‌ای
 */

session_start();

// بررسی قفل نصب
if (file_exists(__DIR__ . '/../.install.lock')) {
    die('
    <!DOCTYPE html>
    <html dir="rtl" lang="fa">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>نصب قفل شده</title>
        <style>
            body { font-family: Tahoma, sans-serif; text-align: center; padding: 50px; background: #f5f5f5; }
            .error-box { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 500px; margin: 0 auto; }
            h1 { color: #d32f2f; }
            p { color: #666; line-height: 1.8; }
            .note { background: #fff3cd; padding: 15px; border-radius: 4px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h1>❌ نصب قبلاً انجام شده است</h1>
            <p>سیستم CRM با موفقیت نصب شده و قفل گشته است.</p>
            <div class="note">
                <strong>⚠️ توجه:</strong><br>
                برای نصب مجدد، فایل <code>.install.lock</code> را از ریشه پروژه حذف کنید.<br>
                <strong>اخطار:</strong> این عمل تمام داده‌های موجود را پاک می‌کند!
            </div>
        </div>
    </body>
    </html>
    ');
}

// بارگذاری توابع کمکی
require_once __DIR__ . '/functions.php';

// تعیین مرحله فعلی
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$step = max(1, min(5, $step));

// بارگذاری مرحله
$step_file = __DIR__ . '/steps/step' . $step . '-' . get_step_slug($step) . '.php';

if (!file_exists($step_file)) {
    die('مرحله نصب پیدا نشد!');
}

?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نصب CRM V2 - مرحله <?php echo $step; ?> از 5</title>
    <link rel="stylesheet" href="assets/install.css">
</head>
<body>
    <div class="install-wrapper">
        <!-- هدر -->
        <div class="install-header">
            <h1>🚀 نصب سیستم CRM نسخه 2.0</h1>
            <p>مرحله <?php echo $step; ?> از 5: <?php echo get_step_title($step); ?></p>
        </div>

        <!-- Progress Bar -->
        <div class="progress-bar">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <div class="progress-step <?php echo $i <= $step ? 'active' : ''; ?> <?php echo $i < $step ? 'completed' : ''; ?>">
                    <div class="step-number"><?php echo $i; ?></div>
                    <div class="step-name"><?php echo get_step_title($i); ?></div>
                </div>
            <?php endfor; ?>
        </div>

        <!-- محتوای مرحله -->
        <div class="install-content">
            <?php include $step_file; ?>
        </div>

        <!-- فوتر -->
        <div class="install-footer">
            <p>© <?php echo date('Y'); ?> Ready CRM - توسعه توسط <a href="https://readystudio.ir" target="_blank">ردی استودیو</a></p>
        </div>
    </div>
</body>
</html>
