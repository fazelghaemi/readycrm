<?php
require_once __DIR__ . '/_bootstrap.php';
$cfg = cfg();
$u = require_admin();

$pdo = db();
$rooms = $pdo->query("SELECT id, name, type, is_readonly, created_at FROM rooms WHERE type IN ('group','channel') ORDER BY id DESC")->fetchAll();
$users = $pdo->query("SELECT id, username, role, is_active, created_at FROM users ORDER BY id DESC LIMIT 100")->fetchAll();
$uploads = $pdo->query("SELECT up.id, up.original_name, up.stored_name, up.mime, up.size_bytes, up.created_at, us.username
                        FROM uploads up JOIN users us ON us.id=up.user_id
                        ORDER BY up.id DESC LIMIT 100")->fetchAll();
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title><?= h($cfg['project']['name']) ?> • پنل ادمین</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="assets/css/app.css">
  <meta name="csrf-token" content="<?= h(csrf_token()) ?>">
</head>
<body class="admin-body">
  <div class="admin-shell">
    <div class="admin-top">
      <div class="brand">پنل ادمین</div>
      <div class="top-actions">
        <a class="pill" href="app.php">بازگشت</a>
        <a class="pill" href="settings.php">تنظیمات</a>
        <a class="pill" href="logout.php">خروج</a>
      </div>
    </div>

    <div class="grid">
      <section class="card">
        <h3>ساخت کاربر</h3>
        <div class="row">
          <input id="newUser" placeholder="username" maxlength="32">
          <input id="newPass" placeholder="password (min 8)" type="text">
        </div>
        <div class="row">
          <select id="newRole">
            <option value="user">کاربر</option>
            <option value="admin">ادمین</option>
          </select>
          <button id="createUserBtn" class="btn-primary">ایجاد</button>
        </div>
        <div id="userResult" class="hint"></div>

        <hr>
        <h3>کاربران</h3>
        <div class="table">
          <div class="thead">
            <div>یوزرنیم</div><div>نقش</div><div>فعال</div><div>تاریخ</div>
          </div>
          <?php foreach ($users as $us): ?>
            <div class="trow">
              <div><?= h($us['username']) ?></div>
              <div><?= h($us['role']) ?></div>
              <div><?= ((int)$us['is_active']===1 ? '✅' : '⛔') ?></div>
              <div class="mono"><?= h($us['created_at']) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="card">
        <h3>ساخت گروه/کانال</h3>
        <div class="row">
          <input id="roomNameInput" placeholder="نام" maxlength="64">
          <select id="roomType">
            <option value="group">گروه</option>
            <option value="channel">کانال</option>
          </select>
        </div>
        <div class="row">
          <label class="check">
            <input id="roomReadonly" type="checkbox">
            فقط ادمین بتواند ارسال کند (کانال)
          </label>
          <button id="createRoomBtn" class="btn-primary">ایجاد</button>
        </div>
        <div id="roomResult" class="hint"></div>

        <hr>
        <h3>گروه‌ها و کانال‌ها</h3>
        <div class="table">
          <div class="thead rooms">
            <div>نام</div><div>نوع</div><div>Readonly</div><div>تاریخ</div><div>عملیات</div>
          </div>
          <?php foreach ($rooms as $r): ?>
            <div class="trow rooms">
              <div><?= h($r['name']) ?></div>
              <div><?= h($r['type']) ?></div>
              <div><?= ((int)$r['is_readonly']===1 ? '✅' : '—') ?></div>
              <div class="mono"><?= h($r['created_at']) ?></div>
              <div><button class="btn-danger" data-del-room="<?= (int)$r['id'] ?>">حذف</button></div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="hint">حذف Room باعث حذف پیام‌ها می‌شود.</div>

        <hr>
        <h3>مدیریت فایل‌ها</h3>
        <div class="table">
          <div class="thead uploads">
            <div>فایل</div><div>کاربر</div><div>نوع</div><div>حجم</div><div>تاریخ</div><div>عملیات</div>
          </div>
          <?php foreach ($uploads as $up): ?>
            <div class="trow uploads">
              <div><a class="link" href="<?= h(upload_public_url($up['stored_name'])) ?>" target="_blank"><?= h($up['original_name']) ?></a></div>
              <div><?= h($up['username']) ?></div>
              <div class="mono"><?= h($up['mime']) ?></div>
              <div class="mono"><?= number_format((int)$up['size_bytes']/1024, 1) ?> KB</div>
              <div class="mono"><?= h($up['created_at']) ?></div>
              <div><button class="btn-danger" data-del-upload="<?= (int)$up['id'] ?>">حذف</button></div>
            </div>
          <?php endforeach; ?>
        </div>
        <div id="uploadResult" class="hint"></div>
      </section>
    </div>
  </div>

<script>
  window.PromptAllChat = { csrf: document.querySelector('meta[name="csrf-token"]').content };
</script>
<script src="assets/js/admin.js"></script>
</body>
</html>
