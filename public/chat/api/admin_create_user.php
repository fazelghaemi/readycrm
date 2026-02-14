<?php
require_once __DIR__ . '/_api_bootstrap.php';
$u = require_admin();
require_json_post();

$username = trim((string)($_POST['username'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$role = (string)($_POST['role'] ?? 'user');

if ($username === '' || $password === '') json_out(['ok'=>false,'error'=>'REQUIRED'], 422);
if (!preg_match('/^[A-Za-z0-9_\.\-]{3,32}$/', $username)) json_out(['ok'=>false,'error'=>'USERNAME_INVALID'], 422);
if (mb_strlen($password) < 8) json_out(['ok'=>false,'error'=>'PASSWORD_TOO_SHORT'], 422);
if (!in_array($role, ['user','admin'], true)) $role = 'user';

$pass_hash = password_hash($password, PASSWORD_DEFAULT);
$pdo = db();

try {
  $stmt = $pdo->prepare("INSERT INTO users (username, pass_hash, role, is_active) VALUES (?, ?, ?, 1)");
  $stmt->execute([$username, $pass_hash, $role]);
  $uid = (int)$pdo->lastInsertId();
  $pdo->prepare("INSERT IGNORE INTO user_profiles (user_id, display_name, bio, avatar_path) VALUES (?, '', '', '')")->execute([$uid]);
} catch (PDOException $e) {
  if (str_contains($e->getMessage(), 'Duplicate')) json_out(['ok'=>false,'error'=>'USERNAME_TAKEN'], 409);
  throw $e;
}

json_out(['ok'=>true,'username'=>$username,'role'=>$role]);
