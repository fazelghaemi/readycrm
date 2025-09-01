<?php
require_once __DIR__ . '/../includes/codehub.php';
codehub_require_admin();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id) { codehub_snippet_delete($id); }
header("Location: snippets.php"); exit;
