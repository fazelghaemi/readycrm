<?php
/**
 * ReadyCRM v2 - Session Manager
 * 
 * فایل: private/core/Session.php
 * 
 * مدیریت جلسات کاربری:
 * - شروع امن Session
 * - Flash Messages (پیام‌های یکبار مصرف)
 * - محافظت در برابر Session Hijacking
 * - Session Regeneration خودکار
 * - ذخیره و بازیابی داده با Dot Notation
 * - CSRF Token Management
 * 
 * @package ReadyCRM
 * @subpackage Core
 * @version 2.0.0
 * @author ReadyStudio
 */

declare(strict_types=1);

namespace Core;

/**
 * کلاس مدیریت Session
 * 
 * الگوی Singleton برای مدیریت متمرکز جلسات
 * پشتیبانی از Flash Messages
 * محافظت CSRF
 */
class Session
{
    /**
     * نمونه Singleton
     * @var self|null
     */
    private static ?self $instance = null;
    
    /**
     * آیا Session شروع شده؟
     * @var bool
     */
    private bool $started = false;
    
    /**
     * نام Session
     * @var string
     */
    private string $name = 'READYCRM_SESSION';
    
    /**
     * طول عمر Session (ثانیه)
     * @var int
     */
    private int $lifetime = 7200; // 2 ساعت
    
    /**
     * مسیر Cookie
     * @var string
     */
    private string $path = '/';
    
    /**
     * دامنه Cookie
     * @var string
     */
    private string $domain = '';
    
    /**
     * فقط HTTPS
     * @var bool
     */
    private bool $secure = false;
    
    /**
     * فقط HTTP (غیرقابل دسترسی با JS)
     * @var bool
     */
    private bool $httpOnly = true;
    
    /**
     * SameSite Policy
     * @var string
     */
    private string $sameSite = 'Lax';
    
    /**
     * کلید Flash Messages
     * @var string
     */
    private const FLASH_KEY = '_flash';
    
    /**
     * کلید CSRF Token
     * @var string
     */
    private const CSRF_KEY = '_csrf_token';
    
    /**
     * کلید زمان آخرین فعالیت
     * @var string
     */
    private const LAST_ACTIVITY_KEY = '_last_activity';
    
    /**
     * کلید IP کاربر
     * @var string
     */
    private const USER_IP_KEY = '_user_ip';
    
    /**
     * کلید User Agent
     * @var string
     */
    private const USER_AGENT_KEY = '_user_agent';
    
    /**
     * زمان برای Regenerate Session ID (ثانیه)
     * @var int
     */
    private int $regenerateInterval = 1800; // 30 دقیقه
    
    // ============================================
    // Constructor & Singleton
    // ============================================
    
    /**
     * Constructor خصوصی
     * 
     * @param array<string, mixed> $config تنظیمات
     */
    private function __construct(array $config = [])
    {
        // بارگذاری تنظیمات
        $this->loadConfig($config);
    }
    
    /**
     * جلوگیری از Clone
     */
    private function __clone() {}
    
    /**
     * جلوگیری از Unserialize
     * @throws \Exception
     */
    public function __wakeup()
    {
        throw new \Exception('Cannot unserialize Session singleton');
    }
    
    /**
     * دریافت نمونه Singleton
     * 
     * @param array<string, mixed>|null $config
     * @return self
     */
    public static function getInstance(?array $config = null): self
    {
        if (self::$instance === null) {
            // دریافت تنظیمات از Config
            if ($config === null && class_exists('Core\Config')) {
                $configInstance = Config::getInstance();
                $config = [
                    'name' => $configInstance->get('session.name', 'READYCRM_SESSION'),
                    'lifetime' => $configInstance->get('session.lifetime', 7200),
                    'path' => $configInstance->get('session.path', '/'),
                    'domain' => $configInstance->get('session.domain', ''),
                    'secure' => $configInstance->get('session.secure', false),
                    'httpOnly' => $configInstance->get('session.http_only', true),
                    'sameSite' => $configInstance->get('session.same_site', 'Lax'),
                ];
            }
            
            self::$instance = new self($config ?? []);
        }
        
        return self::$instance;
    }
    
    /**
     * بازنشانی نمونه (برای تست)
     */
    public static function resetInstance(): void
    {
        if (self::$instance !== null && self::$instance->started) {
            self::$instance->destroy();
        }
        self::$instance = null;
    }
    
