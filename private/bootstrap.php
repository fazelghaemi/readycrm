<?php
/**
 * ReadyCRM v2 - Bootstrap & Autoloader
 * 
 * فایل: private/bootstrap.php
 * 
 * این فایل مسئول راه‌اندازی اولیه سیستم است:
 * - تعریف ثابت‌های مسیر
 * - Autoloader بدون Composer (PSR-4 دستی)
 * - تنظیمات اولیه PHP
 * - مدیریت خطا
 * - شروع Session امن
 * 
 * @package ReadyCRM
 * @version 2.0.0
 * @author ReadyStudio
 */

declare(strict_types=1);

// جلوگیری از دسترسی مستقیم
if (basename($_SERVER['PHP_SELF']) === 'bootstrap.php') {
    http_response_code(403);
    exit('Direct access not allowed');
}

// ============================================
// بخش ۱: تعریف ثابت‌های مسیر
// ============================================

// مسیر ریشه پروژه (یک سطح بالاتر از private)
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

// مسیرهای اصلی
define('PRIVATE_PATH', ROOT_PATH . 'private' . DIRECTORY_SEPARATOR);
define('PUBLIC_PATH', ROOT_PATH . 'public' . DIRECTORY_SEPARATOR);
define('APP_PATH', ROOT_PATH . 'app' . DIRECTORY_SEPARATOR);
define('ASSETS_PATH', ROOT_PATH . 'assets' . DIRECTORY_SEPARATOR);
define('DATABASE_PATH', ROOT_PATH . 'database' . DIRECTORY_SEPARATOR);
define('STORAGE_PATH', ROOT_PATH . 'storage' . DIRECTORY_SEPARATOR);
define('INSTALL_PATH', ROOT_PATH . 'install' . DIRECTORY_SEPARATOR);

// مسیرهای فرعی private
define('CORE_PATH', PRIVATE_PATH . 'core' . DIRECTORY_SEPARATOR);
define('COMPONENTS_PATH', PRIVATE_PATH . 'components' . DIRECTORY_SEPARATOR);

// مسیرهای فرعی app
define('SERVICES_PATH', APP_PATH . 'Services' . DIRECTORY_SEPARATOR);
define('REPOSITORIES_PATH', APP_PATH . 'Repositories' . DIRECTORY_SEPARATOR);
define('INTEGRATIONS_PATH', APP_PATH . 'Integrations' . DIRECTORY_SEPARATOR);

// مسیرهای storage
define('LOGS_PATH', STORAGE_PATH . 'logs' . DIRECTORY_SEPARATOR);
define('CACHE_PATH', STORAGE_PATH . 'cache' . DIRECTORY_SEPARATOR);
define('UPLOADS_PATH', STORAGE_PATH . 'uploads' . DIRECTORY_SEPARATOR);
define('BACKUPS_PATH', STORAGE_PATH . 'backups' . DIRECTORY_SEPARATOR);

// مسیرهای database
define('MIGRATIONS_PATH', DATABASE_PATH . 'migrations' . DIRECTORY_SEPARATOR);

// ============================================
// بخش ۲: ثابت‌های سیستمی
// ============================================

define('CRM_VERSION', '2.0.0');
define('CRM_NAME', 'ReadyCRM');
define('CRM_CODENAME', 'Phoenix');

// وضعیت نصب
define('INSTALLED_LOCK_FILE', STORAGE_PATH . '.installed');
define('IS_INSTALLED', file_exists(INSTALLED_LOCK_FILE));

// ============================================
// بخش ۳: تنظیمات PHP
// ============================================

// تنظیم خطاها بر اساس محیط
$isDebugMode = false;
if (file_exists(PRIVATE_PATH . 'config.php')) {
    $configContent = file_get_contents(PRIVATE_PATH . 'config.php');
    if (strpos($configContent, "'DEBUG_MODE', true") !== false || 
        strpos($configContent, "'DEBUG_MODE',true") !== false) {
        $isDebugMode = true;
    }
}

if ($isDebugMode) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', LOGS_PATH . 'php_errors.log');
}

// تنظیمات عمومی PHP
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// منطقه زمانی پیش‌فرض
date_default_timezone_set('Asia/Tehran');

// محدودیت‌های امنیتی
ini_set('expose_php', '0');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

