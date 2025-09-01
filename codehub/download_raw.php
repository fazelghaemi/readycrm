<?php
require_once __DIR__ . '/../includes/codehub.php';
codehub_require_admin();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$item = $id ? codehub_snippet_get($id) : null;
if (!$item) { http_response_code(404); exit; }
$ext = preg_replace('/[^a-z0-9]+/i','', $item['language'] ?: 'txt');
$fname = 'snippet_'.$id.'.'.$ext;
header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="'.$fname.'"');
echo $item['content'];
