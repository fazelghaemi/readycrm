<?php
/**
 * includes/security_bootstrap.php
 * Idempotent bootstrap: اگر سشن فعال باشد، ini_set/cookie_params را دست نمی‌زند.
 * هدرهای امنیتی + HSTS/CSP (Report-Only) + لاگر سبک.
 */

// ---- Session & cookie ----
if (PHP_SAPI !== 'cli') {
    $sessionStatus = session_status();
    $isActive = ($sessionStatus === PHP_SESSION_ACTIVE);

    // فقط قبل از start تنظیمات را ست کن
    if (!$isActive) {
        @ini_set('session.use_strict_mode', '1');
        @ini_set('session.use_only_cookies', '1');
        @ini_set('session.cookie_httponly', '1');

        $isHttps = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
        if ($isHttps) { @ini_set('session.cookie_secure', '1'); }

        if (PHP_VERSION_ID >= 70300) {
            @session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'domain'   => '',
                'secure'   => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
    }

    if ($sessionStatus === PHP_SESSION_NONE) {
        @session_start();
    }
}

// ---- Security headers ----
if (!headers_sent()) {
    if (!defined('SECURITY_HEADERS_ENFORCE')) define('SECURITY_HEADERS_ENFORCE', false);
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

    $isHttps = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
    if ($isHttps) {
        header('Strict-Transport-Security: max-age=15552000; includeSubDomains; preload');
    }

    $csp = "default-src 'self' data: blob:; img-src 'self' data: blob: https:; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https:; font-src 'self' data: https:; connect-src 'self' https:; frame-ancestors 'self';";
    if (SECURITY_HEADERS_ENFORCE) {
        header('Content-Security-Policy: ' . $csp);
    } else {
        header('Content-Security-Policy-Report-Only: ' . $csp);
    }
}

// ---- Logger ----
$logRoot = __DIR__ . '/../storage/logs';
if (!is_dir($logRoot)) { @mkdir($logRoot, 0775, true); }
if (!function_exists('app_log')) {
    function app_log(string $message, array $context = []): void {
        @file_put_contents(
            __DIR__ . '/../storage/logs/app.log',
            json_encode([
                'ts'   => date('c'),
                'ip'   => $_SERVER['REMOTE_ADDR'] ?? null,
                'path' => $_SERVER['REQUEST_URI'] ?? null,
                'msg'  => $message,
                'ctx'  => $context,
            ], JSON_UNESCAPED_UNICODE) . PHP_EOL,
            FILE_APPEND
        );
    }
}
