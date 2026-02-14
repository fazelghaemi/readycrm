<?php
require_once __DIR__ . '/_api_bootstrap.php';
$u = require_login();
require_json_post();

$other_id = (int)($_POST['other_user_id'] ?? 0);
if ($other_id <= 0 || $other_id === (int)$u['id']) json_out(['ok'=>false,'error'=>'INVALID_USER'], 422);

$pdo = db();
$chk = $pdo->prepare("SELECT id FROM users WHERE id=? AND is_active=1 LIMIT 1");
$chk->execute([$other_id]);
if (!$chk->fetch()) json_out(['ok'=>false,'error'=>'USER_NOT_FOUND'], 404);

$a = min((int)$u['id'], $other_id);
$b = max((int)$u['id'], $other_id);

$ex = $pdo->prepare("SELECT room_id FROM dm_pairs WHERE user_a=? AND user_b=? LIMIT 1");
$ex->execute([$a,$b]);
$row = $ex->fetch();
if ($row) json_out(['ok'=>true,'room_id'=>(int)$row['room_id']]);

$pdo->beginTransaction();
try {
  $pdo->prepare("INSERT INTO rooms (name, type, is_readonly) VALUES ('', 'dm', 0)")->execute([]);
  $room_id = (int)$pdo->lastInsertId();
  $pdo->prepare("INSERT INTO dm_pairs (user_a, user_b, room_id) VALUES (?, ?, ?)")->execute([$a,$b,$room_id]);
  $pdo->commit();
} catch (Throwable $e) {
  $pdo->rollBack();
  throw $e;
}

json_out(['ok'=>true,'room_id'=>$room_id]);
