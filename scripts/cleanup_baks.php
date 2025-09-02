<?php
/**
 * scripts/cleanup_baks.php — v01
 * Recursively lists and deletes backup files like *.bak-YYYYmmddHHMMSS and .DS_Store.
 * Dry-run by default. Pass 'apply' (CLI) or ?apply=1 (web) to actually delete.
 */
@ini_set('display_errors', 1);
@error_reporting(E_ALL);

function is_cli(){ return (php_sapi_name() === 'cli'); }
$apply = false;

// Parse flags
if (is_cli()) {
    global $argv;
    $apply = in_array('apply', $argv, true);
} else {
    $apply = !empty($_GET['apply']);
}

$root = getcwd();
$deleted = 0; $listed = 0;
$targets = [];

$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($it as $file) {
    if ($file->isDir()) continue;
    $name = $file->getFilename();
    $path = $file->getPathname();
    // Match *.bak-YYYYmmddHHMMSS or .DS_Store
    if (preg_match('/\.bak-\d{14}$/', $name) || $name === '.DS_Store') {
        $targets[] = $path;
    }
}

if ($apply) {
    foreach ($targets as $p) {
        $listed++;
        if (@unlink($p)) {
            echo "DELETED: $p\n";
            $deleted++;
        } else {
            echo "FAILED:  $p\n";
        }
    }
    echo "\nSummary: deleted=$deleted, total_matched=$listed\n";
} else {
    foreach ($targets as $p) {
        $listed++;
        echo "MATCH:   $p\n";
    }
    echo "\nDry-run (no deletion). Matched files: $listed\n";
    echo "Run with 'apply' (CLI) or ?apply=1 (web) to delete.\n";
}
