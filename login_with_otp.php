
<?php
// msgway_otp_module/login_with_otp.php
// Self-contained login form that supports sending & verifying OTP via MSGway.
// Assumes `users` table has `mobile` field and a session-based login helper.

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/msgway_client.php';
require_once __DIR__ . '/includes/otp.php';

use RS\MSGWAY\Client;

$apiKey     = setting_get($pdo, 'msgway_api_key', '');
$templateID = (int) setting_get($pdo, 'msgway_template_id', '1');
$length     = (int) setting_get($pdo, 'msgway_otp_length', '5');
$expiry     = (int) setting_get($pdo, 'msgway_otp_expiry', '180');
$resend     = (int) setting_get($pdo, 'msgway_resend_after', '45');

$client = null;
$error = '';
$ok = '';

try { $client = new Client($apiKey); }
catch (Exception $e) { $error = $e->getMessage(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) exit;
    $mobile = $_POST['mobile'] ?? '';
    if (isset($_POST['send'])) {
        if (!$client) { $error = $error ?: 'کلاینت MSGway در دسترس نیست.'; }
        else {
            // (Optional) ensure user exists & is active
            $stmt = $pdo->prepare("SELECT id, status FROM users WHERE mobile=:m LIMIT 1");
            $stmt->execute([':m' => $mobile]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user || ($user['status'] ?? '') !== 'active') {
                $error = 'کاربر با این شماره یافت نشد یا غیرفعال است.';
            } else {
                $r = otp_send($pdo, $client, $mobile, $templateID, $length, $expiry, $resend);
                $r['ok'] ? ($ok = $r['msg']) : ($error = $r['msg']);
            }
        }
    } elseif (isset($_POST['verify'])) {
        if (!$client) { $error = $error ?: 'کلاینت MSGway در دسترس نیست.'; }
        else {
            $code = $_POST['code'] ?? '';
            $r = otp_verify($pdo, $client, $mobile, $code);
            if ($r['ok']) {
                // Sign-in user
                $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE mobile=:m LIMIT 1");
                $stmt->execute([':m' => $mobile]);
                if ($u = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $_SESSION['user_id'] = $u['id'];
                    $_SESSION['username'] = $u['username'];
                    $_SESSION['role'] = $u['role'];
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'حساب کاربری یافت نشد.';
                }
            } else {
                $error = $r['msg'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<title>ورود با کد تایید (MSGway)</title>
<link rel="stylesheet" href="assets/msgway-otp.css">
</head>
<body>
<div class="container">
  <h2>ورود با کد تایید</h2>
  <?php if ($error): ?><div class="status error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($ok): ?><div class="status success"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>شماره موبایل</label>
    <input type="tel" name="mobile" inputmode="numeric" placeholder="09xxxxxxxxx" value="<?= htmlspecialchars($_POST['mobile'] ?? ($_SESSION['otp_mobile'] ?? '')) ?>" required>

    <div class="row">
      <div>
        <label>کد تایید</label>
        <input type="text" name="code" inputmode="numeric" placeholder="•••••">
      </div>
      <div class="actions">
        <button type="submit" name="send">ارسال / ارسال مجدد</button>
        <button type="submit" name="verify">تایید و ورود</button>
      </div>
    </div>
  </form>
  <div class="hint">پس از تایید، مستقیماً وارد داشبورد می‌شوید.</div>
</div>
</body>
</html>
