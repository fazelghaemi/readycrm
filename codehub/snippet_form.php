<?php
require_once __DIR__ . '/../includes/codehub.php';
codehub_require_admin();

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) && !empty($_SESSION['user']['id'])) { $_SESSION['user_id'] = (int)$_SESSION['user']['id']; }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$item = $id ? codehub_snippet_get($id) : null;
if ($id && !$item) { http_response_code(404); exit('یافت نشد'); }

$csrf = generateCSRFToken();
$langs = codehub_languages();
$errors = [];

// لاگ برای دیباگ
$logdir = __DIR__ . '/../storage/logs';
if (!is_dir($logdir)) @mkdir($logdir, 0775, true);
@ini_set('log_errors','1'); @ini_set('error_log', $logdir.'/codehub.log');

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) { $errors[]='درخواست نامعتبر.'; }
    $data = [
        'title'       => trim($_POST['title'] ?? ''),
        'language'    => trim($_POST['language'] ?? 'text'),
        'tags'        => trim($_POST['tags'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'content'     => (string)($_POST['content'] ?? ''),
        'is_private'  => isset($_POST['is_private']) ? 1 : 0,
    ];
    if ($data['title']==='')   $errors[]='عنوان را وارد کنید';
    if ($data['content']==='') $errors[]='محتوای کد خالی است';

    if (!$errors) {
        try {
            if ($id) { codehub_snippet_update($id, $data); $newId=$id; }
            else     { $newId = codehub_snippet_create($data); }
            header('Location: snippet_view.php?id='.$newId); exit;
        } catch (PDOException $e) {
            if ((int)$e->errorInfo[1]===1452) {
                $errors[]='خطای احراز هویت در لاگ فعالیت‌ها: شناسه کاربر معتبر نیست. لطفاً وارد حساب ادمین شوید یا مقدار user_id سشن را بررسی کنید.';
            } else { $errors[]='خطا در ذخیره‌سازی: '.$e->getMessage(); }
            error_log('[SaveError] '.$e->getMessage());
        } catch (Throwable $t) {
            $errors[]='خطای غیرمنتظره: '.$t->getMessage();
            error_log('[SaveError] '.$t->getMessage());
        }
    }
}

$page_title = ($id?'ویرایش':'ایجاد') . ' اسنیپت | CodeHub';
$breadcrumbs = [ ['label'=>'CodeHub','url'=>'/codehub/snippets.php','active'=>false], ['label'=>$id?'ویرایش اسنیپت':'اسنیپت جدید','active'=>true] ];

include_once __DIR__ . '/../include/header.php';
?>
<link rel="stylesheet" href="../public/assets/codehub-ultra.css">
<link rel="stylesheet" href="../public/assets/ch-editor.css">

<div class="ch-wrap">
  <div class="ch-card">
    <div class="ch-head">
      <h2><?= $id?'ویرایش':'ایجاد' ?> اسنیپت</h2>
      <div class="ch-actions">
        <a class="ch-btn" style="border:1px solid var(--ch-border)" href="snippets.php">لیست</a>
        <?php if ($id): ?><a class="ch-btn ch-btn-danger" href="snippet_delete.php?id=<?= (int)$id ?>" onclick="return confirm('حذف شود؟')">حذف</a><?php endif; ?>
      </div>
    </div>

    <?php if ($errors): ?>
      <div class="ch-card" style="border-color:#fecaca;background:#fff1f2;color:#b91c1c"><?= e(implode('، ', $errors)) ?></div>
    <?php endif; ?>

    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
      <div class="ch-row">
        <div class="ch-full">
          <label class="mb-2">عنوان</label>
          <input class="ch-input" name="title" value="<?= e($item['title'] ?? '') ?>" placeholder="مثلاً: پیکربندی Nginx برای PHP-FPM">
        </div>
        <div>
          <label class="mb-2">زبان</label>
          <select class="ch-select" name="language">
            <?php foreach($langs as $k=>$v): ?>
              <option value="<?= e($k) ?>" <?= isset($item['language']) && $item['language']===$k?'selected':'' ?>><?= e($v) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="mb-2">تگ‌ها</label>
          <input class="ch-input" name="tags" value="<?= e($item['tags'] ?? '') ?>" placeholder="php, nginx, docker">
        </div>
        <div>
          <label class="mb-2">خصوصی</label>
          <div><label><input type="checkbox" name="is_private" <?= !empty($item['is_private'])?'checked':'' ?>> فقط برای ادمین</label></div>
        </div>
        <div class="ch-full">
          <label class="mb-2">توضیحات</label>
          <textarea class="ch-input" name="description" style="min-height:120px" placeholder="توضیح کوتاه"><?= e($item['description'] ?? '') ?></textarea>
        </div>
        <div class="ch-full">
          <label class="mb-2">محتوای کد</label>
          <div class="ch-editor">
            <div class="gutter"></div>
            <textarea id="codearea" name="content"><?= e($item['content'] ?? '') ?></textarea>
          </div>
        </div>
        <div class="ch-full" style="text-align:left">
          <button class="ch-btn ch-btn-primary" type="submit">ذخیره</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script src="../public/assets/ch-editor.js"></script>
<script> CHEditor.attachEditor('codearea'); </script>
<?php if (file_exists(__DIR__.'/../footer.php')) include __DIR__.'/../footer.php'; ?>
