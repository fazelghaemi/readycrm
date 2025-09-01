<?php
require_once __DIR__ . '/../includes/codehub_bootstrap.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$item = codehub_snippet_get($id);
if(!$item){ http_response_code(404); exit('Not Found'); }
header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="snippet-'.$id.'.txt"');
echo $item['content'];
