<?php
declare(strict_types=1);

function h(?string $s): string {
  return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function start_session(): void {
  if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
      'lifetime' => 0,
      'path' => '/',
      'httponly' => true,
      'samesite' => 'Lax',
      'secure' => $secure,
    ]);
    session_start();
  }
}

function csrf_token(): string {
  start_session();
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
  return $_SESSION['csrf'];
}

function csrf_check(): void {
  start_session();
  $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
  if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
    http_response_code(419);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>false,'error'=>'CSRF_INVALID']);
    exit;
  }
}

function json_out(array $data, int $code = 200): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function safe_filename(string $name): string {
  $name = trim($name);
  $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name);
  $name = preg_replace('/[^\pL\pN\-_.()\s]/u', '_', $name);
  $name = preg_replace('/\s+/u', ' ', $name);
  return mb_substr($name, 0, 120);
}
