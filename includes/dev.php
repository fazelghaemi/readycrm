<?php
/**
 * ReadyCRM – Dev/Debug Gate
 * افزودنی اختیاری: اگر ?dev=1 و IP مجاز بود، دیباگ روشن می‌شود.
 */
$allowedIps = ['127.0.0.1', '::1']; // اگر روی سرور هستی IP خودت را هم اضافه کن
if (isset($_GET['dev']) && $_GET['dev'] == '1' && in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowedIps, true)) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}
