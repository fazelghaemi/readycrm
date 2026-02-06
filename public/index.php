<?php
/**
 * Temporary Public Index - CRM V2
 * این فایل موقتی است تا Installer کار کند
 */

// Check if installation is complete
if (!file_exists(__DIR__ . '/../.install.lock')) {
    // Redirect to installer
    header('Location: /readycrm/V2/install/');
    exit();
}

// Check if config exists
if (!file_exists(__DIR__ . '/../private/config.php')) {
    die('خطا: فایل تنظیمات یافت نشد. لطفاً نصب را تکمیل کنید.');
}

// Load configuration
require_once __DIR__ . '/../private/config.php';

// Check if logged in
session_start();

if (!isset($_SESSION['user_id'])) {
    // Redirect to login
    header('Location: /readycrm/V2/public/login.php');
    exit();
}

// Redirect to dashboard
header('Location: /readycrm/V2/public/dashboard.php');
exit();
