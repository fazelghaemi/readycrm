<?php
/**
 * scripts/rollback_from_backup.php — v01
 * Restore a single file from the latest backup (*.bak-YYYYmmddHHMMSS).
 * Dry-run by default. Pass 'apply' (CLI) or ?apply=1 (web) to perform changes.
 *
 * Examples:
 *   CLI: php scripts/rollback_from_backup.php file=includes/header.php
 *   CLI apply: php scripts/rollback_from_backup.php file=includes/header.php apply
 *   Web: /scripts/rollback_from_backup.php?file=includes/header.php
 *   Web apply: /scripts/rollback_from_backup.php?file=includes/header.php&apply=1
 */
@ini_set('display_errors', 1);
@error_reporting(E_ALL);

function is_cli(){ return (php_sapi_name() === 'cli'); }

$apply = false;
$fileParam = null;

if (is_cli()) {
    global $argv;
    foreach ($argv as $arg) {
        if (strpos($arg, 'file=') === 0) { $fileParam = substr($arg, 5); }
        if ($arg === 'apply') { $apply = true; }
    }
} else {
    $fileParam = isset($_GET['file']) ? $_GET['file'] : null;
    $apply = !empty($_GET['apply']);
}

if (!$fileParam) {
    echo "ERROR: missing 'file' parameter (relative path, e.g., includes/header.php)\n";
    exit(1);
}

$original = rtrim($fileParam);
if (!file_exists($original)) {
    echo "ERROR: original file not found: $original\n";
    // Still try to detect backups
}

$found = [];
$globs = glob($original . '.bak-*');
if ($globs) {
    foreach ($globs as $p) {
        if (preg_match('/\.bak-(\d{14})$/', $p, $m)) {
            $found[] = ['path'=>$p, 'ts'=>$m[1]];
        }
    }
}

if (empty($found)) {
    echo "No backups found for $original\n";
    exit(0);
}

// Sort by timestamp desc
usort($found, function($a,$b){ return strcmp($b['ts'], $a['ts']); });
$latest = $found[0];

echo "Latest backup: {$latest['path']} (ts={$latest['ts']})\n";
if (!$apply) {
    echo "Dry-run: nothing changed. Re-run with 'apply' (CLI) or ?apply=1 (web) to restore.\n";
    exit(0);
}

// Make safety copy of current (if exists)
if (file_exists($original)) {
    $safety = $original . '.rollback-' . date('YmdHis');
    if (!@copy($original, $safety)) {
        echo "WARN: could not create safety copy: $safety\n";
    } else {
        echo "Safety copy created: $safety\n";
    }
}

// Restore
if (@copy($latest['path'], $original)) {
    echo "RESTORED: $original from {$latest['path']}\n";
} else {
    echo "ERROR: could not restore $original\n";
    exit(1);
}

echo "Done.\n";
