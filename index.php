<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/auth.php';

// اگر کاربر لاگین نکرده است، به صفحه لاگین هدایت شود
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// اگر کاربر لاگین کرده است، به داشبورد هدایت شود
header('Location: dashboard.php');
exit();
?>
