<?php
require_once __DIR__ . '/../includes/codehub.php';
codehub_require_admin();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id) {
    if (codehub_is_starred($id)) codehub_unstar($id);
    else codehub_star($id);
}
header("Location: snippet_view.php?id=".$id); exit;
