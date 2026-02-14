<?php
require_once __DIR__ . '/_api_bootstrap.php';
$u = require_login();
require_json_post();

$cfg = cfg();
$room_id = (int)($_POST['room_id'] ?? 0);
$body = trim((string)($_POST['body'] ?? ''));

$pdo = db();
$r = $pdo->prepare("SELECT id, type, is_readonly FROM rooms WHERE id=? LIMIT 1");
$r->execute([$room_id]);
$room = $r->fetch();
if (!$room) json_out(['ok'=>false,'error'=>'ROOM_NOT_FOUND'], 404);

$is_readonly = ((int)$room['is_readonly'] === 1);
if ($is_readonly && ($u['role'] ?? '') !== 'admin') json_out(['ok'=>false,'error'=>'READONLY'], 403);

$upload_id = null;
if (!empty($_FILES['file']) && is_array($_FILES['file'])) {
  $res = store_upload($_FILES['file'], (int)$u['id'], $room_id);
  if (!$res['ok']) json_out($res, 422);
  $upload_id = (int)$res['upload_id'];
}

if ($body === '' && !$upload_id) json_out(['ok'=>false,'error'=>'EMPTY'], 422);
if ($body !== '' && mb_strlen($body) > (int)$cfg['limits']['message_max_len']) json_out(['ok'=>false,'error'=>'TOO_LONG'], 422);

$pdo->prepare("INSERT INTO messages (room_id, user_id, body, upload_id) VALUES (?, ?, ?, ?)")
    ->execute([$room_id, (int)$u['id'], $body, $upload_id]);

json_out(['ok'=>true,'id'=>(int)$pdo->lastInsertId()]);
