<?php
/**
 * scripts/inject_security_include.php
 * Usage: php scripts/inject_security_include.php /path/to/project
 */
declare(strict_types=1);

$root = $argv[1] ?? (__DIR__ . '/..');
$root = rtrim($root, DIRECTORY_SEPARATOR);

$includeLine = "require_once __DIR__ . '/includes/security_bootstrap.php';";

$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    if (substr($path, -4) !== '.php') continue;
    if (strpos($path, DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR) !== false) continue;
    if (strpos($path, DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR) !== false) continue;

    $src = @file_get_contents($path);
    if ($src === false) continue;
    if (strpos($src, 'security_bootstrap.php') !== false) continue;

    $pos = strpos($src, '<?php');
    if ($pos === false) continue;

    $insertPos = $pos + 5;
    $before = substr($src, 0, $insertPos);
    $after  = substr($src, $insertPos);
    $new    = $before . PHP_EOL . $includeLine . PHP_EOL . $after;

    @file_put_contents($path, $new);
    echo "Injected: {$path}\n";
}
