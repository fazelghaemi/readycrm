<?php
require_once __DIR__ . '/../includes/codehub.php';
codehub_require_admin();

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) && !empty($_SESSION['user']['id'])) { $_SESSION['user_id'] = (int)$_SESSION['user']['id']; }

$sid = isset($_GET['sid']) ? (int)$_GET['sid'] : 0;
$vno = isset($_GET['v']) ? (int)$_GET['v'] : 0;
$pdo = codehub_pdo();
$stmt = $pdo->prepare("SELECT * FROM code_snippet_versions WHERE snippet_id=:sid AND version_no=:v");
$stmt->execute([':sid'=>$sid, ':v'=>$vno]);
$v = $stmt->fetch();
if (!$v) { http_response_code(404); exit('نسخه یافت نشد'); }
$snip = codehub_snippet_get($sid);

$page_title = 'نسخه '.$vno.' — '.e($snip['title']);
$breadcrumbs = [
  ['label'=>'CodeHub','url'=>'/codehub/snippets.php','active'=>false],
  ['label'=>$snip['title'],'url'=>'/codehub/snippet_view.php?id='.$sid,'active'=>false],
  ['label'=>'نسخه '.$vno,'active'=>true],
];

include_once __DIR__ . '/../include/header.php';
?>
<link rel="stylesheet" href="../public/assets/codehub-ultra.css">
<link rel="stylesheet" href="../public/assets/ch-highlight.css">
<div class="ch-wrap">
  <div class="ch-card">
    <div class="ch-head">
      <h2>نسخه <?= (int)$vno ?> — <?= e($snip['title']) ?></h2>
      <div class="ch-actions"><a class="ch-btn" style="border:1px solid var(--ch-border)" href="snippet_view.php?id=<?= (int)$sid ?>">بازگشت</a></div>
    </div>
    <pre data-ch-lang="<?= e($snip['language']) ?>"><?= htmlspecialchars($v['content'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></pre>
  </div>
</div>
<script src="../public/assets/ch-highlight.js"></script>
<?php if (file_exists(__DIR__.'/../footer.php')) include __DIR__.'/../footer.php'; ?>
