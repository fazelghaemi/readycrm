<?php
require_once __DIR__ . '/../includes/codehub.php';
codehub_require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$item = $id ? codehub_snippet_get($id) : null;
if (!$item) { http_response_code(404); exit('یافت نشد'); }
$versions = codehub_versions($id);
$csrf = generateCSRFToken();

$msg = null;
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['upload_file'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) { $msg='درخواست نامعتبر.'; }
    else if (!isset($_FILES['file']) || $_FILES['file']['error']!==UPLOAD_ERR_OK) { $msg='آپلود ناموفق بود'; }
    else {
        $allowed = ['txt','zip','json','sql','php','js','ts','css','html','md','xml','yml','yaml','ini','conf','log'];
        $name = $_FILES['file']['name'];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext,$allowed)) { $msg='پسوند فایل مجاز نیست'; }
        else {
            $uploaddir = realpath(__DIR__ . '/../public/uploads') ?: (__DIR__ . '/../public/uploads');
            if (!is_dir($uploaddir)) @mkdir($uploaddir,0775,true);
            $dir = $uploaddir . '/codehub';
            if (!is_dir($dir)) @mkdir($dir,0775,true);
            $basename = time().'_'.preg_replace('/[^a-zA-Z0-9\._\-]+/','_', $name);
            $dest = $dir . '/' . $basename;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
                $pdo = codehub_pdo();
                $stmt = $pdo->prepare("INSERT INTO code_snippet_files (snippet_id, filename, filepath, mime, size, uploaded_by, created_at)
                                       VALUES (:sid,:fn,:fp,:mime,:size,:uid,NOW())");
                $stmt->execute([
                    ':sid'=>$id, ':fn'=>$name, ':fp'=>'uploads/codehub/'.$basename,
                    ':mime'=>$_FILES['file']['type'] ?? null, ':size'=> (int)$_FILES['file']['size'],
                    ':uid'=> codehub_user_id(),
                ]);
                $msg='فایل با موفقیت آپلود شد';
            } else { $msg='خطا در ذخیره فایل'; }
        }
    }
}

$pdo = codehub_pdo();
$files = $pdo->prepare("SELECT * FROM code_snippet_files WHERE snippet_id=:id ORDER BY id DESC");
$files->execute([':id'=>$id]);
$files = $files->fetchAll() ?: [];

$isStar = codehub_is_starred($id);
?><!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<title><?= e($item['title']) ?> | CodeHub</title>
<link rel="stylesheet" href="../public/assets/codehub.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/highlight.js@11.9.0/styles/github-dark.min.css">
<script src="https://cdn.jsdelivr.net/npm/highlight.js@11.9.0/lib/highlight.min.js"></script>
<script>document.addEventListener('DOMContentLoaded',()=>{hljs.highlightAll();});</script>
<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<div class="card">
  <div class="codehub-header">
    <i class="fa-solid fa-file-code fa-xl"></i>
    <h2 style="margin:0"><?= e($item['title']) ?></h2>
    <div class="codehub-actions">
      <a class="btn btn-ghost" href="snippets.php"><i class="fa fa-arrow-right"></i> لیست</a>
      <a class="btn btn-ghost" href="snippet_form.php?id=<?= (int)$id ?>"><i class="fa fa-pen"></i> ویرایش</a>
      <a class="btn btn-danger" href="snippet_delete.php?id=<?= (int)$id ?>" onclick="return confirm('حذف شود؟')"><i class="fa fa-trash"></i> حذف</a>
    </div>
  </div>

  <div style="margin-bottom:12px;color:#555">
    <span class="codehub-badge"><?= e($item['language']) ?></span>
    <?php if (!empty($item['is_private'])): ?><span class="codehub-badge codehub-private">خصوصی</span><?php endif; ?>
    <?php if ($item['tags']): ?><span class="codehub-badge"><?= e($item['tags']) ?></span><?php endif; ?>
    <a class="btn btn-ghost" href="star.php?id=<?= (int)$id ?>"><?= $isStar? '⭐ لغو علاقه‌مندی' : '☆ علاقه‌مندی' ?></a>
  </div>

  <?php if (!empty($item['description'])): ?>
  <div class="card" style="margin-bottom:12px">
    <h3>توضیحات</h3>
    <div><?= nl2br(e($item['description'])) ?></div>
  </div>
  <?php endif; ?>

  <div class="card" style="margin-bottom:12px">
    <div style="display:flex;align-items:center;justify-content:space-between">
      <h3 style="margin:0">کد</h3>
      <button class="btn btn-primary" onclick="copyToClipboard('codeblock')"><i class="fa fa-copy"></i> کپی کد</button>
    </div>
    <pre id="codeblock"><code class="<?= e($item['language']) ?>"><?= htmlspecialchars($item['content'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code></pre>
    <div style="margin-top:8px">
      <a class="btn btn-ghost" href="download_raw.php?id=<?= (int)$id ?>"><i class="fa fa-download"></i> دانلود RAW</a>
    </div>
  </div>

  <div class="card" style="margin-bottom:12px">
    <h3>نسخه‌ها</h3>
    <table class="table">
      <thead><tr><th style="width:80px">نسخه</th><th>تغییرات</th><th style="width:180px">تاریخ</th><th style="width:160px">اقدام</th></tr></thead>
      <tbody>
        <?php if (!$versions): ?>
          <tr><td colspan="4" style="text-align:center;color:#666">نسخه‌ای موجود نیست</td></tr>
        <?php else: foreach($versions as $v): ?>
          <tr>
            <td><?= (int)$v['version_no'] ?></td>
            <td><?= e($v['changelog'] ?: '-') ?></td>
            <td><?= e($v['created_at']) ?></td>
            <td><a class="btn btn-ghost" href="version_view.php?sid=<?= (int)$id ?>&v=<?= (int)$v['version_no'] ?>">مشاهده</a>
                <a class="btn btn-primary" href="version_restore.php?sid=<?= (int)$id ?>&v=<?= (int)$v['version_no'] ?>" onclick="return confirm('بازگردانی محتوا به این نسخه؟')">بازگردانی</a></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <div class="card">
    <h3>فایل‌های پیوست</h3>
    <?php if ($msg): ?><div style="margin-bottom:8px"><?= e($msg) ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data" style="margin-bottom:12px">
      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
      <input type="file" name="file" required>
      <button class="btn btn-primary" name="upload_file" value="1"><i class="fa fa-upload"></i> آپلود</button>
    </form>
    <table class="table">
      <thead><tr><th>نام فایل</th><th style="width:140px">حجم</th><th style="width:160px">تاریخ</th><th style="width:160px">دانلود</th></tr></thead>
      <tbody>
        <?php if (!$files): ?>
          <tr><td colspan="4" style="text-align:center;color:#666">فایلی آپلود نشده</td></tr>
        <?php else: foreach($files as $f): ?>
          <tr>
            <td><?= e($f['filename']) ?></td>
            <td><?= number_format((int)$f['size']) ?> B</td>
            <td><?= e($f['created_at']) ?></td>
            <td><a class="btn btn-ghost" href="../public/<?= e($f['filepath']) ?>" download>دانلود</a></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<script src="../public/assets/codehub.js"></script>
</body>
</html>
