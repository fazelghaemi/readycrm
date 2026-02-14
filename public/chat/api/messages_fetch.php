<?php
require_once __DIR__ . '/_api_bootstrap.php';
$u = require_login();

$room_id = (int)($_GET['room_id'] ?? 0);
$after_id = (int)($_GET['after_id'] ?? 0);
$limit = (int)($_GET['limit'] ?? 50);
if ($limit < 1 || $limit > 200) $limit = 50;

$pdo = db();
$stmt = $pdo->prepare("SELECT m.id, m.room_id, m.body, m.created_at,
         u.username,
         COALESCE(up.display_name,'') AS display_name,
         COALESCE(up.avatar_path,'') AS avatar_path,
         up.bio AS bio,
         upx.id AS upload_id, upx.stored_name, upx.original_name, upx.mime, upx.size_bytes
  FROM messages m
  JOIN users u ON u.id = m.user_id
  LEFT JOIN user_profiles up ON up.user_id = u.id
  LEFT JOIN uploads upx ON upx.id = m.upload_id
  WHERE m.room_id = ? AND m.id > ?
  ORDER BY m.id ASC
  LIMIT $limit");
$stmt->execute([$room_id, $after_id]);
$rows = $stmt->fetchAll();

foreach ($rows as &$r) {
  if (!empty($r['stored_name'])) $r['file_url'] = upload_public_url((string)$r['stored_name']);
}

json_out(['ok'=>true,'messages'=>$rows]);
