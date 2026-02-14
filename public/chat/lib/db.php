<?php
declare(strict_types=1);

function cfg(): array {
  static $cfg = null;
  if ($cfg === null) $cfg = require __DIR__ . '/../config/config.php';
  return $cfg;
}

function db(): PDO {
  static $pdo = null;
  if ($pdo === null) {
    $c = cfg()['db'];
    $pdo = new PDO($c['dsn'], $c['user'], $c['pass'], $c['options'] ?? []);
  }
  return $pdo;
}