    /**
     * بارگذاری تنظیمات
     * 
     * @param array<string, mixed> $config
     */
    private function loadConfig(array $config): void
    {
        $this->name = $config['name'] ?? $this->name;
        $this->lifetime = (int) ($config['lifetime'] ?? $this->lifetime);
        $this->path = $config['path'] ?? $this->path;
        $this->domain = $config['domain'] ?? $this->domain;
        $this->secure = (bool) ($config['secure'] ?? $this->secure);
        $this->httpOnly = (bool) ($config['httpOnly'] ?? $this->httpOnly);
        $this->sameSite = $config['sameSite'] ?? $this->sameSite;
        
        // تشخیص خودکار HTTPS
        if (!isset($config['secure'])) {
            $this->secure = $this->isHttps();
        }
    }
    
    // ============================================
    // Session Lifecycle
    // ============================================
    
    /**
     * شروع Session
     * 
     * @return bool
     */
    public function start(): bool
    {
        if ($this->started) {
            return true;
        }
        
        // بررسی وضعیت Session
        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            return true;
        }
        
        // تنظیم Cookie Params
        $this->configureCookie();
        
        // تنظیم نام Session
        session_name($this->name);
        
        // تنظیمات امنیتی
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        
        // شروع Session
        if (!session_start()) {
            error_log('[Session] Failed to start session');
            return false;
        }
        
        $this->started = true;
        
        // اعتبارسنجی Session
        if (!$this->validateSession()) {
            $this->destroy();
            $this->start();
            return true;
        }
        
        // بررسی Regeneration
        $this->checkRegeneration();
        
        // پردازش Flash Messages
        $this->ageFlashData();
        