// ============================================
// بخش ۴: ایجاد دایرکتوری‌های ضروری
// ============================================

$requiredDirs = [
    STORAGE_PATH,
    LOGS_PATH,
    CACHE_PATH,
    UPLOADS_PATH,
    BACKUPS_PATH,
];

foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// ایجاد فایل .htaccess برای محافظت از storage
$htaccessContent = "Order deny,allow\nDeny from all";
$htaccessFile = STORAGE_PATH . '.htaccess';
if (!file_exists($htaccessFile)) {
    @file_put_contents($htaccessFile, $htaccessContent);
}

// ============================================
// بخش ۵: Autoloader (PSR-4 دستی بدون Composer)
// ============================================

/**
 * کلاس Autoloader
 * 
 * پیاده‌سازی PSR-4 بدون نیاز به Composer
 * قابلیت ثبت چندین namespace با مسیرهای مختلف
 */
class Autoloader
{
    /**
     * نگاشت namespace به مسیر
     * @var array<string, string>
     */
    private static array $namespaces = [];
    
    /**
     * کش کلاس‌های بارگذاری شده
     * @var array<string, string>
     */
    private static array $classCache = [];
    
    /**
     * آیا autoloader ثبت شده؟
     * @var bool
     */
    private static bool $registered = false;
    
    /**
     * ثبت autoloader
     */
    public static function register(): void
    {
        if (self::$registered) {
            return;
        }
        
        spl_autoload_register([self::class, 'loadClass'], true, true);
        self::$registered = true;
        
        // ثبت namespace های پیش‌فرض
        self::addNamespace('App\\', APP_PATH);
        self::addNamespace('Core\\', CORE_PATH);
        self::addNamespace('App\\Services\\', SERVICES_PATH);
        self::addNamespace('App\\Repositories\\', REPOSITORIES_PATH);
        self::addNamespace('App\\Integrations\\', INTEGRATIONS_PATH);
    }
    
    /**
     * افزودن namespace جدید
     * 
     * @param string $namespace پیشوند namespace (با \\ در انتها)
     * @param string $basePath مسیر پایه
     */
    public static function addNamespace(string $namespace, string $basePath): void
    {
        // نرمال‌سازی namespace
        $namespace = trim($namespace, '\\') . '\\';
        
        // نرمال‌سازی مسیر
        $basePath = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        
        self::$namespaces[$namespace] = $basePath;
    }
    
