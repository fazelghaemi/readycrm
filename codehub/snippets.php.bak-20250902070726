<?php
require_once __DIR__ . '/../includes/codehub_bootstrap.php';
codehub_require_admin();
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) && !empty($_SESSION['user']['id'])) { $_SESSION['user_id'] = (int)$_SESSION['user']['id']; }

$q    = isset($_GET['q']) ? trim($_GET['q']) : null;
$lang = isset($_GET['lang']) ? trim($_GET['lang']) : null;
$tag  = isset($_GET['tag']) ? trim($_GET['tag']) : null;

$list  = codehub_snippet_list($q, $lang, $tag, 200, 0);
$langs = ['text'=>'متن','php'=>'PHP','js'=>'JavaScript','ts'=>'TypeScript','html'=>'HTML','css'=>'CSS','sql'=>'SQL','json'=>'JSON','bash'=>'Bash','nginx'=>'Nginx','docker'=>'Dockerfile'];

$page_title = 'CodeHub — مدیریت قطعه‌کدها';
$breadcrumbs = [ ['label'=>'CodeHub','url'=>'/codehub/snippets.php','active'=>true] ];

include_once __DIR__ . '/../include/header.php';
?>
<link rel="stylesheet" href="/codehub/assets/codehub.css">
<div class="ch-scope">
  <div class="ch-card">
    <div class="ch-head">
      <h2>CodeHub — مدیریت قطعه‌کدها</h2>
      <div class="ch-actions">
        <a class="ch-btn ch-btn-primary" href="snippet_form.php">اسنیپت جدید</a>
      </div>
    </div>

    <form method="get" class="ch-row">
      <select class="ch-select" name="lang" title="زبان">
        <option value="">زبان (همه)</option>
        <?php foreach($langs as $k=>$v): ?>
          <option value="<?= e($k) ?>" <?= $lang===$k?'selected':'' ?>><?= e($v) ?></option>
        <?php endforeach; ?>
      </select>
      <input class="ch-input" name="q" placeholder="جستجو در عنوان/توضیحات/کد…" value="<?= e($q) ?>">
      <div class="ch-full" style="display:flex;gap:8px">
        <input class="ch-input" name="tag" placeholder="تگ (مثلاً: php یا nginx)" value="<?= e($tag) ?>">
        <button class="ch-btn ch-btn-ghost" type="submit">جستجو</button>
        <a class="ch-btn" href="snippets.php">پاک‌سازی</a>
      </div>
    </form>

    <table class="ch-table">
      <thead>
        <tr>
          <th style="width:70px">#</th>
          <th>عنوان</th>
          <th style="width:120px">زبان</th>
          <th style="width:220px">برچسب‌ها</th>
          <th style="width:160px">ایجاد</th>
          <th style="width:160px">ویرایش</th>
          <th style="width:280px;text-align:right">عملیات</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="7"><div class="ch-empty">چیزی پیدا نشد</div></td></tr>
      <?php else: foreach($list as $row): ?>
        <tr>
          <td><?= (int)$row['id'] ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:10px">
              <div style="font-weight:800"><a href="snippet_view.php?id=<?= (int)$row['id'] ?>" style="color:#0f172a"><?= e($row['title']) ?></a></div>
              <?php if (!empty($row['is_private'])): ?><span class="ch-badge danger">خصوصی</span><?php endif; ?>
            </div>
          </td>
          <td><span class="ch-badge"><?= e($row['language'] ?: 'text') ?></span></td>
          <td><span class="ch-meta"><?= e($row['tags'] ?: '—') ?></span></td>
          <td><span class="ch-meta"><?= e($row['created_at']) ?></span></td>
          <td><span class="ch-meta"><?= e($row['updated_at'] ?: '—') ?></span></td>
          <td style="text-align:right">
            <a class="ch-btn ch-btn-ghost" href="snippet_view.php?id=<?= (int)$row['id'] ?>">مشاهده</a>
            <a class="ch-btn ch-btn-ghost" href="snippet_form.php?id=<?= (int)$row['id'] ?>">ویرایش</a>
            <a class="ch-btn ch-btn-danger" href="snippet_delete.php?id=<?= (int)$row['id'] ?>&csrf=<?= e(generateCSRFToken()) ?>" onclick="return confirm('حذف شود؟')">حذف</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<script src="/codehub/assets/codehub.js"></script>
<?php if (file_exists(__DIR__.'/../footer.php')) include __DIR__.'/../footer.php'; ?>
