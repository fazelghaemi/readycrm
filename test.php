<?php
// فعال کردن نمایش خطاها
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "PHP Version: " . phpversion() . "<br>";
echo "Current Directory: " . __DIR__ . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";

// بررسی فایل‌های مهم
$files = [
    'index.php',
    'install/index.php',
    'public/index.php',
    'private/config.php',
    'private/config.sample.php'
];

echo "<h3>بررسی فایل‌ها:</h3>";
foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "✅ $file موجود است<br>";
    } else {
        echo "❌ $file یافت نشد<br>";
    }
}

// بررسی دسترسی نوشتن
$writableDirs = ['private', 'uploads', 'logs'];
echo "<h3>بررسی دسترسی نوشتن:</h3>";
foreach ($writableDirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_writable($path)) {
        echo "✅ $dir قابل نوشتن است<br>";
    } else {
        echo "❌ $dir قابل نوشتن نیست<br>";
    }
}

phpinfo();
?>
