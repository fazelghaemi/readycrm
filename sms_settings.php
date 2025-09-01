<?php
/**
 * sms_settings.php (v2)
 * Full settings for MSGway (RahPayam) OTP:
 *   - msgway_api_key (string)
 *   - msgway_template_code (int)
 *   - msgway_lineNumber (string, optional)
 *   - msgway_mobile_format (enum: auto, +98, 09)
 *   - msgway_resend_time (int seconds)
 *   - msgway_otp_length (int 4..8)
 * Backward-compatibility mirrors: rahpayam_api_key, rahpayam_pattern_code
 */

@session_start();

$projectRootFiles = [
    __DIR__ . '/config/config.php',
    __DIR__ . '/config/database.php',
    __DIR__ . '/includes/functions.php',
    __DIR__ . '/includes/auth.php',
];
foreach ($projectRootFiles as $file) { if (file_exists($file)) require_once $file; }

function ss_h($str) { return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8'); }
function ss_csrf_token() { if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); return $_SESSION['csrf_token']; }
function ss_verify_csrf($token) { return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token); }

function ss_get_pdo() {
    if (class_exists('Database')) {
        try {
            $db = new Database();
            if (method_exists($db, 'getConnection')) return $db->getConnection();
            if (property_exists($db, 'pdo')) return $db->pdo;
        } catch (Throwable $e) { error_log('Database init error: '.$e->getMessage()); }
    }
    $host = defined('DB_HOST') ? DB_HOST : getenv('DB_HOST');
    $name = defined('DB_NAME') ? DB_NAME : getenv('DB_NAME');
    $user = defined('DB_USER') ? DB_USER : getenv('DB_USER');
    $pass = defined('DB_PASS') ? DB_PASS : getenv('DB_PASS');
    $charset = 'utf8mb4';
    if ($host && $name && $user !== false && $pass !== false) {
        $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";
        $opts = [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false];
        try { return new PDO($dsn, (string)$user, (string)$pass, $opts); } catch (Throwable $e) { error_log('PDO fallback error: '.$e->getMessage()); }
    }
    return null;
}

function ss_get_setting($pdo, $key, $table = 'settings') {
    $sql = "SELECT setting_value FROM {$table} WHERE setting_key = :k LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':k' => $key]);
    $row = $stmt->fetch();
    return $row ? (string)$row['setting_value'] : '';
}

function ss_upsert_setting($pdo, $key, $value, $table = 'settings') {
    $update = $pdo->prepare("UPDATE {$table} SET setting_value = :v WHERE setting_key = :k");
    $update->execute([':v' => $value, ':k' => $key]);
    if ($update->rowCount() === 0) {
        $insert = $pdo->prepare("INSERT INTO {$table} (setting_key, setting_value) VALUES (:k, :v)");
        $insert->execute([':k' => $key, ':v' => $value]);
    }
}

if (function_exists('isLoggedIn') && !isLoggedIn()) { header('Location: login.php'); exit; }

$pdo = ss_get_pdo();
if (!$pdo) { http_response_code(500); echo '<h2>Database error</h2>'; exit; }

