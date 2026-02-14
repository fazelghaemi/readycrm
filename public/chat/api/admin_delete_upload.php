<?php
require_once __DIR__ . '/_api_bootstrap.php';
$u = require_admin();
require_json_post();

$upload_id = (int)($_POST['upload_id'] ?? 0);
if ($upload_id <= 0) json_out(['ok'=>false,'error'=>'ID_REQUIRED'], 422);

$ok = delete_upload($upload_id);
json_out(['ok'=>true,'deleted'=>$ok]);
