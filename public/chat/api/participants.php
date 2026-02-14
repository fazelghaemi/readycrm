<?php
require_once __DIR__ . '/_api_bootstrap.php';
$u = require_login();

$room_id = (int)($_GET['room_id'] ?? 0);
if ($room_id <= 0) json_out(['ok'=>false,'error'=>'ROOM_REQUIRED'], 422);

$pdo = db();
$r = $pdo->prepare("SELECT id, type FROM rooms WHERE id=? LIMIT 1");
$r->execute([$room_id]);
$room = $r->fetch();
if (!$room) json_out(['ok'=>false,'error'=>'ROOM_NOT_FOUND'], 404);

$rows = $pdo->query("SELECT us.id, us.username,
         COALESCE(up.display_name,'') AS display_name,
         COALESCE(up.bio,'') AS bio,
         COALESCE(up.avatar_path,'') AS avatar_path
  FROM users us
  LEFT JOIN user_profiles up ON up.user_id = us.id
  WHERE us.is_active = 1
  ORDER BY us.id DESC
  LIMIT 500")->fetchAll();

json_out(['ok'=>true,'users'=>$rows]);
