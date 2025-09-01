<?php
require_once __DIR__ . '/../includes/codehub_bootstrap.php';
codehub_require_admin();
if (!verifyCSRFToken($_GET['csrf'] ?? '')) { http_response_code(400); exit('Bad Request'); }
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id) codehub_snippet_delete($id);
header('Location: snippets.php'); exit;
