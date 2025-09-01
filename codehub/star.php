<?php
require_once __DIR__ . '/../includes/codehub_bootstrap.php';
codehub_require_admin();
$sid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($sid) codehub_toggle_star($sid);
header('Location: snippet_view.php?id='.$sid); exit;
