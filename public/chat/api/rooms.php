<?php
require_once __DIR__ . '/_api_bootstrap.php';
$u = require_login();
$p = get_profile((int)$u['id']);

$pdo = db();
$rooms = $pdo->query("SELECT id, name, type, is_readonly FROM rooms WHERE type IN ('group','channel') ORDER BY id ASC")->fetchAll();

$dm = $pdo->prepare("SELECT r.id, r.name, r.type, r.is_readonly
  FROM dm_pairs dp
  JOIN rooms r ON r.id = dp.room_id
  WHERE dp.user_a = ? OR dp.user_b = ?
  ORDER BY r.id DESC");
$dm->execute([(int)$u['id'], (int)$u['id']]);
$dm_rooms = $dm->fetchAll();

json_out(['ok'=>true,'rooms'=>$rooms,'dm_rooms'=>$dm_rooms,'me'=>[
  'id'=>$u['id'],
  'username'=>$u['username'],
  'role'=>$u['role'],
  'display_name'=>$p['display_name'],
  'bio'=>$p['bio'],
  'avatar_path'=>$p['avatar_path'],
]]);
