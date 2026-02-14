<?php
require_once __DIR__ . '/_api_bootstrap.php';
$u = require_admin();
require_json_post();

$room_id = (int)($_POST['room_id'] ?? 0);
if ($room_id <= 0) json_out(['ok'=>false,'error'=>'ID_REQUIRED'], 422);

$pdo = db();
$chk = $pdo->prepare("SELECT type FROM rooms WHERE id=? LIMIT 1");
$chk->execute([$room_id]);
$r = $chk->fetch();
if (!$r) json_out(['ok'=>false,'error'=>'ROOM_NOT_FOUND'], 404);
if (!in_array((string)$r['type'], ['group','channel'], true)) json_out(['ok'=>false,'error'=>'CANNOT_DELETE_THIS_TYPE'], 403);

$pdo->prepare("DELETE FROM rooms WHERE id=?")->execute([$room_id]);
json_out(['ok'=>true]);
