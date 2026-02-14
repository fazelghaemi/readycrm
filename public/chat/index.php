<?php
require_once __DIR__ . '/_bootstrap.php';
$cfg = cfg();
$u = current_user();
if ($u) { header('Location: app.php'); exit; }

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $username = trim((string)($_POST['username'] ?? ''));
  $password = (string)($_POST['password'] ?? '');
  if (login($username, $password)) { header('Location: app.php'); exit; }
  $err = 'نام کاربری یا رمز عبور اشتباه است.';
}
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title><?= h($cfg['project']['name']) ?> • ورود</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="login-body">
  <div class="login-card">
    <div class="brand"><?= h($cfg['project']['name']) ?></div>
    <div class="subtitle">ورود به چت خصوصی</div>

    <?php if ($err): ?><div class="alert"><?= h($err) ?></div><?php endif; ?>

    <form method="post" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <label>نام کاربری</label>
      <input name="username" maxlength="32" required>
      <label>رمز عبور</label>
      <input name="password" type="password" required>
      <button type="submit" class="btn-primary">ورود</button>
    </form>

    <div class="hint">دسترسی فقط از طریق ساخت اکانت توسط ادمین</div>
  </div>
</body>
</html>
