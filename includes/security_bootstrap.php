<?php
/**
 * includes/security_bootstrap.php
 * سشن امن + هدرهای امنیتی + HSTS/CSP + لاگ ساختاریافته
 * نکته: CSP فعلاً Report-Only است تا چیزی نشکند. بعد از تست، SECURITY_HEADERS_ENFORCE را true کن.
 */

declare(strict_types=1);

// ---- Strict Sessions ----
if (PHP_SAPI !== 'cli') {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    $isHttps = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';

    if ($isHttps) {
        ini_set('session.cookie_secure', '1');
    }

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// ---- Security Headers ----
if (!headers_sent()) {
    if (!defined('SECURITY_HEADERS_ENFORCE')) {
        define('SECURITY_HEADERS_ENFORCE', false);
    }

    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

    $isHttps = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
    if ($isHttps) {
        header('Strict-Transport-Security: max-age=15552000; includeSubDomains; preload');
    }

    // نسبتاً محافظه‌کارانه؛ در ابتدا Report-Only
    $csp = "default-src 'self' data: blob:; img-src 'self' data: blob: https:; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https:; font-src 'self' data: https:; connect-src 'self' https:; frame-ancestors 'self';";
    if (SECURITY_HEADERS_ENFORCE) {
        header('Content-Security-Policy: ' . $csp);
    } else {
        header('Content-Security-Policy-Report-Only: ' . $csp);
    }
}

// ---- Logs directory bootstrap ----
$__logRoot = __DIR__ . '/../storage/logs';
if (!is_dir($__logRoot)) {
    @mkdir($__logRoot, 0775, true);
}
$__appLog = $__logRoot . '/app.log';
if (!file_exists($__appLog)) {
    @touch($__appLog);
    @chmod($__appLog, 0664);
}

/**
 * app_log: JSON-lines logger to storage/logs/app.log
 */
if (!function_exists('app_log')) {
    function app_log(string $message, array $context = []): void {
        $line = json_encode([
            'ts'   => date('c'),
            'ip'   => $_SERVER['REMOTE_ADDR'] ?? null,
            'path' => $_SERVER['REQUEST_URI'] ?? null,
            'msg'  => $message,
            'ctx'  => $context,
        ], JSON_UNESCAPED_UNICODE);
        @file_put_contents(__DIR__ . '/../storage/logs/app.log', $line . PHP_EOL, FILE_APPEND);
    }
}
