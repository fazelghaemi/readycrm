<?php
require_once __DIR__ . '/_bootstrap.php';
$cfg = cfg();
$u = require_login();
$p = get_profile((int)$u['id']);
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title><?= h($cfg['project']['name']) ?> • تنظیمات</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="assets/css/app.css">
  <meta name="csrf-token" content="<?= h(csrf_token()) ?>">
</head>
<body class="admin-body">
  <div class="admin-shell">
    <div class="admin-top">
      <div class="brand">تنظیمات</div>
      <div class="top-actions">
        <a class="pill" href="app.php">بازگشت</a>
        <a class="pill" href="logout.php">خروج</a>
      </div>
    </div>

    <section class="card">
      <h3>پروفایل (مثل تلگرام)</h3>
      <div class="row">
        <div class="avatar big">
          <?php if (!empty($p['avatar_path'])): ?><img src="<?= h($p['avatar_path']) ?>" alt="avatar"><?php else: ?><?= strtoupper(substr($u['username'],0,1)) ?><?php endif; ?>
        </div>
        <div style="flex:2">
          <label>نام نمایشی</label>
          <input id="displayName" value="<?= h($p['display_name']) ?>" maxlength="64" placeholder="مثلاً: امید">
          <label>Bio</label>
          <input id="bio" value="<?= h($p['bio']) ?>" maxlength="160" placeholder="یک متن کوتاه…">
          <label>تغییر عکس پروفایل</label>
          <input id="avatarFile" type="file" accept="image/*">
          <button id="saveProfile" class="btn-primary" style="margin-top:10px">ذخیره</button>
          <div id="saveRes" class="hint"></div>
        </div>
      </div>

      <hr>
      <h3>درباره</h3>
      <div class="hint">
        پروژه: <b><?= h($cfg['project']['name']) ?></b><br>
        طراح: <b><?= h($cfg['project']['designer']) ?></b>
      </div>
    </section>
  </div>

<script>
  window.PromptAllChat = { csrf: document.querySelector('meta[name="csrf-token"]').content };
</script>
<script src="assets/js/settings.js"></script>
</body>
</html>