    /**
     * بارگذاری کلاس
     * 
     * @param string $class نام کامل کلاس
     * @return bool
     */
    public static function loadClass(string $class): bool
    {
        // بررسی کش
        if (isset(self::$classCache[$class])) {
            return true;
        }
        
        // جستجو در namespace ها
        foreach (self::$namespaces as $namespace => $basePath) {
            if (strpos($class, $namespace) === 0) {
                $relativeClass = substr($class, strlen($namespace));
                $file = $basePath . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
                
                if (self::requireFile($file)) {
                    self::$classCache[$class] = $file;
                    return true;
                }
            }
        }
        
        // تلاش برای بارگذاری از مسیرهای شناخته شده
        $possiblePaths = [
            CORE_PATH . $class . '.php',
            CORE_PATH . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php',
            APP_PATH . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php',
        ];
        
        foreach ($possiblePaths as $path) {
            if (self::requireFile($path)) {
                self::$classCache[$class] = $path;
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * بارگذاری امن فایل
     * 
     * @param string $file مسیر فایل
     * @return bool
     */
    private static function requireFile(string $file): bool
    {
        if (file_exists($file) && is_readable($file)) {
            require_once $file;
            return true;
        }
        return false;
    }
    
    /**
     * دریافت لیست namespace ها
     * 
     * @return array<string, string>
     */
    public static function getNamespaces(): array
    {
        return self::$namespaces;
    }
    
    /**
     * دریافت کش کلاس‌ها
     * 
     * @return array<string, string>
     */
    public static function getClassCache(): array
    {
        return self::$classCache;
    }
}

// ثبت autoloader
Autoloader::register();

// ============================================
// بخش ۶: Exception Handler سفارشی
// ============================================

/**
 * مدیریت خطاهای نامنتظره
 * 
 * @param Throwable $exception
 */
function handleException(Throwable $exception): void
{
    $errorId = uniqid('ERR_');
    $timestamp = date('Y-m-d H:i:s');
    
    // لاگ خطا
    $logMessage = sprintf(
        "[%s] [%s] %s in %s:%d\nStack trace:\n%s\n\n",
        $timestamp,
        $errorId,
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    );
    
    @file_put_contents(
        LOGS_PATH . 'exceptions.log',
        $logMessage,
        FILE_APPEND | LOCK_EX
    );
    
    // پاسخ به کاربر
    if (!headers_sent()) {
        http_response_code(500);
    }
    
    // در حالت debug جزئیات را نشان بده
    $isDebug = defined('DEBUG_MODE') && DEBUG_MODE === true;
    
    if ($isDebug) {
        echo '<div style="font-family: monospace; background: #fee; padding: 20px; margin: 20px; border: 1px solid #c00; direction: ltr; text-align: left;">';
        echo '<h2 style="color: #c00;">Exception [' . $errorId . ']</h2>';
        echo '<p><strong>Message:</strong> ' . htmlspecialchars($exception->getMessage()) . '</p>';
        echo '<p><strong>File:</strong> ' . htmlspecialchars($exception->getFile()) . ':' . $exception->getLine() . '</p>';
        echo '<h3>Stack Trace:</h3>';
        echo '<pre>' . htmlspecialchars($exception->getTraceAsString()) . '</pre>';
        echo '</div>';
    } else {
        // صفحه خطای کاربرپسند
        echo '<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <title>خطای سیستم</title>
    <style>
        body { font-family: Tahoma, sans-serif; background: #f5f5f5; padding: 50px; text-align: center; }
        .error-box { background: #fff; padding: 40px; border-radius: 10px; max-width: 500px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #e74c3c; margin-bottom: 20px; }
        p { color: #666; line-height: 1.8; }
        .error-id { background: #f8f8f8; padding: 10px; border-radius: 5px; font-family: monospace; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="error-box">
        <h1>⚠️ خطای سیستم</h1>
        <p>متأسفانه خطایی در سیستم رخ داده است.<br>لطفاً دقایقی دیگر مجدداً تلاش کنید.</p>
        <div class="error-id">کد خطا: ' . $errorId . '</div>
    </div>
</body>
</html>';
    }
    
    exit(1);
}

// ثبت exception handler
set_exception_handler('handleException');

// ============================================
// بخش ۷: Error Handler سفارشی
// ============================================

/**
 * تبدیل خطاهای PHP به Exception
 * 
 * @param int $severity
 * @param string $message
 * @param string $file
 * @param int $line
 * @return bool
 * @throws ErrorException
 */
function handleError(int $severity, string $message, string $file, int $line): bool
{
    // اگر error suppressed شده (@)، نادیده بگیر
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    throw new ErrorException($message, 0, $severity, $file, $line);
}

// ثبت error handler
set_error_handler('handleError');

// ============================================
// بخش ۸: Shutdown Handler
// ============================================

/**
 * مدیریت خطاهای fatal در shutdown
 */
function handleShutdown(): void
{
    $error = error_get_last();
    
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $errorId = uniqid('FATAL_');
        $timestamp = date('Y-m-d H:i:s');
        
        $logMessage = sprintf(
            "[%s] [%s] Fatal Error: %s in %s:%d\n\n",
            $timestamp,
            $errorId,
            $error['message'],
            $error['file'],
            $error['line']
        );
        
        @file_put_contents(
            LOGS_PATH . 'fatal.log',
            $logMessage,
            FILE_APPEND | LOCK_EX
        );
    }
}

// ثبت shutdown handler
register_shutdown_function('handleShutdown');

// ============================================
// بخش ۹: Session امن
// ============================================

/**
 * شروع Session با تنظیمات امن
 * 
 * @param array $options تنظیمات اضافی
 */
function startSecureSession(array $options = []): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    
    // تنظیمات پیش‌فرض امن
    $defaults = [
        'name' => 'CRMSSID',
        'cookie_lifetime' => 0,
        'cookie_path' => '/',
        'cookie_domain' => '',
        'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
        'use_only_cookies' => true,
        'gc_maxlifetime' => 7200, // 2 ساعت
    ];
    
    $settings = array_merge($defaults, $options);
    
    // اعمال تنظیمات
    session_name($settings['name']);
    
    session_set_cookie_params([
        'lifetime' => $settings['cookie_lifetime'],
        'path' => $settings['cookie_path'],
        'domain' => $settings['cookie_domain'],
        'secure' => $settings['cookie_secure'],
        'httponly' => $settings['cookie_httponly'],
        'samesite' => $settings['cookie_samesite'],
    ]);
    
    ini_set('session.use_strict_mode', $settings['use_strict_mode'] ? '1' : '0');
    ini_set('session.use_only_cookies', $settings['use_only_cookies'] ? '1' : '0');
    ini_set('session.gc_maxlifetime', (string) $settings['gc_maxlifetime']);
    
    // شروع session
    session_start();
    
    // بازسازی session ID اگر قدیمی است
    if (!isset($_SESSION['_created'])) {
        $_SESSION['_created'] = time();
    } elseif (time() - $_SESSION['_created'] > 1800) {
        // بازسازی هر 30 دقیقه
        session_regenerate_id(true);
        $_SESSION['_created'] = time();
    }
    
    // ذخیره IP و User Agent برای تشخیص hijacking
    $currentFingerprint = md5(
        ($_SERVER['REMOTE_ADDR'] ?? '') . 
        ($_SERVER['HTTP_USER_AGENT'] ?? '')
    );
    
    if (!isset($_SESSION['_fingerprint'])) {
        $_SESSION['_fingerprint'] = $currentFingerprint;
    } elseif ($_SESSION['_fingerprint'] !== $currentFingerprint) {
        // احتمال session hijacking
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['_created'] = time();
        $_SESSION['_fingerprint'] = $currentFingerprint;
    }
}

// ============================================
// بخش ۱۰: Helper Functions پایه
// ============================================

/**
 * دریافت مقدار از آرایه با پیش‌فرض
 * 
 * @param array $array
 * @param string|int $key
 * @param mixed $default
 * @return mixed
 */
function array_get(array $array, string|int $key, mixed $default = null): mixed
{
    return $array[$key] ?? $default;
}

/**
 * بررسی اجرا در محیط CLI
 * 
 * @return bool
 */
function isCli(): bool
{
    return php_sapi_name() === 'cli' || defined('STDIN');
}

/**
 * بررسی درخواست AJAX
 * 
 * @return bool
 */
function isAjax(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * تولید UUID v4
 * 
 * @return string
 */
function generateUuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * دریافت URL پایه سایت
 * 
 * @return string
 */
function getBaseUrl(): string
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    
    // حذف /public یا /install از انتها
    $baseDir = preg_replace('#/(public|install)$#', '', $scriptDir);
    
    return rtrim($protocol . '://' . $host . $baseDir, '/');
}

/**
 * ریدایرکت امن
 * 
 * @param string $url
 * @param int $statusCode
 */
function redirect(string $url, int $statusCode = 302): never
{
    if (!headers_sent()) {
        header('Location: ' . $url, true, $statusCode);
    } else {
        echo '<script>window.location.href="' . htmlspecialchars($url) . '";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url) . '"></noscript>';
    }
    exit;
}

/**
 * پاسخ JSON
 * 
 * @param mixed $data
 * @param int $statusCode
 */
function jsonResponse(mixed $data, int $statusCode = 200): never
{
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
    }
    
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * بررسی نصب بودن سیستم
 * در صورت عدم نصب، ریدایرکت به installer
 */
function checkInstallation(): void
{
    if (!IS_INSTALLED) {
        $currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
        
        // اگر در installer هستیم، اجازه بده
        if (strpos($currentScript, '/install/') !== false) {
            return;
        }
        
        // ریدایرکت به installer
        $baseUrl = getBaseUrl();
        redirect($baseUrl . '/install/');
    }
}

/**
 * بارگذاری فایل config
 * 
 * @return bool
 */
function loadConfig(): bool
{
    $configFile = PRIVATE_PATH . 'config.php';
    
    if (file_exists($configFile)) {
        require_once $configFile;
        return true;
    }
    
    return false;
}

// ============================================
// بخش ۱۱: بارگذاری اولیه
// ============================================

// بارگذاری config اگر وجود دارد
if (IS_INSTALLED) {
    loadConfig();
}

// ============================================
// پایان Bootstrap
// ============================================