        return true;
    }
    
    /**
     * تنظیم Cookie Parameters
     */
    private function configureCookie(): void
    {
        $cookieParams = [
            'lifetime' => $this->lifetime,
            'path' => $this->path,
            'domain' => $this->domain,
            'secure' => $this->secure,
            'httponly' => $this->httpOnly,
            'samesite' => $this->sameSite,
        ];
        
        session_set_cookie_params($cookieParams);
    }
    
    /**
     * اعتبارسنجی Session
     * 
     * @return bool
     */
    private function validateSession(): bool
    {
        // بررسی وجود اطلاعات امنیتی
        if (!$this->has(self::USER_IP_KEY) || !$this->has(self::USER_AGENT_KEY)) {
            $this->initializeSecurityData();
            return true;
        }
        
        // بررسی IP (اختیاری - ممکن است مشکل ایجاد کند)
        // if ($this->get(self::USER_IP_KEY) !== $this->getClientIp()) {
        //     return false;
        // }
        
        // بررسی User Agent
        if ($this->get(self::USER_AGENT_KEY) !== $this->getUserAgent()) {
            return false;
        }
        
        // بررسی انقضای Session
        $lastActivity = $this->get(self::LAST_ACTIVITY_KEY, 0);
        if ($lastActivity > 0 && (time() - $lastActivity) > $this->lifetime) {
            return false;
        }
        
        // بروزرسانی زمان آخرین فعالیت
        $this->set(self::LAST_ACTIVITY_KEY, time());
        
        return true;
    }
    
    /**
     * مقداردهی اولیه داده‌های امنیتی
     */
    private function initializeSecurityData(): void
    {
        $this->set(self::USER_IP_KEY, $this->getClientIp());
        $this->set(self::USER_AGENT_KEY, $this->getUserAgent());
        $this->set(self::LAST_ACTIVITY_KEY, time());
    }
    
    /**
     * بررسی و اجرای Regeneration
     */
    private function checkRegeneration(): void
    {
        $lastRegenerate = $this->get('_last_regenerate', 0);
        
        if ($lastRegenerate === 0) {
            $this->set('_last_regenerate', time());
            return;
        }
        
        if ((time() - $lastRegenerate) > $this->regenerateInterval) {
            $this->regenerate(true);
        }
    }
    
    /**
     * تولید مجدد Session ID
     * 
     * @param bool $deleteOldSession
     * @return bool
     */
    public function regenerate(bool $deleteOldSession = true): bool
    {
        if (!$this->started) {
            return false;
        }
        
        $result = session_regenerate_id($deleteOldSession);
        
        if ($result) {
            $this->set('_last_regenerate', time());
        }
        
        return $result;
    }
    
    /**
     * نابودی Session
     * 
     * @return bool
     */
    public function destroy(): bool
    {
        if (!$this->started) {
            return true;
        }
        
        // پاک کردن داده‌ها
        $_SESSION = [];
        
        // پاک کردن Cookie
        if (isset($_COOKIE[$this->name])) {
            $params = session_get_cookie_params();
            setcookie(
                $this->name,
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]
            );
        }
        
        // نابودی Session
        $result = session_destroy();
        $this->started = false;
        
        return $result;
    }
    
    /**
     * آیا Session شروع شده؟
     * 
     * @return bool
     */
    public function isStarted(): bool
    {
        return $this->started;
    }
    
    /**
     * دریافت Session ID
     * 
     * @return string
     */
    public function getId(): string
    {
        return session_id() ?: '';
    }
    
    /**
     * تنظیم Session ID
     * 
     * @param string $id
     * @return void
     */
    public function setId(string $id): void
    {
        if (!$this->started) {
            session_id($id);
        }
    }
    
    // ============================================
    // Data Management
    // ============================================
    
    /**
     * تنظیم مقدار
     * 
     * @param string $key کلید (پشتیبانی از Dot Notation)
     * @param mixed $value مقدار
     * @return self
     */
    public function set(string $key, mixed $value): self
    {
        $this->ensureStarted();
        
        $keys = explode('.', $key);
        $data = &$_SESSION;
        
        foreach ($keys as $i => $k) {
            if ($i === count($keys) - 1) {
                $data[$k] = $value;
            } else {
                if (!isset($data[$k]) || !is_array($data[$k])) {
                    $data[$k] = [];
                }
                $data = &$data[$k];
            }
        }
        
        return $this;
    }
    
    /**
     * دریافت مقدار
     * 
     * @param string $key کلید (پشتیبانی از Dot Notation)
     * @param mixed $default مقدار پیش‌فرض
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->ensureStarted();
        
        $keys = explode('.', $key);
        $data = $_SESSION;
        
        foreach ($keys as $k) {
            if (!is_array($data) || !array_key_exists($k, $data)) {
                return $default;
            }
            $data = $data[$k];
        }
        
        return $data;
    }
    
    /**
     * بررسی وجود کلید
     * 
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }
    
    /**
     * حذف کلید
     * 
     * @param string $key
     * @return self
     */
    public function remove(string $key): self
    {
        $this->ensureStarted();
        
        $keys = explode('.', $key);
        $data = &$_SESSION;
        
        foreach ($keys as $i => $k) {
            if ($i === count($keys) - 1) {
                unset($data[$k]);
            } else {
                if (!isset($data[$k]) || !is_array($data[$k])) {
                    return $this;
                }
                $data = &$data[$k];
            }
        }
        
        return $this;
    }
    
    /**
     * پاک کردن همه داده‌ها (به جز موارد سیستمی)
     * 
     * @return self
     */
    public function clear(): self
    {
        $this->ensureStarted();
        
        $preserve = [
            self::CSRF_KEY,
            self::USER_IP_KEY,
            self::USER_AGENT_KEY,
            self::LAST_ACTIVITY_KEY,
            '_last_regenerate',
        ];
        
        $preserved = [];
        foreach ($preserve as $key) {
            if (isset($_SESSION[$key])) {
                $preserved[$key] = $_SESSION[$key];
            }
        }
        
        $_SESSION = $preserved;
        
        return $this;
    }
    
    /**
     * دریافت همه داده‌ها
     * 
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $this->ensureStarted();
        return $_SESSION;
    }
    
    /**
     * دریافت و حذف (Pull)
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->remove($key);
        return $value;
    }
    
    /**
     * اطمینان از شروع Session
     */
    private function ensureStarted(): void
    {
        if (!$this->started) {
            $this->start();
        }
    }
    
    // ============================================
    // Flash Messages
    // ============================================
    
    /**
     * ذخیره Flash Message
     * 
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function flash(string $key, mixed $value): self
    {
        $this->ensureStarted();
        
        // ذخیره داده
        $flash = $this->get(self::FLASH_KEY, []);
        $flash['new'][$key] = $value;
        $this->set(self::FLASH_KEY, $flash);
        
        return $this;
    }
    
    /**
     * دریافت Flash Message
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        $flash = $this->get(self::FLASH_KEY, []);
        
        // ابتدا در old جستجو کن
        if (isset($flash['old'][$key])) {
            return $flash['old'][$key];
        }
        
        // سپس در new
        if (isset($flash['new'][$key])) {
            return $flash['new'][$key];
        }
        
        return $default;
    }
    
    /**
     * بررسی وجود Flash Message
     * 
     * @param string $key
     * @return bool
     */
    public function hasFlash(string $key): bool
    {
        $flash = $this->get(self::FLASH_KEY, []);
        return isset($flash['old'][$key]) || isset($flash['new'][$key]);
    }
    
    /**
     * پیرسازی Flash Data
     * انتقال new به old و حذف old قبلی
     */
    private function ageFlashData(): void
    {
        $flash = $this->get(self::FLASH_KEY, []);
        
        // old را حذف کن
        $flash['old'] = $flash['new'] ?? [];
        $flash['new'] = [];
        
        $this->set(self::FLASH_KEY, $flash);
    }
    
    /**
     * نگه داشتن Flash Messages برای درخواست بعدی
     * 
     * @param array<int, string>|null $keys
     * @return self
     */
    public function reflash(?array $keys = null): self
    {
        $flash = $this->get(self::FLASH_KEY, []);
        
        if ($keys === null) {
            // همه را نگه دار
            $flash['new'] = array_merge($flash['new'] ?? [], $flash['old'] ?? []);
        } else {
            // فقط کلیدهای مشخص شده
            foreach ($keys as $key) {
                if (isset($flash['old'][$key])) {
                    $flash['new'][$key] = $flash['old'][$key];
                }
            }
        }
        
        $this->set(self::FLASH_KEY, $flash);
        
        return $this;
    }
    
    // ============================================
    // Flash Message Helpers (پیام‌های آماده)
    // ============================================
    
    /**
     * پیام موفقیت
     * 
     * @param string $message
     * @return self
     */
    public function success(string $message): self
    {
        return $this->flash('success', $message);
    }
    
    /**
     * پیام خطا
     * 
     * @param string $message
     * @return self
     */
    public function error(string $message): self
    {
        return $this->flash('error', $message);
    }
    
    /**
     * پیام هشدار
     * 
     * @param string $message
     * @return self
     */
    public function warning(string $message): self
    {
        return $this->flash('warning', $message);
    }
    
    /**
     * پیام اطلاعات
     * 
     * @param string $message
     * @return self
     */
    public function info(string $message): self
    {
        return $this->flash('info', $message);
    }
    
    /**
     * دریافت همه پیام‌های Flash
     * 
     * @return array<string, string>
     */
    public function getMessages(): array
    {
        $messages = [];
        $types = ['success', 'error', 'warning', 'info'];
        
        foreach ($types as $type) {
            if ($this->hasFlash($type)) {
                $messages[$type] = $this->getFlash($type);
            }
        }
        
        return $messages;
    }
    
    // ============================================
    // CSRF Protection
    // ============================================
    
    /**
     * دریافت CSRF Token
     * 
     * @return string
     */
    public function getCsrfToken(): string
    {
        $this->ensureStarted();
        
        $token = $this->get(self::CSRF_KEY);
        
        if ($token === null) {
            $token = $this->regenerateCsrfToken();
        }
        
        return $token;
    }
    
    /**
     * تولید مجدد CSRF Token
     * 
     * @return string
     */
    public function regenerateCsrfToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->set(self::CSRF_KEY, $token);
        return $token;
    }
    
    /**
     * اعتبارسنجی CSRF Token
     * 
     * @param string $token
     * @return bool
     */
    public function validateCsrfToken(string $token): bool
    {
        $sessionToken = $this->get(self::CSRF_KEY);
        
        if ($sessionToken === null || $token === '') {
            return false;
        }
        
        return hash_equals($sessionToken, $token);
    }
    
    /**
     * دریافت فیلد HTML برای CSRF
     * 
     * @return string
     */
    public function csrfField(): string
    {
        return sprintf(
            '<input type="hidden" name="_csrf_token" value="%s">',
            htmlspecialchars($this->getCsrfToken(), ENT_QUOTES, 'UTF-8')
        );
    }
    
    /**
     * دریافت Meta Tag برای CSRF (برای AJAX)
     * 
     * @return string
     */
    public function csrfMeta(): string
    {
        return sprintf(
            '<meta name="csrf-token" content="%s">',
            htmlspecialchars($this->getCsrfToken(), ENT_QUOTES, 'UTF-8')
        );
    }
    
    // ============================================
    // Authentication Helpers
    // ============================================
    
    /**
     * ذخیره اطلاعات کاربر لاگین شده
     * 
     * @param int $userId
     * @param array<string, mixed> $userData
     * @return self
     */
    public function login(int $userId, array $userData = []): self
    {
        // Regenerate برای جلوگیری از Session Fixation
        $this->regenerate(true);
        
        $this->set('user_id', $userId);
        $this->set('user', $userData);
        $this->set('logged_in', true);
        $this->set('login_time', time());
        
        return $this;
    }
    
    /**
     * خروج کاربر
     * 
     * @return self
     */
    public function logout(): self
    {
        $this->remove('user_id');
        $this->remove('user');
        $this->remove('logged_in');
        $this->remove('login_time');
        
        // Regenerate بعد از خروج
        $this->regenerate(true);
        
        return $this;
    }
    
    /**
     * آیا کاربر لاگین است؟
     * 
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return $this->get('logged_in', false) === true && $this->get('user_id') !== null;
    }
    
    /**
     * دریافت ID کاربر لاگین شده
     * 
     * @return int|null
     */
    public function getUserId(): ?int
    {
        $userId = $this->get('user_id');
        return $userId !== null ? (int) $userId : null;
    }
    
    /**
     * دریافت اطلاعات کاربر
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function getUser(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->get('user', []);
        }
        
        return $this->get("user.{$key}", $default);
    }
    
    /**
     * بروزرسانی اطلاعات کاربر در Session
     * 
     * @param array<string, mixed> $data
     * @return self
     */
    public function updateUser(array $data): self
    {
        $user = $this->get('user', []);
        $user = array_merge($user, $data);
        $this->set('user', $user);
        
        return $this;
    }
    
    // ============================================
    // Utilities
    // ============================================
    
    /**
     * تشخیص HTTPS
     * 
     * @return bool
     */
    private function isHttps(): bool
    {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }
        
        if (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
            return true;
        }
        
        return false;
    }
    
    /**
     * دریافت IP کاربر
     * 
     * @return string
     */
    private function getClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];
        
        foreach ($headers as $header) {
            if (isset($_SERVER[$header]) && !empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * دریافت User Agent
     * 
     * @return string
     */
    private function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    
    /**
     * ذخیره داده موقت با TTL
     * 
     * @param string $key
     * @param mixed $value
     * @param int $ttl طول عمر (ثانیه)
     * @return self
     */
    public function setTemporary(string $key, mixed $value, int $ttl): self
    {
        $this->set("_temp.{$key}", [
            'value' => $value,
            'expires' => time() + $ttl,
        ]);
        
        return $this;
    }
    
    /**
     * دریافت داده موقت
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getTemporary(string $key, mixed $default = null): mixed
    {
        $data = $this->get("_temp.{$key}");
        
        if ($data === null) {
            return $default;
        }
        
        if ($data['expires'] < time()) {
            $this->remove("_temp.{$key}");
            return $default;
        }
        
        return $data['value'];
    }
    
    /**
     * ذخیره URL قبلی
     * 
     * @return self
     */
    public function savePreviousUrl(): self
    {
        $url = $_SERVER['REQUEST_URI'] ?? '/';
        $this->set('_previous_url', $url);
        return $this;
    }
    
    /**
     * دریافت URL قبلی
     * 
     * @param string $default
     * @return string
     */
    public function getPreviousUrl(string $default = '/'): string
    {
        return $this->get('_previous_url', $default);
    }
    
    /**
     * ذخیره URL هدف (برای redirect بعد از لاگین)
     * 
     * @param string $url
     * @return self
     */
    public function setIntendedUrl(string $url): self
    {
        $this->set('_intended_url', $url);
        return $this;
    }
    
    /**
     * دریافت و حذف URL هدف
     * 
     * @param string $default
     * @return string
     */
    public function pullIntendedUrl(string $default = '/'): string
    {
        return $this->pull('_intended_url', $default);
    }
}

// ============================================
// توابع کمکی سراسری
// ============================================

if (!function_exists('session')) {
    /**
     * دسترسی سریع به Session
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed|Session
     */
    function session(?string $key = null, mixed $default = null): mixed
    {
        $session = Session::getInstance();
        
        if ($key === null) {
            return $session;
        }
        
        return $session->get($key, $default);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * دریافت CSRF Token
     * 
     * @return string
     */
    function csrf_token(): string
    {
        return Session::getInstance()->getCsrfToken();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * دریافت فیلد HTML برای CSRF
     * 
     * @return string
     */
    function csrf_field(): string
    {
        return Session::getInstance()->csrfField();
    }
}

if (!function_exists('old')) {
    /**
     * دریافت داده قدیمی فرم (برای نمایش بعد از خطا)
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function old(string $key, mixed $default = null): mixed
    {
        return Session::getInstance()->getFlash("old.{$key}", $default);
    }
}
