<?php
/**
 * ReadyCRM Header Refactor Kit (v1.0)
 * Usage (CLI):    php tools/refactor_header.php /absolute/path/to/header.php --apply
 * Usage (Web):    /tools/refactor_header.php?file=/absolute/path/to/header.php&apply=1
 *
 * What it does:
 *  - Ensures bootstrap include is present
 *  - Adds RTL <html lang="fa" dir="rtl"> and core meta tags (if missing)
 *  - Removes Font Awesome includes
 *  - Adds rs-icons.css to <head> (if missing)
 *  - Converts <i class="fa ... fa-ICON ..."> and <span class="fa-..."> to RS <i data-icon="...">
 *  - Backs up original to header.php.bak-YYYYmmddHHMMSS
 *  - Writes <original>.refactored.php (or overwrites if apply=1)
 */

function rr_read($path) {
    $c = @file_get_contents($path);
    if ($c === false) throw new RuntimeException("Cannot read file: $path");
    return $c;
}
function rr_write($path, $content) {
    $ok = @file_put_contents($path, $content);
    if ($ok === false) throw new RuntimeException("Cannot write file: $path");
}
function rr_backup($path) {
    $ts = date('YmdHis');
    $bak = $path . '.bak-' . $ts;
    rr_write($bak, rr_read($path));
    return $bak;
}

function load_map($file) {
    $json = @file_get_contents($file);
    if (!$json) return [];
    $map = json_decode($json, true);
    return is_array($map) ? $map : [];
}

function ensure_bootstrap($code) {
    // If bootstrap include is missing, inject it before DOCTYPE or at very top
    if (!preg_match('/require_once\s+__DIR__\s*\.\s*\'\/includes\/bootstrap\.php\'\s*;/', $code)) {
        $inject = "<?php\nrequire_once __DIR__ . '/includes/bootstrap.php';\n?>\n";
        if (preg_match('/<!doctype|<\!DOCTYPE/i', $code)) {
            $code = preg_replace('/(<!doctype[^>]*>)/i', $inject . "$1", $code, 1);
        } else {
            $code = $inject . $code;
        }
    }
    return $code;
}

function ensure_html_meta($code) {
    // lang/dir on <html>
    if (preg_match('/<html[^>]*>/i', $code)) {
        $code = preg_replace_callback('/<html[^>]*>/i', function($m){
            $tag = $m[0];
            if (!preg_match('/\blang=/i', $tag)) $tag = preg_replace('/<html/i', '<html lang="fa"', $tag, 1);
            if (!preg_match('/\bdir=/i', $tag))  $tag = preg_replace('/<html/i', '<html dir="rtl"', $tag, 1);
            // If both missing, add both
            if (!preg_match('/\blang=/i', $m[0]) && !preg_match('/\bdir=/i', $m[0])) {
                $tag = preg_replace('/<html/i', '<html lang="fa" dir="rtl"', $m[0], 1);
            }
            return $tag;
        }, $code, 1);
    }
    // meta charset & viewport
    if (preg_match('/<head[^>]*>/i', $code)) {
        $needMeta = !preg_match('/<meta\s+charset=/i', $code) || !preg_match('/name=["\']viewport["\']\s+content=/i', $code);
        if ($needMeta) {
            $meta = "\n  <meta charset=\"utf-8\">\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n";
            $code = preg_replace('/(<head[^>]*>)/i', "$1$meta", $code, 1);
        }
    }
    return $code;
}

function remove_fa_includes($code) {
    // Remove Font Awesome CSS/JS includes
    $code = preg_replace('/<link[^>]+font[- ]?awesome[^>]*>/i', '', $code);
    $code = preg_replace('/<script[^>]+font[- ]?awesome[^>]*><\/script>/i', '', $code);
    $code = preg_replace('/<script[^>]+kit\.fontawesome[^>]*><\/script>/i', '', $code);
    return $code;
}

