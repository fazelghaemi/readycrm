<?php
require_once __DIR__ . '/../includes/codehub.php';
codehub_require_admin();
$sid = isset($_GET['sid']) ? (int)$_GET['sid'] : 0;
$vno = isset($_GET['v']) ? (int)$_GET['v'] : 0;
$pdo = codehub_pdo();
$stmt = $pdo->prepare("SELECT * FROM code_snippet_versions WHERE snippet_id=:sid AND version_no=:v");
$stmt->execute([':sid'=>$sid, ':v'=>$vno]);
$v = $stmt->fetch();
if (!$v) { http_response_code(404); exit('نسخه یافت نشد'); }
$snip = codehub_snippet_get($sid);
?><!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<title>نسخه <?= (int)$vno ?> — <?= e($snip['title']) ?></title>
<link rel="stylesheet" href="../public/assets/codehub.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/highlight.js@11.9.0/styles/github-dark.min.css">
<script src="https://cdn.jsdelivr.net/npm/highlight.js@11.9.0/lib/highlight.min.js"></script>
<script>document.addEventListener('DOMContentLoaded',()=>{hljs.highlightAll();});</script>
</head>
<body>
<div class="card">
  <div class="codehub-header">
    <h2 style="margin:0">نسخه <?= (int)$vno ?> — <?= e($snip['title']) ?></h2>
    <div class="codehub-actions">
      <a class="btn btn-ghost" href="snippet_view.php?id=<?= (int)$sid ?>">بازگشت</a>
    </div>
  </div>
  <pre><code><?= htmlspecialchars($v['content'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code></pre>
</div>
</body>
</html>
