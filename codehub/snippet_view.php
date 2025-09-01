<?php
require_once __DIR__ . '/../includes/codehub.php';
codehub_require_admin();

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) && !empty($_SESSION['user']['id'])) { $_SESSION['user_id'] = (int)$_SESSION['user']['id']; }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$item = $id ? codehub_snippet_get($id) : null;
if (!$item) { http_response_code(404); exit('یافت نشد'); }
$versions = codehub_versions($id);

$pdo = codehub_pdo();
$files = $pdo->prepare("SELECT * FROM code_snippet_files WHERE snippet_id=:id ORDER BY id DESC");
$files->execute([':id'=>$id]); $files = $files->fetchAll() ?: [];

$isStar = codehub_is_starred($id);

$page_title = e($item['title']).' | CodeHub';
$breadcrumbs = [ ['label'=>'CodeHub','url'=>'/codehub/snippets.php','active'=>false], ['label'=>$item['title'],'active'=>true] ];

include_once __DIR__ . '/../include/header.php';
?>
<link rel="stylesheet" href="../public/assets/codehub-ultra.css">
<link rel="stylesheet" href="../public/assets/ch-highlight.css">

<div class="ch-wrap">
  <div class="ch-card">
    <div class="ch-head">
      <h2><?= e($item['title']) ?></h2>
      <div class="ch-actions ch-toolbar">
        <a class="ch-btn" style="border:1px solid var(--ch-border)" href="snippets.php">لیست</a>
        <a class="ch-btn" style="border:1px solid var(--ch-border)" href="snippet_form.php?id=<?= (int)$id ?>">ویرایش</a>
        <a class="ch-btn ch-btn-ghost" href="download_raw.php?id=<?= (int)$id ?>">RAW</a>
        <button class="ch-btn ch-btn-primary" onclick="chCopy('codeblock')">کپی کد</button>
      </div>
    </div>

    <?php if (!empty($item['description'])): ?>
      <div class="ch-card" style="margin:12px 0">
        <h3>توضیحات</h3>
        <div class="ch-meta"><?= nl2br(e($item['description'])) ?></div>
      </div>
    <?php endif; ?>

    <div class="ch-card" style="margin:12px 0">
      <h3>کد</h3>
      <pre id="codeblock" data-ch-lang="<?= e($item['language']) ?>"><?= htmlspecialchars($item['content'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></pre>
    </div>

    <div class="ch-card" style="margin:12px 0">
      <h3>نسخه‌ها</h3>
      <table class="ch-table">
        <thead><tr><th style="width:90px">نسخه</th><th>تغییرات</th><th style="width:220px">تاریخ</th><th style="width:220px">اقدام</th></tr></thead>
        <tbody>
          <?php if (!$versions): ?>
            <tr><td colspan="4" class="ch-meta">نسخه‌ای موجود نیست</td></tr>
          <?php else: foreach($versions as $v): ?>
            <tr>
              <td><?= (int)$v['version_no'] ?></td>
              <td><?= e($v['changelog'] ?: '-') ?></td>
              <td><span class="ch-meta"><?= e($v['created_at']) ?></span></td>
              <td style="text-align:right">
                <a class="ch-btn ch-btn-ghost" href="version_view.php?sid=<?= (int)$id ?>&v=<?= (int)$v['version_no'] ?>">مشاهده</a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <div class="ch-card">
      <h3>فایل‌های پیوست</h3>
      <table class="ch-table" style="margin-top:12px">
        <thead><tr><th>نام فایل</th><th style="width:160px">حجم</th><th style="width:200px">تاریخ</th><th style="width:160px">دانلود</th></tr></thead>
        <tbody>
        <?php if (!$files): ?>
          <tr><td colspan="4" class="ch-meta">فایلی آپلود نشده</td></tr>
        <?php else: foreach($files as $f): ?>
          <tr>
            <td><?= e($f['filename']) ?></td>
            <td><?= number_format((int)$f['size']) ?> B</td>
            <td><span class="ch-meta"><?= e($f['created_at']) ?></span></td>
            <td><a class="ch-btn ch-btn-ghost" href="../public/<?= e($f['filepath']) ?>" download>دانلود</a></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script src="../public/assets/codehub-ultra.js"></script>
<script src="../public/assets/ch-highlight.js"></script>
<?php if (file_exists(__DIR__.'/../footer.php')) include __DIR__.'/../footer.php'; ?>
