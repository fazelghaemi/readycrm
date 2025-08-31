<?php
// public/upload_secure_example.php
declare(strict_types=1);

require_once __DIR__ . '/../includes/security_bootstrap.php';
require_once __DIR__ . '/../includes/upload_guard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $res = ug_handle_upload($_FILES['file']);
    if ($res['ok']) {
        app_log('upload_ok', ['path' => $res['path']]);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'آپلود شد: ' . $res['path'];
        exit;
    }
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'خطا: ' . $res['msg'];
    app_log('upload_error', ['msg' => $res['msg']]);
    exit;
}
?>
<!doctype html>
<html lang="fa" dir="rtl">
<meta charset="utf-8">
<body>
  <form method="post" enctype="multipart/form-data">
    <input type="file" name="file" required>
    <button>آپلود امن</button>
  </form>
</body>
</html>
