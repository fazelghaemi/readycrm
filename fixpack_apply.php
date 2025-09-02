<?php
/**
 * ReadyCRM FixPack v01c — fixpack_apply.php
 * Fully robust quoting (NOWDOC everywhere) + safe str_replace.
 * Replaces earlier buggy scripts.
 * Run from project root (CLI: php fixpack_apply.php, or open via browser).
 * Creates .bak-YYYYmmddHHMMSS backups before writing.
 */
@ini_set('display_errors', 1);
@error_reporting(E_ALL);
date_default_timezone_set('UTC');

$stamp = date('YmdHis');
$log   = [];
function logmsg($s){ global $log; $log[] = $s; echo $s . PHP_EOL; }

function backup_and_write($path, $content){
    global $stamp;
    // ensure directory
    $dir = dirname($path);
    if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
    if (!file_exists($path)) {
        file_put_contents($path, $content);
        return "CREATED $path";
    }
    $bak = $path . ".bak-$stamp";
    if (!@copy($path, $bak)) { return "ERROR backup $path"; }
    file_put_contents($path, $content);
    return "UPDATED $path (backup: ".basename($bak).")";
}

function smart_regex_replace_in_file($file, $pattern, $replacement, $flags=''){
    if (!file_exists($file)) return "SKIP (not found): $file";
    $src = file_get_contents($file);
    $rx  = $pattern.$flags;
    $new = @preg_replace($rx, $replacement, $src);
    if ($new === null) return "ERROR regex in $file";
    if ($new === $src) return "NOCHANGE $file";
    return backup_and_write($file, $new);
}

function smart_str_replace_in_file($file, $search, $replace){
    if (!file_exists($file)) return "SKIP (not found): $file";
    $src = file_get_contents($file);
    if (strpos($src, $search) === false) return "NOCHANGE $file";
    $new = str_replace($search, $replace, $src);
    return backup_and_write($file, $new);
}

// 0) Ensure storage/logs exists
if (!is_dir('storage/logs')) { @mkdir('storage/logs', 0775, true); }
if (!file_exists('storage/logs/error.log')) { @touch('storage/logs/error.log'); }

// 1) includes/codehub_bootstrap.php — None -> null, secure access function
$f = 'includes/codehub_bootstrap.php';
if (file_exists($f)) {
    logmsg(smart_regex_replace_in_file($f, '/\bNone\b/', 'null', ''));
    // Replace function codehub_require_admin(...) { ... }
    $funcBody = <<<'PHP'
function codehub_require_admin(){
    if (function_exists('isLoggedIn') && !isLoggedIn()) { http_response_code(403); exit('Forbidden'); }
    if (function_exists('hasRole') && !hasRole('admin')) { http_response_code(403); exit('Forbidden'); }
    return true;
}
PHP;
    logmsg(smart_regex_replace_in_file($f, '/function\s+codehub_require_admin\s*\([^)]*\)\s*\{.*?\}/s', $funcBody));
} else {
    logmsg("SKIP (not found): $f");
}

// 2) Fix include path in CodeHub files: ../include/header.php -> ../includes/header.php
if (is_dir('codehub')) {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('codehub', FilesystemIterator::SKIP_DOTS));
    foreach ($rii as $file) {
        if (strtolower($file->getExtension()) !== 'php') continue;
        $p = $file->getPathname();
        logmsg(smart_str_replace_in_file($p, "../include/header.php", "../includes/header.php"));
    }
}

// 3) settings/branding queries: `key`/`value` -> setting_key/setting_value
$targets = ['includes/branding.php','includes/settings.php'];
foreach ($targets as $t) {
    if (!file_exists($t)) { logmsg("SKIP (not found): $t"); continue; }
    logmsg(smart_regex_replace_in_file($t, '/SELECT\s+`?value`?\s+FROM\s+settings\s+WHERE\s+`?key`?\s*=/', 'SELECT setting_value FROM settings WHERE setting_key =', 'i'));
    logmsg(smart_regex_replace_in_file($t, '/INSERT\s+INTO\s+settings\s*\(\s*`?key`?\s*,\s*`?value`?\s*\)/', 'INSERT INTO settings(setting_key,setting_value)', 'i'));
    logmsg(smart_regex_replace_in_file($t, '/ON\s+DUPLICATE\s+KEY\s+UPDATE\s+`?value`?\s*=\s*VALUES\(\s*`?value`?\s*\)/', 'ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)', 'i'));
}

