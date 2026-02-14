<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';

function current_user(): ?array {
  start_session();
  if (empty($_SESSION['uid'])) return null;
  return [
    'id' => (int)$_SESSION['uid'],
    'username' => (string)($_SESSION['username'] ?? ''),
    'role' => (string)($_SESSION['role'] ?? 'user'),
  ];
}

function require_login(): array {
  $u = current_user();
  if (!$u) { header('Location: index.php'); exit; }
  return $u;
}

function require_admin(): array {
  $u = require_login();
  if (($u['role'] ?? '') !== 'admin') { http_response_code(403); echo "Forbidden"; exit; }
  return $u;
}

function login(string $username, string $password): bool {
  $pdo = db();
  $stmt = $pdo->prepare("SELECT id, username, pass_hash, role, is_active FROM users WHERE username=? LIMIT 1");
  $stmt->execute([$username]);
  $row = $stmt->fetch();
  if (!$row) return false;
  if ((int)$row['is_active'] !== 1) return false;
  if (!password_verify($password, $row['pass_hash'])) return false;

  start_session();
  session_regenerate_id(true);
  $_SESSION['uid'] = (int)$row['id'];
  $_SESSION['username'] = (string)$row['username'];
  $_SESSION['role'] = (string)$row['role'];
  return true;
}

function logout(): void {
  start_session();
  $_SESSION = [];
  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
  }
  session_destroy();
}

function get_profile(int $user_id): array {
  $pdo = db();
  $stmt = $pdo->prepare("SELECT display_name, bio, avatar_path FROM user_profiles WHERE user_id=? LIMIT 1");
  $stmt->execute([$user_id]);
  $p = $stmt->fetch();
  if (!$p) {
    $pdo->prepare("INSERT IGNORE INTO user_profiles (user_id, display_name, bio, avatar_path) VALUES (?, '', '', '')")->execute([$user_id]);
    return ['display_name'=>'', 'bio'=>'', 'avatar_path'=>''];
  }
  return [
    'display_name' => (string)($p['display_name'] ?? ''),
    'bio' => (string)($p['bio'] ?? ''),
    'avatar_path' => (string)($p['avatar_path'] ?? ''),
  ];
}
