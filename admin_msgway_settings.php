<?php
// msgway_otp_module/admin_msgway_settings.php
declare(strict_types=1);

session_start();
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('دسترسی غیرمجاز');
}

require_once __DIR__ . '/../includes/security_bootstrap.php';
require_once __DIR__ . '/../config/database.php';   // باید $pdo داشته باشد
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/csrf.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) exit;
    $ok  = setting_set($pdo, 'msgway_api_key',       trim((string)($_POST['msgway_api_key'] ?? '')));
    $ok &= setting_set($pdo, 'msgway_template_id',   (string)intval($_POST['msgway_template_id'] ?? 1));
    $ok &= setting_set($pdo, 'msgway_otp_length',    (string)max(4, min(8, intval($_POST['msgway_otp_length'] ?? 5))));
    $ok &= setting_set($pdo, 'msgway_otp_expiry',    (string)max(60, min(600, intval($_POST['msgway_otp_expiry'] ?? 180))));
    $ok &= setting_set($pdo, 'msgway_resend_after',  (string)max(15, min(180, intval($_POST['msgway_resend_after'] ?? 45))));
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
<link rel="stylesheet" href="../public/assets/readystudio-theme.css">
<style>
.container{max-width:720px;margin:32px auto;background:#fff;padding:24px;border-radius:16px;box-shadow:0 8px 32px #0001}
.status{margin:10px 0 14px;padding:10px 12px;border-radius:12px;background:#f6f6f8}
.row{display:flex;gap:10px}
.row>div{flex:1}
label{display:block;margin:6px 0}
input{width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:10px}
button{margin-top:12px;padding:12px 16px;border:0;border-radius:10px;background:linear-gradient(90deg,var(--rs-primary),var(--rs-primary-dark));color:#fff;font-weight:700}
</style>
</head>
<body>
<div class="container">
  <h2>تنظیمات OTP (MSGway)</h2>
  <?php if ($msg): ?><div class="status"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>API Key</label>
    <input type="password" name="msgway_api_key" value="<?= htmlspecialchars($apiKey, ENT_QUOTES, 'UTF-8') ?>" placeholder="کلید وب‌سرویس">

    <label>Template ID</label>
    <input type="number" name="msgway_template_id" value="<?= htmlspecialchars($templateID, ENT_QUOTES, 'UTF-8') ?>">

    <div class="row">
      <div>
        <label>طول کد</label>
        <input type="number" name="msgway_otp_length" value="<?= htmlspecialchars($length, ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div>
        <label>انقضا (ثانیه)</label>
        <input type="number" name="msgway_otp_expiry" value="<?= htmlspecialchars($expiry, ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div>
        <label>ارسال مجدد (ثانیه)</label>
        <input type="number" name="msgway_resend_after" value="<?= htmlspecialchars($resend, ENT_QUOTES, 'UTF-8') ?>">
      </div>
    </div>

    <button type="submit">ذخیره تنظیمات</button>
  </form>
</div>
</body>
</html>
