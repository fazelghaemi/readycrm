<?php
require_once __DIR__ . '/_api_bootstrap.php';
$u = require_admin();
require_json_post();

$name = trim((string)($_POST['name'] ?? ''));
$type = (string)($_POST['type'] ?? 'group');
$is_readonly = (int)($_POST['is_readonly'] ?? 0);

if ($name === '') json_out(['ok'=>false,'error'=>'NAME_REQUIRED'], 422);
if (!in_array($type, ['group','channel'], true)) $type = 'group';
$is_readonly = $is_readonly ? 1 : 0;

$pdo = db();
$pdo->prepare("INSERT INTO rooms (name, type, is_readonly) VALUES (?, ?, ?)")->execute([$name, $type, $is_readonly]);

json_out(['ok'=>true,'id'=>(int)$pdo->lastInsertId()]);
