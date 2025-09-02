<?php
/**
 * ReadyCRM – Dev/Debug Gate (optional)
 * Enable by visiting any page with ?dev=1 from allowed IPs.
 */
$allowedIps = ['127.0.0.1', '::1']; // add your public IP for production if needed
if (isset($_GET['dev']) && $_GET['dev'] == '1' && in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowedIps, true)) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}
