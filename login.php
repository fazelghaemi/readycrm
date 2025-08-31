<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// اگر کاربر لاگین کرده است، به داشبورد هدایت شود
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username   = sanitizeInput($_POST['username']);
    $password   = $_POST['password'];
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($csrf_token)) {
        $error = 'درخواست نامعتبر. لطفاً مجدداً تلاش کنید.';
    } elseif (empty($username) || empty($password)) {
        $error = 'لطفاً تمام فیلدها را پر کنید';
    } else {
        $result = loginUser($username, $password);
        if ($result['success']) {
            header('Location: dashboard.php');
            exit();
        } else {
            $error = $result['message'];
        }
    }
}

if (isset($_GET['expired'])) {
    $error = 'جلسه شما منقضی شده است. لطفاً مجدداً وارد شوید.';
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ورود به سیستم - <?php echo APP_NAME; ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;600;700;800&display=swap" rel="stylesheet">

<style>
:root{
  /* Ready Studio Brand */
  --primary:#00b0a4;
  --primary-dark:#098b82;
  --midnight:#0f172a;
  --white:#ffffff;

  /* UI */
  --text:#0b1020;
  --muted:#334155;
  --border:#e6f3f1;
  --radius-xl:28px;
  --radius-lg:20px;
  --radius-pill:999px;
  --ring:0 0 0 6px rgba(0,176,164,.10);
  --shadow:0 18px 60px rgba(0,176,164,.22);
}

html,body{height:100%}
body{
  font-family:'Vazirmatn',sans-serif;
  background:
    radial-gradient(1100px 800px at 10% -10%,rgba(0,176,164,.30) 0,rgba(0,176,164,.06) 45%,transparent 60%),
    linear-gradient(135deg,#00b0a4 0%, #f3f7f8 100%);
  display:flex;align-items:center;justify-content:center;
  color:var(--text);
  padding:16px;
}

.login-container{
  background:var(--white);
  border-radius:var(--radius-xl);
  box-shadow:var(--shadow);
  overflow:hidden;
  max-width:1000px;width:100%;
  border:1px solid rgba(0,176,164,.08);
}

.login-image{
  position:relative;
  background:linear-gradient(35deg,var(--primary),var(--primary-dark));
  color:#eafffb;
  padding:64px 40px;
  min-height:520px;
  display:flex;align-items:center;justify-content:center;text-align:center;
}

.login-image h1{font-weight:800;font-size:2.6rem;margin-bottom:10px}
.login-image p{opacity:.95;margin-bottom:6px}

.brand-chips{
  display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:18px
}
.brand-chip{
  display:inline-flex;align-items:center;gap:.5rem;
  padding:.55rem .9rem;border-radius:var(--radius-pill);
  background:rgba(255,255,255,.16);
  border:1px solid rgba(255,255,255,.25);
  transition:transform .08s ease, box-shadow .2s;
  color:#fff;text-decoration:none;font-weight:700;
}
.brand-chip:hover{transform:translateY(-1px);box-shadow:0 8px 20px rgba(0,0,0,.12)}

.login-form{padding:64px 44px}
.form-title{text-align:center;margin-bottom:24px;font-weight:800;color:var(--midnight)}

.alert{border:none;border-radius:18px}
.alert-danger{background:#fff1f2;color:#e11d48;border-left:5px solid #e11d48}
.alert-success{background:#ecfdf5;color:#10b981;border-left:5px solid #10b981}

/* ===== Float Fields (Glass) ===== */
.float-field{
  position:relative;margin-bottom:16px;
}
.float-control{
  width:100%;
  background:rgba(255,255,255,.55);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border:1px solid rgba(0,176,164,.15);
  border-radius:var(--radius-lg);
  padding:1rem 3.1rem 1rem 1rem; /* فضای آیکن سمت چپ */
  font-size:16px; color:var(--text);
  transition:border-color .2s ease, box-shadow .2s ease;
}
.float-control::placeholder{color:transparent}
.float-label{
  position:absolute; right:1rem; top:50%; transform:translateY(-50%);
  background:transparent; padding:0 .35rem; color:var(--muted);
  pointer-events:none; transition: all .18s ease;
  font-weight:600;
}
.field-icon{
  position:absolute; left:14px; top:50%; transform:translateY(-50%);
  width:34px;height:34px; border-radius:12px;
  display:grid;place-items:center;
  color:var(--primary-dark);
  background:rgba(0,176,164,.08);
  border:1px solid rgba(0,176,164,.18);
}

/* Focus + filled */
.float-control:focus{border-color:var(--primary);box-shadow:var(--ring);outline:0}
.float-control:focus + .float-label,
.float-control:not(:placeholder-shown) + .float-label{
  top:0; transform:translateY(-50%) scale(.86);
  background:#fff; border-radius:8px; color:var(--primary-dark)
}

/* Password eye button */
.toggle-pass{
  position:absolute; left:56px; top:50%; transform:translateY(-50%);
  border:0; background:transparent; color:var(--primary-dark);
  width:34px;height:34px;border-radius:10px;
}
.toggle-pass:hover{background:rgba(0,176,164,.08)}
.toggle-pass:focus{outline:0; box-shadow:var(--ring)}

/* Checkbox */
.form-check-input{width:1.1rem;height:1.1rem;border-radius:8px;border:2px solid var(--border)}
.form-check-input:checked{background-color:var(--primary);border-color:var(--primary)}

/* CTA */
.btn-primary{
  position:relative;
  background: linear-gradient(180deg,var(--primary),var(--primary-dark));
  border:none;border-radius:var(--radius-pill);
  padding:14px 20px;font-weight:800;font-size:16px;width:100%;
  transition:transform .06s ease, box-shadow .25s ease, background .2s ease;
  box-shadow:0 14px 30px rgba(0,176,164,.25);
}
.btn-primary:hover{filter:saturate(1.02)}
.btn-primary:active{transform:translateY(1px)}
.btn-primary::after{
  content:""; position:absolute; inset:0 0 auto 0; height:46%;
  background:linear-gradient(180deg,rgba(255,255,255,.35),transparent);
  border-radius:inherit; pointer-events:none;
}

/* CapsLock feedback */
#password[data-caps="true"]{
  border-color:#f1c40f !important;
  box-shadow:0 0 0 6px rgba(241,196,15,.25) !important;
}

/* Footer */
.login-footer{text-align:center;margin-top:20px;color:var(--muted)}
.forgot-password{color:var(--primary-dark);font-weight:700;text-decoration:none}
.forgot-password:hover{color:var(--primary)}

/* Responsive */
@media (max-width:992px){
  .login-image{min-height:280px;padding:40px 24px}
  .login-form{padding:40px 24px}
}
@media (max-width:576px){
  .login-container{border-radius:20px}
  .login-image{border-radius:20px}
  .login-form{padding:28px 18px}
}
</style>
</head>
<body>
  <div class="login-container">
    <div class="row g-0">
      <!-- Brand panel -->
      <div class="col-lg-6 d-none d-lg-block">
        <div class="login-image">
          <div>
            <div class="mb-4" aria-label="Ready Studio logo" title="Ready Studio">
              <!-- لوگوی موقت -->
              <i class="fas fa-dice-d20 fa-4x"></i>
            </div>
            <h1>سیستم CRM</h1>
            <p>مدیریت حرفه‌ای ارتباط با مشتریان</p>
            <p>افزایش فروش، بهبود خدمات و رضایت مشتریان</p>

            <div class="brand-chips">
              <a class="brand-chip" href="https://readystudio.ir/" target="_blank" rel="noopener">
                <i class="fa-solid fa-link"></i> ReadyStudio.ir
              </a>
              <span class="brand-chip"><i class="fa-solid fa-shield"></i> امن و مطمئن</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Form -->
      <div class="col-12 col-lg-6">
        <div class="login-form">
          <h2 class="form-title">ورود به سیستم</h2>

          <?php if ($error): ?>
            <div class="alert alert-danger">
              <i class="fas fa-exclamation-circle me-2"></i>
              <?php echo $error; ?>
            </div>
          <?php endif; ?>

          <?php if ($success): ?>
            <div class="alert alert-success">
              <i class="fas fa-check-circle me-2"></i>
              <?php echo $success; ?>
            </div>
          <?php endif; ?>

          <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <!-- Username -->
            <div class="float-field">
              <input type="text" class="float-control" id="username" name="username"
                     value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                     placeholder=" " required autocomplete="username">
              <label for="username" class="float-label">نام کاربری یا ایمیل</label>
              <span class="field-icon" aria-hidden="true"><i class="fa-solid fa-user"></i></span>
            </div>

            <!-- Password -->
            <div class="float-field">
              <input type="password" class="float-control" id="password" name="password"
                     placeholder=" " required autocomplete="current-password">
              <label for="password" class="float-label">رمز عبور</label>
              <span class="field-icon" aria-hidden="true"><i class="fa-solid fa-lock"></i></span>
              <button type="button" id="togglePass" class="toggle-pass" aria-label="نمایش/پنهان کردن رمز">
                <i class="fa-solid fa-eye"></i>
              </button>
            </div>

            <div class="mb-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me">
                <label class="form-check-label" for="remember_me">مرا به خاطر بسپار</label>
              </div>
            </div>

            <button type="submit" name="login" class="btn btn-primary">
              <i class="fas fa-sign-in-alt ms-1"></i>
              ورود
            </button>
          </form>

          <div class="login-footer">
            <a href="#" class="forgot-password">رمز عبور خود را فراموش کرده‌اید؟</a>
            <p class="mt-3 mb-0"><small>نسخه <?php echo APP_VERSION; ?></small></p>
            <p class="mt-2 mb-0">
              <small>شخصی‌سازی‌شده توسط
                <a href="https://readystudio.ir/" target="_blank" rel="noopener" class="text-decoration-none" style="color:var(--primary-dark);font-weight:700">
                  Ready Studio
                </a>
              </small>
            </p>
          </div>
        </div>
      </div>

    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle password visibility
const pass = document.getElementById('password');
const toggle = document.getElementById('togglePass');
const form = document.querySelector('form');
const submitBtn = document.querySelector('button[name="login"]');

toggle?.addEventListener('click', ()=>{
  const t = pass.type === 'password' ? 'text' : 'password';
  pass.type = t;
  toggle.firstElementChild.className = t==='text' ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
});

// CapsLock indicator
pass?.addEventListener('keyup', (e)=>{
  const isCaps = e.getModifierState && e.getModifierState('CapsLock');
  pass.toggleAttribute('data-caps', !!isCaps);
});

// Submit loading state
form?.addEventListener('submit', ()=>{
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin ms-1"></i> در حال ورود…';
});
</script>
</body>
</html>
