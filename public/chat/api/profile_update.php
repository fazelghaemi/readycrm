<?php
require_once __DIR__ . '/_api_bootstrap.php';
$u = require_login();
require_json_post();

$display_name = trim((string)($_POST['display_name'] ?? ''));
$bio = trim((string)($_POST['bio'] ?? ''));

if (mb_strlen($display_name) > 64) json_out(['ok'=>false,'error'=>'NAME_TOO_LONG'], 422);
if (mb_strlen($bio) > 160) json_out(['ok'=>false,'error'=>'BIO_TOO_LONG'], 422);

$avatar_path = null;
if (!empty($_FILES['avatar']) && is_array($_FILES['avatar'])) {
  $res = store_upload($_FILES['avatar'], (int)$u['id'], 0);
  if (!$res['ok']) json_out($res, 422);
  $avatar_path = upload_public_url((string)$res['stored_name']);
}

$pdo = db();
$pdo->prepare("INSERT IGNORE INTO user_profiles (user_id, display_name, bio, avatar_path) VALUES (?, '', '', '')")
    ->execute([(int)$u['id']]);

if ($avatar_path !== null) {
  $pdo->prepare("UPDATE user_profiles SET display_name=?, bio=?, avatar_path=? WHERE user_id=?")
      ->execute([$display_name, $bio, $avatar_path, (int)$u['id']]);
} else {
  $pdo->prepare("UPDATE user_profiles SET display_name=?, bio=? WHERE user_id=?")
      ->execute([$display_name, $bio, (int)$u['id']]);
}

json_out(['ok'=>true, 'avatar_path'=>$avatar_path]);
