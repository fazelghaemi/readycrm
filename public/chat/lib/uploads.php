<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';

function ensure_upload_dir(): string {
  $dir = cfg()['uploads']['dir'];
  if (!is_dir($dir)) @mkdir($dir, 0755, true);
  return $dir;
}

function store_upload(array $file, int $user_id, int $room_id = 0): array {
  $cfg = cfg();

  if (!isset($file['error']) || is_array($file['error'])) return ['ok'=>false,'error'=>'UPLOAD_INVALID'];
  if ($file['error'] !== UPLOAD_ERR_OK) return ['ok'=>false,'error'=>'UPLOAD_ERR_' . $file['error']];

  $max = (int)($cfg['limits']['upload_max_bytes'] ?? 0);
  if ($max > 0 && (int)$file['size'] > $max) return ['ok'=>false,'error'=>'TOO_LARGE'];

  $tmp = (string)$file['tmp_name'];

  $mime = '';
  if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo) {
      $mime = (string)finfo_file($finfo, $tmp);
      finfo_close($finfo);
    }
  }
  if ($mime === '') $mime = (string)($file['type'] ?? 'application/octet-stream');

  $allowed = $cfg['uploads']['allowed_mimes'] ?? [];
  if (!in_array($mime, $allowed, true)) return ['ok'=>false,'error'=>'MIME_NOT_ALLOWED','mime'=>$mime];

  $orig = safe_filename((string)($file['name'] ?? 'file'));
  $ext = pathinfo($orig, PATHINFO_EXTENSION);
  $ext = $ext ? ('.' . preg_replace('/[^a-zA-Z0-9]/', '', $ext)) : '';

  $dir = ensure_upload_dir();
  $stored = bin2hex(random_bytes(16)) . $ext;
  $dest = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $stored;

  if (!move_uploaded_file($tmp, $dest)) return ['ok'=>false,'error'=>'MOVE_FAILED'];

  $pdo = db();
  $stmt = $pdo->prepare("INSERT INTO uploads (user_id, room_id, stored_name, original_name, mime, size_bytes) VALUES (?, ?, ?, ?, ?, ?)");
  $stmt->execute([$user_id, $room_id, $stored, $orig, $mime, (int)$file['size']]);

  return [
    'ok' => true,
    'upload_id' => (int)$pdo->lastInsertId(),
    'stored_name' => $stored,
    'original_name' => $orig,
    'mime' => $mime,
    'size_bytes' => (int)$file['size'],
  ];
}

function upload_public_url(string $stored_name): string {
  $pub = cfg()['uploads']['public_path'] ?? 'uploads';
  return $pub . '/' . rawurlencode($stored_name);
}

function delete_upload(int $upload_id): bool {
  $pdo = db();
  $stmt = $pdo->prepare("SELECT stored_name FROM uploads WHERE id=? LIMIT 1");
  $stmt->execute([$upload_id]);
  $r = $stmt->fetch();
  if (!$r) return false;

  $stored = (string)$r['stored_name'];
  $path = rtrim(cfg()['uploads']['dir'], '/\\') . DIRECTORY_SEPARATOR . $stored;
  if (is_file($path)) @unlink($path);

  $pdo->prepare("DELETE FROM uploads WHERE id=?")->execute([$upload_id]);
  return true;
}
