<?php
require_once 'config/config.php';
require_once 'includes/auth.php';

// خروج کاربر
logoutUser();

// هدایت به صفحه لاگین
header('Location: login.php');
exit();
?>
