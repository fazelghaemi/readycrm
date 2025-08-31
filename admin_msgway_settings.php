
<?php
// msgway_otp_module/admin_msgway_settings.php
// Restrict to admin only. Assumes $_SESSION['user_role'] === 'admin' for admins.
// Requires: config/database.php provides $pdo (PDO instance).

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/csrf.php';

if (($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('دسترسی غیرمجاز');
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) exit;
    $ok  = setting_set($pdo, 'msgway_api_key', trim($_POST['msgway_api_key'] ?? ''));
    $ok &= setting_set($pdo, 'msgway_template_id', (string)intval($_POST['msgway_template_id'] ?? 1));
    $ok &= setting_set($pdo, 'msgway_otp_length', (string)max(4, min(8, intval($_POST['msgway_otp_length'] ?? 5))));
    $ok &= setting_set($pdo, 'msgway_otp_expiry', (string)max(60, min(600, intval($_POST['msgway_otp_expiry'] ?? 180))));
    $ok &= setting_set($pdo, 'msgway_resend_after', (string)max(15, min(180, intval($_POST['msgway_resend_after'] ?? 45))));
    $msg = $ok ? 'تنظیمات ذخیره شد.' : 'خطا در ذخیره تنظیمات.';
}

$apiKey     = setting_get($pdo, 'msgway_api_key', '');
$templateID = setting_get($pdo, 'msgway_template_id', '1');
$length     = setting_get($pdo, 'msgway_otp_length', '5');
$expiry     = setting_get($pdo, 'msgway_otp_expiry', '180');
$resend     = setting_get($pdo, 'msgway_resend_after', '45');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<title>تنظیمات OTP - MSGway</title>
<link rel="stylesheet" href="assets/msgway-otp.css">
</head>
<body>
<div class="container">
  <h2>تنظیمات OTP (MSGway)</h2>
  <?php if ($msg): ?><div class="status"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>API Key</label>
    <input type="password" name="msgway_api_key" value="<?= htmlspecialchars($apiKey) ?>" placeholder="کلید وب‌سرویس">

    <label>Template ID</label>
    <input type="number" name="msgway_template_id" value="<?= htmlspecialchars($templateID) ?>">

    <div class="row">
      <div>
        <label>طول کد</label>
        <input type="number" name="msgway_otp_length" value="<?= htmlspecialchars($length) ?>">
      </div>
      <div>
        <label>انقضا (ثانیه)</label>
        <input type="number" name="msgway_otp_expiry" value="<?= htmlspecialchars($expiry) ?>">
      </div>
      <div>
        <label>ارسال مجدد (ثانیه)</label>
        <input type="number" name="msgway_resend_after" value="<?= htmlspecialchars($resend) ?>">
      </div>
    </div>

    <button type="submit">ذخیره تنظیمات</button>
  </form>
</div>
</body>
</html>