$errors = []; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!ss_verify_csrf($token)) { $errors[] = 'درخواست نامعتبر است.'; }
    else {
        $apiKey      = trim((string)($_POST['msgway_api_key'] ?? ''));
        $templateId  = trim((string)($_POST['msgway_template_code'] ?? ''));
        $lineNumber  = trim((string)($_POST['msgway_lineNumber'] ?? ''));
        $mobileFmt   = trim((string)($_POST['msgway_mobile_format'] ?? 'auto'));
        $resendTime  = (int)($_POST['msgway_resend_time'] ?? 60);
        $otpLen      = (int)($_POST['msgway_otp_length'] ?? 6);

        if ($apiKey === '') $errors[] = 'کلید API نمی‌تواند خالی باشد.';
        if ($templateId === '' || !preg_match('/^\d+$/', $templateId)) $errors[] = 'کد الگو باید عددی باشد.';
        if (!in_array($mobileFmt, ['auto','+98','09'], true)) $mobileFmt = 'auto';
        if ($resendTime < 15 || $resendTime > 600) $resendTime = 60;
        if ($otpLen < 4 || $otpLen > 8) $otpLen = 6;

        if (!$errors) {
            try {
                $pdo->beginTransaction();
                ss_upsert_setting($pdo, 'msgway_api_key',        $apiKey);
                ss_upsert_setting($pdo, 'msgway_template_code',  $templateId);
                ss_upsert_setting($pdo, 'msgway_lineNumber',     $lineNumber);
                ss_upsert_setting($pdo, 'msgway_mobile_format',  $mobileFmt);
                ss_upsert_setting($pdo, 'msgway_resend_time',    (string)$resendTime);
                ss_upsert_setting($pdo, 'msgway_otp_length',     (string)$otpLen);
                // legacy mirrors
                ss_upsert_setting($pdo, 'rahpayam_api_key',      $apiKey);
                ss_upsert_setting($pdo, 'rahpayam_pattern_code', $templateId);
                $pdo->commit();
                $success = 'تنظیمات با موفقیت ذخیره شد.';
            } catch (Throwable $e) {
                $pdo->rollBack();
                error_log('Save settings error: ' . $e->getMessage());
                $errors[] = 'خطا در ذخیره تنظیمات.';
            }
        }
    }
}

