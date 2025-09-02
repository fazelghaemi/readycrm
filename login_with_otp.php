<?php
/**
 * login_with_otp.php — ReadyCRM
 * صفحهٔ کامل «ورود با کد تایید» با UI، ولیدیشن و جریان AJAX (ارسال/تایید کد)
 * بدون نیاز به فایل‌های JS/CSS جداگانه (همه‌چیز inline است).
 */

@session_start();

// === Core includes (بدون تغییر ساختار پروژه) ===
$inc = [
    __DIR__ . '/config/config.php',
    __DIR__ . '/config/database.php',
    __DIR__ . '/includes/auth.php',
    __DIR__ . '/includes/functions.php',
];
foreach ($inc as $f) { if (file_exists($f)) require_once $f; }

// اگر کاربر لاگین است، به داشبورد منتقل شود
if (function_exists('isLoggedIn') && isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// CSRF: تلاش برای استفاده از هلپرهای موجود؛ در غیر این صورت، تولید دستی
$csrf = '';
if (function_exists('generateCSRFToken')) {
    $csrf = generateCSRFToken();
} elseif (function_exists('getCSRFToken')) {
    $csrf = getCSRFToken();
} else {
    if (empty($_SESSION['csrf_token'])) {
        try {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        } catch (Throwable $e) {
            $_SESSION['csrf_token'] = bin2hex(strrev(uniqid('', true)));
        }
    }
    $csrf = $_SESSION['csrf_token'];
}

// آیا فایل هدر پروژه وجود دارد؟
$has_header = file_exists(__DIR__ . '/includes/header.php');

// خروجی HTML آغازین
if ($has_header) {
    // هدر پروژه را لود می‌کنیم تا فونت/تم/متاها یکپارچه باشد
    include __DIR__ . '/includes/header.php';
} else {
    // اسکلت حداقلی (اگر هدر نبود)
    echo "<!doctype html>\n<html lang='fa' dir='rtl'>\n<head>\n<meta charset='utf-8'>\n<meta name='viewport' content='width=device-width, initial-scale=1'>\n<title>ورود با کد تایید</title>\n";
    echo "<style>html,body{font-family:Tahoma,Arial,sans-serif;background:#f6f9fa;color:#1b1f2b}</style>\n";
    echo "</head>\n<body>\n";
}

// === استایل‌های اختصاصی این صفحه (inline) ===
?>
<style>
/* ---- OTP Login — Minimal, modern, RTL ---- */
:root{
  --brand-primary:#00b0a4;
  --brand-secondary:#098b82;
  --brand-midnight:#181c24;
  --brand-bg:#f6f9fa;
  --brand-text:#1b1f2b;
}
*{box-sizing:border-box}
body{background:var(--brand-bg); color:var(--brand-text); margin:0}
a{color:var(--brand-primary); text-decoration:none}
a:hover{text-decoration:underline}
.auth-container{
  min-height:100dvh; display:flex; align-items:center; justify-content:center; padding:24px;
  background: radial-gradient(1200px 600px at 80% -10%, rgba(0,176,164,.08), rgba(0,0,0,0));
}
.auth-card{
  width:min(960px,95%); background:#fff; border-radius:20px; overflow:hidden;
  box-shadow:0 10px 30px rgba(0,0,0,.08); display:grid; grid-template-columns:1.2fr 1fr;
}
@media (max-width: 900px){ .auth-card{grid-template-columns:1fr} .auth-right{display:none} }
.auth-left{ padding:32px 28px }
.auth-right{
  background:linear-gradient(135deg, var(--brand-primary), var(--brand-secondary)); color:#fff;
  padding:40px; display:flex; align-items:center; justify-content:center; text-align:center;
}
.auth-title{margin:0 0 6px; font-weight:800; font-size:24px}
.auth-subtitle{margin:0 0 18px; opacity:.85}
.form-group{margin:14px 0; display:flex; flex-direction:column; gap:6px}
.form-group label{font-weight:600}
.form-group input{
  height:44px; border:1px solid #e5e7eb; border-radius:12px; padding:0 12px; outline:none; background:#fff; font:inherit;
}
.form-group input:focus{border-color:var(--brand-primary); box-shadow:0 0 0 3px rgba(0,176,164,.12)}
.hint{font-size:12px; color:#6b7280}
.actions{display:flex; gap:10px; align-items:center; margin-top:10px; flex-wrap:wrap}
.btn{height:44px; padding:0 16px; border-radius:12px; border:1px solid transparent; cursor:pointer; font-weight:700}
.btn-primary{background:var(--brand-primary); color:#fff}
.btn-success{background:#10b981; color:#fff}
.btn-outline{background:transparent; border-color:#c7cdd4; color:#111827}
.btn:disabled{opacity:.6; cursor:not-allowed}
.msg{margin-top:14px; font-size:14px; min-height:20px}
.msg.success{color:#059669}
.msg.error{color:#dc2626}
.msg.info{color:#0ea5e9}
.brand .brand-logo{font-size:56px; line-height:1; margin-bottom:10px}
.brand .badge{display:inline-block; margin-top:8px; padding:6px 10px; border-radius:999px; background:rgba(255,255,255,.2); color:#fff}
.brand .badge.link{background:#fff; color:#0f766e; text-decoration:none}
.back-login{margin-top:16px}
.small-muted{font-size:12px; color:#6b7280; margin-top:10px}
</style>

<div class="auth-container">
  <div class="auth-card">
    <div class="auth-left">
      <h2 class="auth-title">ورود با کد تایید</h2>
      <p class="auth-subtitle">شماره موبایل خود را وارد کنید تا کد تایید برایتان پیامک شود.</p>

      <form id="otp-form" onsubmit="return false;" novalidate>
        <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">

        <div class="form-group">
          <label for="mobile">موبایل</label>
          <input id="mobile" name="mobile" type="tel" placeholder="مثال: 0912xxxxxxx" inputmode="numeric" autocomplete="tel" required>
          <small class="hint">فرمت مجاز: 09xxxxxxxxx</small>
        </div>

        <div id="code-row" class="form-group" style="display:none">
          <label for="code">کد تایید</label>
          <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{4,8}" maxlength="8" placeholder="کد 4 تا 8 رقمی" autocomplete="one-time-code">
        </div>

        <div class="actions">
          <button id="btn-send"   class="btn btn-primary" type="button">ارسال کد</button>
          <button id="btn-verify" class="btn btn-success" type="button" style="display:none">تایید</button>
          <button id="btn-resend" class="btn btn-outline" type="button" style="display:none" disabled>ارسال مجدد (<span id="resend-seconds">90</span>s)</button>
        </div>

        <div id="msg" class="msg" role="alert" aria-live="polite"></div>
        <div class="small-muted">با کلیک روی «ارسال کد»، با شرایط استفاده و حریم خصوصی موافقت می‌کنید.</div>
      </form>

      <div class="back-login">
        <a href="/login.php">ورود با نام کاربری/رمز عبور</a>
      </div>
    </div>

    <div class="auth-right">
      <div class="brand">
        <div class="brand-logo">⚙️</div>
        <h3 class="brand-title">سیستم CRM</h3>
        <p>مدیریت حرفه‌ای ارتباط با مشتریان — توسعه‌یافته توسط Ready Studio</p>
        <div class="brand-badges">
          <span class="badge">نسخه 1.0.0</span>
          <a class="badge link" href="https://ReadyStudio.ir" target="_blank" rel="noopener">ReadyStudio.ir</a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// === OTP Login — Inline JS (بدون نیاز به فایل جداگانه) ===
(function(){
  const $  = (sel) => document.querySelector(sel);
  const mobileEl = $('#mobile');
  const codeEl   = $('#code');
  const csrfEl   = $('#csrf_token');
  const sendBtn  = $('#btn-send');
  const verifyBtn= $('#btn-verify');
  const resendBtn= $('#btn-resend');
  const resendSec= $('#resend-seconds');
  const codeRow  = $('#code-row');
  const msg      = $('#msg');

  // پیکربندی — در صورت نیاز تغییر بده
  const CFG = {
    sendUrl:   '/otp.php?action=send',
    verifyUrl: '/otp.php?action=verify',
    resendSeconds: 90,
    redirectOnSuccess: '/dashboard.php'
  };

  let timer = null;

  function setMsg(text, type){
    msg.textContent = text || '';
    msg.className = 'msg ' + (type||'');
  }

  function validMobile(v){
    return /^09[0-9]{9}$/.test((v||'').trim());
  }

  async function post(url, data){
    try{
      const body = new URLSearchParams(data);
      const res = await fetch(url, {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
        body: body.toString(),
        credentials: 'same-origin'
      });
      let json = null;
      try { json = await res.json(); } catch(e){}
      return json || {success:false, message:'پاسخ نامعتبر از سرور'};
    }catch(err){
      return {success:false, message:'خطای ارتباط با سرور'};
    }
  }

  function startResend(seconds){
    let s = Number(seconds || CFG.resendSeconds || 90);
    if (Number.isNaN(s) || s < 1) s = 90;
    resendBtn.disabled = true;
    resendBtn.style.display = 'inline-block';
    resendSec.textContent = s.toString();
    if (timer) { clearInterval(timer); timer = null; }
    timer = setInterval(()=>{
      s--;
      if (s <= 0){
        clearInterval(timer); timer = null;
        resendBtn.disabled = false;
        resendSec.textContent = '0';
      } else {
        resendSec.textContent = String(s);
      }
    }, 1000);
  }

  async function handleSend(){
    const mobile = (mobileEl.value||'').trim();
    if (!validMobile(mobile)){
      setMsg('فرمت موبایل نادرست است. نمونه: 09xxxxxxxxx', 'error');
      mobileEl.focus();
      return;
    }
    setMsg('در حال ارسال کد...', 'info');
    sendBtn.disabled = true;

    const resp = await post(CFG.sendUrl, {mobile, csrf_token: csrfEl.value});
    sendBtn.disabled = false;

    if (resp && resp.success){
      setMsg(resp.message || 'کد تایید ارسال شد.', 'success');
      codeRow.style.display = 'block';
      verifyBtn.style.display = 'inline-block';
      startResend(Number(resp.resend_after || CFG.resendSeconds));
    } else {
      setMsg((resp && resp.message) || 'ارسال کد ناموفق بود.', 'error');
    }
  }

  async function handleVerify(){
    const mobile = (mobileEl.value||'').trim();
    const code   = (codeEl.value||'').trim();

    if (!validMobile(mobile)){ setMsg('موبایل نامعتبر است.', 'error'); mobileEl.focus(); return; }
    if (!/^[0-9]{4,8}$/.test(code)){ setMsg('کد ۴ تا ۸ رقمی را صحیح وارد کنید.', 'error'); codeEl.focus(); return; }

    setMsg('در حال تایید...', 'info');
    verifyBtn.disabled = true;

    const resp = await post(CFG.verifyUrl, {mobile, code, csrf_token: csrfEl.value});
    verifyBtn.disabled = false;

    if (resp && resp.success){
      setMsg(resp.message || 'ورود موفق.', 'success');
      const to = (resp.redirect && typeof resp.redirect === 'string') ? resp.redirect : CFG.redirectOnSuccess;
      window.location.href = to || '/';
    } else {
      setMsg((resp && resp.message) || 'کد نامعتبر یا منقضی است.', 'error');
    }
  }

  async function handleResend(){
    await handleSend();
  }

  // رویدادها
  sendBtn.addEventListener('click', handleSend);
  resendBtn.addEventListener('click', handleResend);
  verifyBtn.addEventListener('click', handleVerify);
  codeEl.addEventListener('keydown', function(e){
    if (e.key === 'Enter'){ e.preventDefault(); handleVerify(); }
  });
})();
</script>
<?php
// بستن اسکلت حداقلی اگر هدر پروژه وجود نداشت
if (!$has_header) {
    echo "</body></html>";
}
