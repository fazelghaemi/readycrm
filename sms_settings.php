
<?php
// شروع سشن برای مدیریت پیام‌ها
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. اتصال به دیتابیس
// فرض می‌کنیم شما یک فایل برای اتصال به دیتابیس دارید.
// این فایل را با آدرس صحیح فایل اتصال خودتان جایگزین کنید.
require_once('config/database.php'); // <--- !!! این خط را ویرایش کنید

$message = '';

// 2. بررسی اینکه آیا فرم ذخیره‌سازی ارسال شده است
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    // دریافت مقادیر از فرم
    $apiKey = trim($_POST['rahpayam_api_key']);
    $patternCode = trim($_POST['rahpayam_pattern_code']);

    try {
        // کوئری آپدیت برای کلید API
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = :value WHERE setting_key = 'rahpayam_api_key'");
        $stmt->execute(['value' => $apiKey]);

        // کوئری آپدیت برای کد الگو
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = :value WHERE setting_key = 'rahpayam_pattern_code'");
        $stmt->execute(['value' => $patternCode]);
        
        // اگر رکوردی وجود نداشت، آن را ایجاد کن (برای نصب اولیه)
        if ($stmt->rowCount() == 0) {
            $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('rahpayam_api_key', ?), ('rahpayam_pattern_code', ?)")
                ->execute([$apiKey, $patternCode]);
        }


        $message = '<div style="color: green; border: 1px solid green; padding: 10px; margin-bottom: 20px;">تنظیمات با موفقیت ذخیره شد.</div>';

    } catch (PDOException $e) {
        $message = '<div style="color: red; border: 1px solid red; padding: 10px; margin-bottom: 20px;">خطا در ذخیره تنظیمات: ' . $e->getMessage() . '</div>';
    }
}

// 3. خواندن مقادیر فعلی از دیتابیس برای نمایش در فرم
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('rahpayam_api_key', 'rahpayam_pattern_code')");
    $settings_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    die("خطا در خواندن تنظیمات از دیتابیس: " . $e->getMessage());
}

$currentApiKey = $settings_data['rahpayam_api_key'] ?? '';
$currentPatternCode = $settings_data['rahpayam_pattern_code'] ?? '';

?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تنظیمات ماژول پیامک راه پیام</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; padding: 20px; background-color: #f6f9fa; color: #1b1f2b; }
        .container { max-width: 800px; margin: auto; padding: 20px; background-color: #fff; border: 1px solid #e1e4e8; border-radius: 6px; }
        h2 { border-bottom: 1px solid #e1e4e8; padding-bottom: 10px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; }
        input[type="text"] { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 5px; }
        button { padding: 10px 20px; background-color: #00b0a4; color: white; border: none; cursor: pointer; border-radius: 5px; font-size: 16px; }
        button:hover { background-color: #098b82; }
        .help-text { font-size: 12px; color: #666; margin-top: 5px; }
    </style>
</head>
<body>

    <div class="container">
        <h2>تنظیمات درگاه پیامک (راه پیام)</h2>
        <?php echo $message; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="api_key">API Key (کلید وب‌سرویس):</label>
                <input type="text" id="api_key" name="rahpayam_api_key" value="<?php echo htmlspecialchars($currentApiKey); ?>" required>
            </div>
            <div class="form-group">
                <label for="pattern_code">Pattern Code (کد الگو برای OTP):</label>
                <input type="text" id="pattern_code" name="rahpayam_pattern_code" value="<?php echo htmlspecialchars($currentPatternCode); ?>" required>
                <p class="help-text">الگوی شما در پنل راه پیام باید شامل یک متغیر (مثلا %param1%) برای ارسال کد تایید باشد.</p>
            </div>
            <button type="submit" name="save_settings">ذخیره تنظیمات</button>
        </form>
    </div>

</body>
</html>