$cur = [
    'msgway_api_key'       => ss_get_setting($pdo, 'msgway_api_key') ?: ss_get_setting($pdo, 'rahpayam_api_key'),
    'msgway_template_code' => ss_get_setting($pdo, 'msgway_template_code') ?: ss_get_setting($pdo, 'rahpayam_pattern_code'),
    'msgway_lineNumber'    => ss_get_setting($pdo, 'msgway_lineNumber'),
    'msgway_mobile_format' => ss_get_setting($pdo, 'msgway_mobile_format') ?: 'auto',
    'msgway_resend_time'   => ss_get_setting($pdo, 'msgway_resend_time') ?: '60',
    'msgway_otp_length'    => ss_get_setting($pdo, 'msgway_otp_length') ?: '6',
];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تنظیمات پیامک (MSGway)</title>
<style>
:root{--primary:#00b0a4;--primary-dark:#098b82;--bg:#f6f8fb;--card:#fff;--text:#1f2937;--muted:#6b7280;--border:#e5e7eb}
*{box-sizing:border-box}body{margin:0;font-family:IRANSans,Vazirmatn,Segoe UI,Roboto,Arial,sans-serif;background:var(--bg);color:var(--text)}
.container{max-width:980px;margin:40px auto;padding:0 16px}.card{background:var(--card);border:1px solid var(--border);border-radius:16px;box-shadow:0 8px 30px rgba(2,8,20,.05);overflow:hidden}
.head{display:flex;gap:12px;align-items:center;padding:18px 22px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff}
.head .logo{width:36px;height:36px;display:grid;place-items:center;background:rgba(255,255,255,.15);border-radius:12px}
.body{padding:22px}.grid{display:grid;grid-template-columns:1fr;gap:16px}@media(min-width:760px){.grid{grid-template-columns:1fr 1fr}}
label{display:block;font-weight:700;margin-bottom:8px}.muted{color:var(--muted);font-size:12px}
input,select{width:100%;padding:12px 14px;border:1px solid var(--border);border-radius:12px;background:#fff;outline:none}
input:focus,select:focus{border-color:var(--primary);box-shadow:0 0 0 4px rgba(0,176,164,.1)}
.actions{margin-top:12px}.btn{appearance:none;border:none;border-radius:12px;padding:12px 18px;font-weight:800;cursor:pointer}
.btn-primary{background:var(--primary);color:#fff;box-shadow:0 6px 18px rgba(0,176,164,.25)}
.alert{padding:12px 16px;border-radius:12px;margin:10px 0;border:1px solid}.alert-success{background:#ecfdf5;color:#065f46;border-color:#a7f3d0}.alert-danger{background:#fef2f2;color:#991b1b;border-color:#fecaca}
.kbd{font:500 12px/1.4 ui-monospace,SFMono-Regular,Menlo,Consolas,"Liberation Mono",monospace;background:#f3f4f6;border:1px solid #e5e7eb;padding:2px 6px;border-radius:6px}
</style>
</head>
<body>
<div class="container">
  <div class="card">
    <div class="head"><div class="logo">💬</div><h3>تنظیمات پیامک تایید هویت (MSGway / راه‌پیام)</h3></div>
    <div class="body">
      <?php if ($success): ?><div class="alert alert-success"><?php echo ss_h($success); ?></div><?php endif; ?>
      <?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $e) echo '<div>• '.ss_h($e).'</div>'; ?></div><?php endif; ?>

      <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo ss_h(ss_csrf_token()); ?>">
        <div class="grid">
          <div>
            <label for="msgway_api_key">کلید API</label>
            <input type="password" id="msgway_api_key" name="msgway_api_key" value="<?php echo ss_h($cur['msgway_api_key']); ?>" placeholder="sk_live_xxx">
            <div class="muted">کلید معتبر MSGway/راه‌پیام</div>
          </div>
          <div>
            <label for="msgway_template_code">کد الگو (templateID)</label>
            <input type="text" id="msgway_template_code" name="msgway_template_code" value="<?php echo ss_h($cur['msgway_template_code']); ?>" placeholder="مثال: 3" inputmode="numeric">
            <div class="muted">الگوی OTP تأییدشده (مثلاً 3)</div>
          </div>
          <div>
            <label for="msgway_lineNumber">خط ارسال (lineNumber)</label>
            <input type="text" id="msgway_lineNumber" name="msgway_lineNumber" value="<?php echo ss_h($cur['msgway_lineNumber']); ?>" placeholder="مثال: 3000xxxxx (اختیاری/در برخی حساب‌ها اجباری)">
            <div class="muted">اگر حساب شما نیاز به تعیین خط دارد، این فیلد را پر کنید؛ عدم تکمیل می‌تواند 400 بدهد.</div>
          </div>
          <div>
            <label for="msgway_mobile_format">فرمت ذخیره شماره</label>
            <select id="msgway_mobile_format" name="msgway_mobile_format">
              <?php $opts=['auto'=>'اتوماتیک (+98 به‌صورت هوشمند)','+98'=>'+98xxxxxxxxxx','09'=>'09xxxxxxxxx']; foreach($opts as $k=>$v){ $sel = ($cur['msgway_mobile_format']===$k)?'selected':''; echo "<option value=\"$k\" $sel>$v</option>"; } ?>
            </select>
          </div>
          <div>
            <label for="msgway_resend_time">زمان ارسال مجدد (ثانیه)</label>
            <input type="number" id="msgway_resend_time" name="msgway_resend_time" min="15" max="600" value="<?php echo ss_h($cur['msgway_resend_time']); ?>">
          </div>
          <div>
            <label for="msgway_otp_length">طول کد OTP</label>
            <input type="number" id="msgway_otp_length" name="msgway_otp_length" min="4" max="8" value="<?php echo ss_h($cur['msgway_otp_length']); ?>">
          </div>
        </div>
        <div class="actions"><button type="submit" name="save_settings" class="btn btn-primary">ذخیره تنظیمات</button></div>
      </form>

      <p class="muted">نکته: اگر ۴۰۰ می‌گیرید، معمولاً یکی از موارد زیر است: <span class="kbd">apiKey</span> نامعتبر، <span class="kbd">templateID</span> تأییدنشده، نیاز به <span class="kbd">lineNumber</span>، یا فرمت اشتباه شماره.</p>
    </div>
  </div>
</div>
</body>
</html>
