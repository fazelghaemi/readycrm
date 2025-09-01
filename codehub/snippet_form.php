<?php
require_once __DIR__ . '/../includes/codehub.php';
codehub_require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$item = $id ? codehub_snippet_get($id) : null;
if ($id && !$item) { http_response_code(404); exit('یافت نشد'); }

$csrf = generateCSRFToken();
$langs = codehub_languages();
$errors = []; $ok = false;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) { $errors[]='درخواست نامعتبر.'; }
    $data = [
        'title' => trim($_POST['title'] ?? ''),
        'language' => trim($_POST['language'] ?? 'text'),
        'tags' => trim($_POST['tags'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'content' => (string)($_POST['content'] ?? ''),
        'is_private' => isset($_POST['is_private']) ? 1 : 0,
    ];
    if ($data['title']==='') $errors[]='عنوان را وارد کنید';
    if ($data['content']==='') $errors[]='محتوای کد خالی است';

    if (!$errors) {
        if ($id) {
            $ok = codehub_snippet_update($id, $data);
            if ($ok) { header("Location: snippet_view.php?id=".$id); exit; }
        } else {
            $newId = codehub_snippet_create($data);
            header("Location: snippet_view.php?id=".$newId);
            exit;
        }
    }
}

?><!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<title><?= $id?'ویرایش':'ایجاد' ?> اسنیپت | CodeHub</title>
<link rel="stylesheet" href="../public/assets/codehub.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.css">
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/meta.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/edit/closebrackets.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/edit/matchbrackets.js"></script>
<style>.CodeMirror{border:1px solid #e3e3e3;border-radius:8px;height:500px}</style>
<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<div class="card">
  <div class="codehub-header">
    <i class="fa-solid fa-file-code fa-xl"></i>
    <h2 style="margin:0"><?= $id?'ویرایش':'ایجاد' ?> اسنیپت</h2>
    <div class="codehub-actions">
      <a class="btn btn-ghost" href="snippets.php"><i class="fa fa-arrow-right"></i> لیست</a>
    </div>
  </div>

  <?php if ($errors): ?>
    <div style="background:#ffecec;color:#c62828;border:1px solid #ffcdd2;padding:10px;border-radius:8px;margin-bottom:12px">
      <?= e(implode('، ', $errors)) ?>
    </div>
  <?php endif; ?>

  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
    <div class="form-row">
      <div>
        <label>عنوان</label>
        <input class="input" name="title" value="<?= e($item['title'] ?? '') ?>">
      </div>
      <div>
        <label>زبان</label>
        <select class="select" name="language" id="langSelect">
          <?php foreach($langs as $k=>$v): ?>
            <option value="<?= e($k) ?>" <?= isset($item['language']) && $item['language']===$k?'selected':'' ?>><?= e($v) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="full">
        <label>تگ‌ها (با کاما جدا کنید)</label>
        <input class="input" name="tags" value="<?= e($item['tags'] ?? '') ?>">
      </div>
      <div class="full">
        <label>توضیحات</label>
        <textarea class="input" name="description" style="min-height:120px"><?= e($item['description'] ?? '') ?></textarea>
      </div>
      <div class="full">
        <label>محتوای کد</label>
        <textarea id="codearea" name="content"><?= e($item['content'] ?? '') ?></textarea>
      </div>
      <div>
        <label><input type="checkbox" name="is_private" <?= !empty($item['is_private'])?'checked':'' ?>> خصوصی</label>
      </div>
      <div class="full" style="text-align:left">
        <button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> ذخیره</button>
      </div>
    </div>
  </form>
</div>

<script>
  var editor = CodeMirror.fromTextArea(document.getElementById('codearea'), {
    lineNumbers: true, matchBrackets: true, autoCloseBrackets: true,
    mode: (document.getElementById('langSelect').value || 'text').toLowerCase()
  });
  document.getElementById('langSelect').addEventListener('change', function(){
    editor.setOption('mode', (this.value || 'text').toLowerCase());
  });
</script>
<script src="../public/assets/codehub.js"></script>
</body>
</html>
