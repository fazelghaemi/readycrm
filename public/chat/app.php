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
  <title><?= h($cfg['project']['name']) ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="assets/css/app.css">
  <meta name="csrf-token" content="<?= h(csrf_token()) ?>">
</head>
<body class="app-body">
  <div class="app-shell">
    <aside class="sidebar">
      <div class="sidebar-top">
        <div class="me">
          <div class="avatar">
            <?php if (!empty($p['avatar_path'])): ?>
              <img src="<?= h($p['avatar_path']) ?>" alt="avatar">
            <?php else: ?>
              <?= strtoupper(substr($u['username'],0,1)) ?>
            <?php endif; ?>
          </div>
          <div class="me-meta">
            <div class="me-name"><?= h($p['display_name'] ?: $u['username']) ?></div>
            <div class="me-sub"><?= h($p['bio']) ?></div>
          </div>
        </div>
        <div class="top-actions">
          <a class="pill" href="settings.php">تنظیمات</a>
          <?php if ($u['role']==='admin'): ?><a class="pill" href="admin.php">پنل ادمین</a><?php endif; ?>
          <a class="pill" href="logout.php">خروج</a>
        </div>
      </div>

      <div class="sidebar-tabs">
        <button class="tab-btn active" data-tab="chats">چت‌ها</button>
        <button class="tab-btn" data-tab="users">کاربران</button>
      </div>

      <div id="tabChats" class="tab-panel">
        <div class="sidebar-title">گروه‌ها و کانال‌ها</div>
        <div id="roomList" class="room-list"><div class="room-item muted">در حال بارگذاری…</div></div>

        <div class="sidebar-title">پیام‌های خصوصی</div>
        <div id="dmList" class="room-list compact"><div class="room-item muted">—</div></div>
      </div>

      <div id="tabUsers" class="tab-panel" style="display:none">
        <div class="sidebar-title">کاربران (برای پیام خصوصی)</div>
        <div id="userList" class="room-list"><div class="room-item muted">در حال بارگذاری…</div></div>
      </div>
    </aside>

    <main class="chat">
      <div class="chat-header">
        <div class="chat-header-left">
          <div class="chat-header-avatar" id="roomAvatar"></div>
          <div>
            <div id="roomName" class="chat-title">—</div>
            <div id="roomMeta" class="chat-sub">—</div>
          </div>
        </div>
        <div class="chat-right">
          <button id="participantsBtn" class="pill small">اعضا</button>
          <span id="connState" class="dot offline" title="وضعیت اتصال"></span>
        </div>
      </div>

      <div id="msgList" class="messages"></div>

      <div class="composer">
        <input id="fileInput" type="file" class="file-input" />
        <button id="attachBtn" class="btn-ghost" title="ضمیمه">📎</button>
        <textarea id="msgInput" placeholder="پیام…" maxlength="2000"></textarea>
        <button id="sendBtn" class="btn-primary">ارسال</button>
      </div>
      <div id="uploadPreview" class="upload-preview" style="display:none"></div>
    </main>
  </div>

  <div id="modal" class="modal" style="display:none">
    <div class="modal-card">
      <div class="modal-top">
        <div class="modal-title" id="modalTitle">—</div>
        <button class="btn-ghost" id="modalClose">بستن</button>
      </div>
      <div class="modal-body" id="modalBody"></div>
    </div>
  </div>

<script>
  window.PromptAllChat = {
    csrf: document.querySelector('meta[name="csrf-token"]').content,
    me: <?= json_encode([
      'id' => $u['id'],
      'username' => $u['username'],
      'role' => $u['role'],
      'display_name' => $p['display_name'],
      'bio' => $p['bio'],
      'avatar_path' => $p['avatar_path'],
    ], JSON_UNESCAPED_UNICODE) ?>
  };
</script>
<script src="assets/js/app.js"></script>
</body>
</html>
