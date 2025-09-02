<?php
/**
 * ReadyCRM – Global Bootstrap (safe v2.1)
 * - Single entry point to load config, database, helpers
 * - Robust path resolution (works on Windows/Linux)
 * - Icon pack integration with safe fallbacks
 * - Debugging via ?debug=1 or READY_DEBUG env
 *
 * Usage:
 *   require_once __DIR__ . '/includes/bootstrap.php';
 */

@session_start();

// ---- Paths (resolve absolute root based on this file location)
define('RC_ROOT', realpath(__DIR__ . '/..'));   // /includes -> /
define('RC_INC',  RC_ROOT . DIRECTORY_SEPARATOR . 'includes');
define('RC_CFG',  RC_ROOT . DIRECTORY_SEPARATOR . 'config');
define('RC_PUB',  RC_ROOT . DIRECTORY_SEPARATOR . 'public');

// ---- Debug toggle
$debug = (isset($_GET['debug']) && $_GET['debug'] == '1') || getenv('READY_DEBUG');
if ($debug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

// ---- Require helper with graceful error
function rc_require($file, $label) {
    if (!is_file($file)) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Boot error: Missing {$label} at {$file}\n";
        exit;
    }
    require_once $file;
}

// ---- Load core config & database
rc_require(RC_CFG . DIRECTORY_SEPARATOR . 'config.php',   'config');
rc_require(RC_CFG . DIRECTORY_SEPARATOR . 'database.php', 'database');

// ---- Load common helpers
rc_require(RC_INC . DIRECTORY_SEPARATOR . 'functions.php','functions');
rc_require(RC_INC . DIRECTORY_SEPARATOR . 'auth.php',     'auth');

// ---- Icon Pack integration (with safe fallback)
// Define icon paths if not already defined elsewhere.
if (!defined('RS_ICON_DIR'))       define('RS_ICON_DIR', RC_ROOT . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'icons');
if (!defined('RS_ICON_CACHE_DIR')) define('RS_ICON_CACHE_DIR', RC_PUB  . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'icons');
if (!defined('RS_ICON_BASE_URL'))  define('RS_ICON_BASE_URL', '/assets/icons');

$iconpack_file = RC_INC . DIRECTORY_SEPARATOR . 'iconpack.php';
if (is_file($iconpack_file)) {
    require_once $iconpack_file;
}
if (!function_exists('rs_icon')) {
    // Safe fallback to prevent fatal errors when iconpack is missing/broken
    function rs_icon($name, $size = 20, $class = '', array $attrs = []) {
        $size = (int)$size ?: 20;
        $cls = trim('rs-icon rs-' . $size . ' ' . preg_replace('/[^A-Za-z0-9\-\_\s]/', '', (string)$class));
        $attr_html = '';
        foreach ($attrs as $k=>$v) {
            $k = strtolower($k);
            if (preg_match('/^(role|aria\-[a-z]+|data\-[a-z0-9\-\_]+|title)$/', $k)) {
                $attr_html .= ' ' . $k . '="' . htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') . '"';
            }
        }
        return '<span class="' . htmlspecialchars($cls, ENT_QUOTES, 'UTF-8') . '" style="width:'.$size.'px;height:'.$size.'px;"'.$attr_html.'></span>';
    }
}

// ---- Encoding safety
if (function_exists('mb_internal_encoding')) { @mb_internal_encoding('UTF-8'); }

// ---- Ensure icon cache dir exists
if (!is_dir(RS_ICON_CACHE_DIR)) {
    @mkdir(RS_ICON_CACHE_DIR, 0775, true);
}
