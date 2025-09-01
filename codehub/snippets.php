<?php
require_once __DIR__ . '/../includes/codehub.php';
codehub_require_admin();

$q    = isset($_GET['q']) ? trim($_GET['q']) : null;
$lang = isset($_GET['lang']) ? trim($_GET['lang']) : null;
$tag  = isset($_GET['tag']) ? trim($_GET['tag']) : null;

$list  = codehub_snippet_list($q, $lang, $tag, 100, 0);
$langs = codehub_languages();
?><!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<title>مدیریت قطعه‌کدها | CodeHub</title>
<link rel="stylesheet" href="../public/assets/codehub.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<div class="card">
  <div class="codehub-header">
    <i class="fa-solid fa-code fa-xl"></i>
    <h2 style="margin:0">CodeHub — مدیریت قطعه‌کدها</h2>
    <div class="codehub-actions">
      <a class="btn btn-primary" href="snippet_form.php"><i class="fa fa-plus"></i> اسنیپت جدید</a>
    </div>
  </div>

  <form method="get" style="margin-bottom:12px" class="form-row">
    <input class="input" name="q" placeholder="جستجو در عنوان/توضیحات/کد…" value="<?= e($q) ?>">
    <select class="select" name="lang">
      <option value="">زبان (همه)</option>
      <?php foreach($langs as $k=>$v): ?>
        <option value="<?= e($k) ?>" <?= $lang===$k?'selected':'' ?>><?= e($v) ?></option>
      <?php endforeach; ?>
    </select>
    <input class="input" name="tag" placeholder="تگ (مثلاً: php یا nginx)" value="<?= e($tag) ?>">
    <button class="btn btn-ghost" type="submit"><i class="fa fa-search"></i> جستجو</button>
  </form>

  <table class="table">
    <thead>
      <tr>
        <th style="width:44px">#</th>
        <th>عنوان</th>
        <th style="width:120px">زبان</th>
        <th style="width:140px">برچسب‌ها</th>
        <th style="width:160px">ایجاد</th>
        <th style="width:160px">ویرایش</th>
        <th style="width:220px">عملیات</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="7" style="text-align:center;color:#666">چیزی پیدا نشد</td></tr>
      <?php else: foreach($list as $row): ?>
        <tr>
          <td><?= (int)$row['id'] ?></td>
          <td>
            <a href="snippet_view.php?id=<?= (int)$row['id'] ?>" style="font-weight:700"><?= e($row['title']) ?></a>
            <?php if (!empty($row['is_private'])): ?>
              <span class="codehub-badge codehub-private">خصوصی</span>
            <?php endif; ?>
          </td>
          <td><?= e($row['language'] ?: 'text') ?></td>
          <td><?= e($row['tags'] ?: '-') ?></td>
          <td><?= e($row['created_at']) ?></td>
          <td><?= e($row['updated_at'] ?: '-') ?></td>
          <td>
            <a class="btn btn-ghost" href="snippet_view.php?id=<?= (int)$row['id'] ?>"><i class="fa fa-eye"></i> مشاهده</a>
            <a class="btn btn-ghost" href="snippet_form.php?id=<?= (int)$row['id'] ?>"><i class="fa fa-pen"></i> ویرایش</a>
            <a class="btn btn-danger" href="snippet_delete.php?id=<?= (int)$row['id'] ?>" onclick="return confirm('حذف شود؟')"><i class="fa fa-trash"></i> حذف</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<script src="../public/assets/codehub.js"></script>
</body>
</html>
