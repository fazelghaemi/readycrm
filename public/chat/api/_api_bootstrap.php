<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/security.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/uploads.php';

function require_json_post(): void {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok'=>false,'error'=>'METHOD_NOT_ALLOWED'], 405);
  csrf_check();
}
