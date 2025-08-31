
<?php
declare(strict_types=1); session_start();
$global=__DIR__.'/../includes/security_bootstrap.php'; $module=__DIR__.'/includes/module_bootstrap.php';
if(is_file($global)) require_once $global; else require_once $module;
if(($_SESSION['role']??'')!=='admin'){ http_response_code(403); exit('دسترسی غیرمجاز'); }
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/includes/settings.php'; require_once __DIR__.'/includes/csrf.php';
$msg=''; if($_SERVER['REQUEST_METHOD']==='POST'){ if(!csrf_verify()) exit;
  $ok= setting_set($pdo,'msgway_api_key',trim((string)($_POST['msgway_api_key']??'')));
  $ok&=setting_set($pdo,'msgway_template_id',(string)intval($_POST['msgway_template_id']??1));
  $ok&=setting_set($pdo,'msgway_otp_length',(string)max(4,min(8,intval($_POST['msgway_otp_length']??5))));
  $ok&=setting_set($pdo,'msgway_otp_expiry',(string)max(60,min(600,intval($_POST['msgway_otp_expiry']??180))));
  $ok&=setting_set($pdo,'msgway_resend_after',(string)max(15,min(180,intval($_POST['msgway_resend_after']??45))));
  $msg=$ok?'تنظیمات ذخیره شد.':'خطا در ذخیره تنظیمات.';
}
$apiKey=(string)setting_get($pdo,'msgway_api_key','');
$templateID=(string)setting_get($pdo,'msgway_template_id','1');
$length=(string)setting_get($pdo,'msgway_otp_length','5');
$expiry=(string)setting_get($pdo,'msgway_otp_expiry','180');
$resend=(string)setting_get($pdo,'msgway_resend_after','45');
?><!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="utf-8">
<title>تنظیمات OTP - MSGway</title><link rel="stylesheet" href="assets/msgway-otp.css">
<style>body{display:block;background:#f6f9fa;color:#1b1f2b}.container{max-width:720px;margin:40px auto;background:#fff;color:#1b1f2b}.status{background:#f6f6f8;color:#111827}</style>
</head><body><div class="container"><h2>تنظیمات OTP (MSGway)</h2>
<?php if($msg):?><div class="status"><?= htmlspecialchars($msg,ENT_QUOTES,'UTF-8') ?></div><?php endif; ?>
<form method="post" autocomplete="off"><?= csrf_field() ?>
<label>API Key</label><input type="password" name="msgway_api_key" value="<?= htmlspecialchars($apiKey,ENT_QUOTES,'UTF-8') ?>">
<label>Template ID</label><input type="number" name="msgway_template_id" value="<?= htmlspecialchars($templateID,ENT_QUOTES,'UTF-8') ?>">
<div class="row"><div><label>طول کد</label><input type="number" name="msgway_otp_length" value="<?= htmlspecialchars($length,ENT_QUOTES,'UTF-8') ?>"></div>
<div><label>انقضا (ثانیه)</label><input type="number" name="msgway_otp_expiry" value="<?= htmlspecialchars($expiry,ENT_QUOTES,'UTF-8') ?>"></div>
<div><label>ارسال مجدد (ثانیه)</label><input type="number" name="msgway_resend_after" value="<?= htmlspecialchars($resend,ENT_QUOTES,'UTF-8') ?>"></div></div>
<button type="submit">ذخیره تنظیمات</button></form></div></body></html>