function ensure_rs_icons_css($code) {
    if (!preg_match('/rs-icons\.css/i', $code)) {
        $link = "\n  <link rel=\"stylesheet\" href=\"/public/assets/css/rs-icons.css\">\n";
        $code = preg_replace('/(<\/head>)/i', "$link$1", $code, 1);
    }
    return $code;
}

function convert_fa_markup($code, $map) {
    // Convert <i class="fa ... fa-ICON ..."> to <i data-icon="ICON" data-size="20">
    $code = preg_replace_callback('/<i([^>]*class=["\']([^"\']*)["\'][^>]*)><\/i>/i', function($m) use ($map){
        $full = $m[0];
        $attrs = $m[1];
        $class = $m[2];
        if (stripos($class, 'fa-') === false) return $full;

        $classes = preg_split('/\s+/', trim($class));
        $iconName = null;
        foreach ($classes as $c) {
            if (stripos($c, 'fa-') === 0 && !in_array($c, ['fa','fa-solid','fa-regular','fa-light','fa-brands'])) {
                $key = $c;
                $iconName = isset($map[$key]) ? $map[$key] : preg_replace('/^fa-/', '', $c);
                break;
            }
        }
        if (!$iconName) return $full;

        // Remove all fa* classes
        $attrs = preg_replace('/\sclass=["\'][^"\']*["\']/', '', $attrs);
        return "<i$attrs data-icon=\"$iconName\" data-size=\"20\"></i>";
    }, $code);

    // Convert <span class="fa-ICON ..."></span>
    $code = preg_replace_callback('/<span([^>]*class=["\']([^"\']*)["\'][^>]*)><\/span>/i', function($m) use ($map){
        $full = $m[0];
        $attrs = $m[1];
        $class = $m[2];
        if (stripos($class, 'fa-') === false) return $full;
        $classes = preg_split('/\s+/', trim($class));
        $iconName = null;
        foreach ($classes as $c) {
            if (stripos($c, 'fa-') === 0 && !in_array($c, ['fa','fa-solid','fa-regular','fa-light','fa-brands'])) {
                $key = $c;
                $iconName = isset($map[$key]) ? $map[$key] : preg_replace('/^fa-/', '', $c);
                break;
            }
        }
        if (!$iconName) return $full;
        $attrs = preg_replace('/\sclass=["\'][^"\']*["\']/', '', $attrs);
        return "<i$attrs data-icon=\"$iconName\" data-size=\"20\"></i>";
    }, $code);

    return $code;
}

function refactor_header($path, $apply=false) {
    $map = load_map(__DIR__ . '/../data/fa_map.json');
    $orig = rr_read($path);
    $out = $orig;

    $out = ensure_bootstrap($out);
    $out = ensure_html_meta($out);
    $out = remove_fa_includes($out);
    $out = ensure_rs_icons_css($out);
    $out = convert_fa_markup($out, $map);

    $backup = rr_backup($path);
    $target = $path . '.refactored.php';

    if ($apply) {
        rr_write($path, $out);
        $target = $path;
    } else {
        rr_write($target, $out);
    }

    return [$backup, $target];
}

// --- Entrypoint
$apply = false;
$path = null;

if (php_sapi_name() === 'cli') {
    global $argv;
    if (isset($argv[1])) $path = $argv[1];
    if (isset($argv[2]) && $argv[2] === '--apply') $apply = true;
} else {
    $path = isset($_GET['file']) ? $_GET['file'] : null;
    $apply = isset($_GET['apply']) && $_GET['apply'] == '1';
}

header('Content-Type: text/plain; charset=utf-8');
if (!$path) {
    echo "Usage:\n";
    echo " CLI:  php tools/refactor_header.php /absolute/path/to/header.php --apply\n";
    echo " Web:  /tools/refactor_header.php?file=/absolute/path/to/header.php&apply=1\n";
    exit;
}
$real = realpath($path);
if (!$real) { echo "File not found: $path\n"; exit; }

try {
    list($bak, $out) = refactor_header($real, $apply);
    echo "Backup created: $bak\n";
    echo ($apply ? "File overwritten: $out\n" : "Refactored file: $out\n");
    echo "Done.\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