// 4) includes/header.php — add security bootstrap + CSS links if missing (use NOWDOC to avoid escaping)
$header = 'includes/header.php';
if (file_exists($header)) {
    $src = file_get_contents($header);
    $changed = false;
    if (strpos($src, 'security_bootstrap.php') === false) {
        $prefix = <<<'PHP'
<?php
require_once __DIR__ . '/security_bootstrap.php';
?>
PHP;
        $src = $prefix . "
" . $src;
        $changed = true;
    }
    // CSS links via NOWDOC (no escaping problems)
    if (strpos($src, 'public/assets/css/fonts.css') === false) {
        $fontLink = <<<'HTML'
<link rel="stylesheet" href="/public/assets/css/fonts.css?v=1">
HTML;
        $src .= "
" . $fontLink . "
";
        $changed = true;
    }
    if (strpos($src, 'public/assets/readystudio-theme.css') === false) {
        $themeLink = <<<'HTML'
<link rel="stylesheet" href="/public/assets/readystudio-theme.css?v=1">
HTML;
        $src .= $themeLink . "
";
        $changed = true;
    }
    if (strpos($src, 'brand_print_css_vars') === false) {
        $snippet = <<<'PHP'
<?php if (isset($pdo)) { require_once __DIR__ . '/branding.php'; if (function_exists('brand_print_css_vars')) brand_print_css_vars($pdo); } ?>
PHP;
        $src .= $snippet . "
";
        $changed = true;
    }
    if ($changed) {
        logmsg(backup_and_write($header, $src));
    } else {
        logmsg("NOCHANGE $header");
    }
} else {
    logmsg("SKIP (not found): $header");
}

// 5) config/config.php — error_log path (safe replacements, no escaping issues)
$cfg = 'config/config.php';
if (file_exists($cfg)) {
    $src = file_get_contents($cfg);
    $new = $src;
    // Replace '../logs/error.log' with '../storage/logs/error.log' in various quoting styles
    $new = str_replace("__DIR__ . '/../logs/error.log'", "__DIR__ . '/../storage/logs/error.log'", $new);
    $new = str_replace("__DIR__.'/../logs/error.log'", "__DIR__.'/../storage/logs/error.log'", $new);
    $new = str_replace('../logs/error.log', '../storage/logs/error.log', $new);
    $new = str_replace('logs/error.log', 'storage/logs/error.log', $new);
    if ($new !== $src) {
        logmsg(backup_and_write($cfg, $new));
    } else {
        logmsg("NOCHANGE $cfg");
    }
} else {
    logmsg("SKIP (not found): $cfg");
}

// 6) database/schema.sql — strip CREATE DATABASE / USE
$schema = 'database/schema.sql';
if (file_exists($schema)) {
    $src = file_get_contents($schema);
    $lines = explode("\n", $src);
    $out = [];
    foreach ($lines as $ln) {
        $trim = trim($ln);
        if (preg_match('/^CREATE\s+DATABASE/i', $trim)) continue;
        if (preg_match('/^USE\s+/i', $trim)) continue;
        $out[] = $ln;
    }
    $new = implode("\n", $out);
    if ($new !== $src) {
        logmsg(backup_and_write($schema, $new));
    } else {
        logmsg("NOCHANGE $schema");
    }
} else {
    logmsg("SKIP (not found): $schema");
}

// 7) Drop in CSS helper files if not present (fonts/theme/codehub patch)
$map = [
    'public/assets/css/fonts.css' => __DIR__ . '/public/assets/css/fonts.css',
    'public/assets/readystudio-theme.css' => __DIR__ . '/public/assets/readystudio-theme.css',
    'codehub/codehub-fonts-patch.css' => __DIR__ . '/codehub/codehub-fonts-patch.css',
];
foreach ($map as $target => $srcfile) {
    $content = @file_get_contents($srcfile);
    if ($content === false) { logmsg("ERROR read template: $srcfile"); continue; }
    if (!file_exists($target)) {
        $dir = dirname($target);
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        file_put_contents($target, $content);
        logmsg("CREATED $target");
    } else {
        logmsg("KEEP (exists): $target");
    }
}

// 8) Generate migrations file into project if not exists
$migDst = 'migrations/001_settings_unify.sql';
if (!file_exists('migrations')) @mkdir('migrations', 0775, true);
$tplMig = __DIR__ . '/migrations/001_settings_unify.sql';
if (!file_exists($migDst) && file_exists($tplMig)) {
    @copy($tplMig, $migDst);
    logmsg("CREATED $migDst");
} else {
    logmsg(file_exists($migDst) ? "KEEP (exists): $migDst" : "ERROR template missing: $tplMig");
}

// 9) Write a log file
file_put_contents('fixpack_apply.log', implode(PHP_EOL, $log));

echo PHP_EOL . "Done. Review fixpack_apply.log for details." . PHP_EOL;
?>