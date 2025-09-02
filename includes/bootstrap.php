<?php
/**
 * ReadyCRM – Global Bootstrap (safe v2)
 * یک نقطه ورود واحد و ایمن برای همه صفحات
 * - بارگذاری امن کانفیگ/دیتابیس/هلپرها
 * - هندل نبودن آیکون‌پک با فالبک
 * - دیباگ اختیاری از طریق ?debug=1
 */

@session_start();

// ---- Paths
define('RC_ROOT', realpath(__DIR__ . '/..'));             // /includes -> / (root)
define('RC_INC',  RC_ROOT . '/includes');
define('RC_CFG',  RC_ROOT . '/config');
define('RC_PUB',  RC_ROOT . '/public');

// ---- Debug toggle (?debug=1 یا READY_DEBUG env)
$debug = (isset($_GET['debug']) && $_GET['debug'] == '1') || getenv('READY_DEBUG');
if ($debug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

// ---- Basic requirements (with graceful errors)
function rc_require($file, $label) {
    if (!is_file($file)) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Boot error: Missing {$label} at {$file}\n";
        exit;
    }
    require_once $file;
}

// 1) Core config & DB
rc_require(RC_CFG . '/config.php',   'config');
rc_require(RC_CFG . '/database.php', 'database');

// 2) Helpers
rc_require(RC_INC . '/functions.php','functions');
rc_require(RC_INC . '/auth.php',     'auth');

// 3) Icon Pack (with fallback)
//    اگر فایل نبود یا مسیرها آماده نبودند، فالبک تعریف می‌شود تا سایت نخوابد.
if (is_file(RC_INC . '/iconpack.php')) {
    // مسیرهای آیکون‌پک را اگر قبلاً تعریف نشده‌اند، اینجا تعریف کن
    if (!defined('RS_ICON_DIR'))       define('RS_ICON_DIR', RC_ROOT . '/assets/icons');
    if (!defined('RS_ICON_CACHE_DIR')) define('RS_ICON_CACHE_DIR', RC_PUB . '/assets/cache/icons');
    if (!defined('RS_ICON_BASE_URL'))  define('RS_ICON_BASE_URL', '/assets/icons');
    require_once RC_INC . '/iconpack.php';
    if (!function_exists('rs_icon')) {
        // اگر فایل هست ولی تابع نبود (خطای سینتکس احتمالی)، یک فالبک امن
        function rs_icon($name,$size=20,$class='',$attrs=[]){ return '<span class="rs-icon rs-'.$size.'"></span>'; }
    }
} else {
    // فالبک اگر iconpack.php فعلاً در دسترس نیست
    if (!function_exists('rs_icon')) {
        function rs_icon($name,$size=20,$class='',$attrs=[]){ return '<span class="rs-icon rs-'.$size.'"></span>'; }
    }
}

// 4) Default internal encoding
if (function_exists('mb_internal_encoding')) { @mb_internal_encoding('UTF-8'); }

// 5) Ensure cache dir exists (silently)
if (defined('RS_ICON_CACHE_DIR') && !is_dir(RS_ICON_CACHE_DIR)) {
    @mkdir(RS_ICON_CACHE_DIR, 0775, true);
}
