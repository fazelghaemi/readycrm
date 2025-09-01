<?php
require_once __DIR__ . '/../includes/codehub.php';
codehub_require_admin();
$sid = isset($_GET['sid']) ? (int)$_GET['sid'] : 0;
$vno = isset($_GET['v']) ? (int)$_GET['v'] : 0;
$pdo = codehub_pdo();
$stmt = $pdo->prepare("SELECT content FROM code_snippet_versions WHERE snippet_id=:sid AND version_no=:v");
$stmt->execute([':sid'=>$sid, ':v'=>$vno]);
$v = $stmt->fetch();
if ($v) {
    $snip = codehub_snippet_get($sid);
    $snip['content'] = $v['content'];
    codehub_snippet_update($sid, $snip);
}
header("Location: snippet_view.php?id=".$sid); exit;
