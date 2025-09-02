<?php
require_once __DIR__ . '/includes/bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');

echo "== ReadyCRM Diagnostics ==\n\n";

echo "PHP version: " . PHP_VERSION . "\n";
$exts = ['pdo','pdo_mysql','mbstring','openssl','dom','libxml'];
foreach ($exts as $e) {
    echo "ext:$e = " . (extension_loaded($e) ? "OK" : "MISSING") . "\n";
}

echo "\n-- Paths --\n";
echo "RC_ROOT: " . RC_ROOT . "\n";
echo "RS_ICON_DIR: " . (defined('RS_ICON_DIR') ? RS_ICON_DIR : 'n/a') . "\n";
echo "RS_ICON_CACHE_DIR: " . (defined('RS_ICON_CACHE_DIR') ? RS_ICON_CACHE_DIR : 'n/a') . "\n";
if (defined('RS_ICON_DIR')) {
    echo "icons dir exists: " . (is_dir(RS_ICON_DIR) ? "YES" : "NO") . "\n";
}
if (defined('RS_ICON_CACHE_DIR')) {
    echo "cache dir exists: " . (is_dir(RS_ICON_CACHE_DIR) ? "YES" : "NO") . "\n";
    if (is_dir(RS_ICON_CACHE_DIR)) {
        $w = @is_writable(RS_ICON_CACHE_DIR);
        echo "cache writable: " . ($w ? "YES" : "NO") . "\n";
    }
}

echo "\n-- Database --\n";
try {
    if (class_exists('Database')) {
        $db = new Database();
        echo "DB: Connected via Database class.\n";
    } else if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER')) {
        $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ]);
        echo "DB: Connected via PDO.\n";
    } else {
        echo "DB: Config constants not found.\n";
    }
} catch (Throwable $e) {
    echo "DB ERROR: " . $e->getMessage() . "\n";
}

echo "\n-- Icon Render Test --\n";
$out = rs_icon('dashboard', 20, 'me-1');
echo $out ? "rs_icon() ok (HTML length: ".strlen($out).")\n" : "rs_icon() empty\n";

echo "\nDone.\n";
