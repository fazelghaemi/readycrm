<?php
require_once __DIR__ . '/_api_bootstrap.php';
$u = require_login();

$pdo = db();
$stmt = $pdo->prepare("SELECT us.id, us.username, us.role, us.is_active,
         COALESCE(up.display_name,'') AS display_name,
         COALESCE(up.bio,'') AS bio,
         COALESCE(up.avatar_path,'') AS avatar_path
  FROM users us
  LEFT JOIN user_profiles up ON up.user_id = us.id
  WHERE us.is_active = 1 AND us.id <> ?
  ORDER BY us.id DESC
  LIMIT 500");
$stmt->execute([(int)$u['id']]);
$rows = $stmt->fetchAll();

json_out(['ok'=>true,'users'=>$rows]);
