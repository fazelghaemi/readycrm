<?php
/**
 * ReadyCRM v2 - Config Manager
 * 
 * فایل: private/core/Config.php
 * 
 * مدیریت پیکربندی سیستم:
 * - بارگذاری تنظیمات از فایل config.php
 * - بارگذاری تنظیمات از دیتابیس
 * - کش تنظیمات برای عملکرد بهتر
 * - مقادیر پیش‌فرض امن
 * - دسترسی با نقطه‌گذاری (dot notation)
 * 
 * @package ReadyCRM
 * @subpackage Core
 * @version 2.0.0
 * @author ReadyStudio
 */

declare(strict_types=1);

namespace Core;

/**
 * کلاس مدیریت پیکربندی
 * 
 * الگوی Singleton برای یک نمونه سراسری
 * پشتیبانی از dot notation برای دسترسی nested
 * کش تنظیمات دیتابیس
 */
class Config
{
    /**
     * نمونه Singleton
     * @var self|null
     */
    private static ?self $instance = null;
    
    /**
     * تنظیمات فایلی (config.php)
     * @var array<string, mixed>
     */
    private array $fileConfig = [];
    
    /**
     * تنظیمات دیتابیس
     * @var array<string, mixed>
     */
    private array $dbConfig = [];
    
    /**
     * آیا تنظیمات دیتابیس بارگذاری شده؟
     * @var bool
     */
    private bool $dbConfigLoaded = false;
    
    /**
     * مقادیر پیش‌فرض سیستم
     * @var array<string, mixed>
     */
    private array $defaults = [];
    
    /**
     * کش تنظیمات ترکیبی
     * @var array<string, mixed>
     */
    private array $cache = [];
    
    /**
     * مسیر فایل config.php
     * @var string
     */
    private string $configFilePath;
    
    /**
     * مسیر فایل کش
     * @var string
     */
    private string $cacheFilePath;
    
    /**
     * Constructor خصوصی (Singleton)
     */
    private function __construct()
    {
        $this->configFilePath = PRIVATE_PATH . 'config.php';
        $this->cacheFilePath = CACHE_PATH . 'config.cache.php';
        
        $this->setDefaults();
        $this->loadFileConfig();
    }
    
    /**
     * جلوگیری از clone
     */
    private function __clone() {}
    
    /**
     * جلوگیری از unserialize
     * @throws \Exception
     */
    public function __wakeup()
    {
        throw new \Exception('Cannot unserialize Config singleton');
    }
    
    /**
     * دریافت نمونه Singleton
     * 
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * تعریف مقادیر پیش‌فرض
     */
    private function setDefaults(): void
    {
        $this->defaults = [
            // تنظیمات عمومی
            'app' => [
                'name' => 'ReadyCRM',
                'version' => CRM_VERSION,
                'debug' => false,
                'timezone' => 'Asia/Tehran',
                'locale' => 'fa_IR',
                'charset' => 'UTF-8',
            ],
            
            // تنظیمات دیتابیس
            'database' => [
                'host' => 'localhost',
                'port' => 3306,
                'name' => '',
                'username' => '',
                'password' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_persian_ci',
                'prefix' => 'crm_',
            ],
            
            // تنظیمات Session
            'session' => [
                'name' => 'CRMSSID',
                'lifetime' => 7200,
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax',
            ],
            
            // تنظیمات امنیتی
            'security' => [
                'csrf_enabled' => true,
                'csrf_token_name' => '_csrf_token',
                'csrf_lifetime' => 3600,
                'password_min_length' => 8,
                'max_login_attempts' => 5,
                'lockout_duration' => 900, // 15 دقیقه
                'allowed_hosts' => [],
            ],
            
            // تنظیمات OTP
            'otp' => [
                'enabled' => true,
                'length' => 6,
                'expiry' => 120, // 2 دقیقه
                'max_attempts' => 3,
                'resend_cooldown' => 60, // 1 دقیقه
                'lockout_after' => 5,
                'lockout_duration' => 3600, // 1 ساعت
            ],
            
            // تنظیمات SMS (MsgWay)
            'sms' => [
                'enabled' => false,
                'provider' => 'msgway',
                'api_key' => '',
                'sender' => '',
                'default_template' => '',
                'debug' => false,
                'log_enabled' => true,
                'rate_limit' => [
                    'per_number_per_hour' => 5,
                    'per_number_per_day' => 20,
                    'global_per_hour' => 1000,
                ],
            ],
            
            // تنظیمات WooCommerce
            'woocommerce' => [
                'enabled' => false,
                'store_url' => '',
                'consumer_key' => '',
                'consumer_secret' => '',
                'webhook_secret' => '',
                'sync_interval' => 300, // 5 دقیقه
                'batch_size' => 100,
                'sync_products' => true,
                'sync_customers' => true,
                'sync_orders' => true,
                'bidirectional' => true,
            ],
            
            // تنظیمات هوش مصنوعی (GapGPT)
            'ai' => [
                'enabled' => false,
                'provider' => 'gapgpt',
                'api_key' => '',
                'model' => 'gpt-4',
                'max_tokens' => 2000,
                'temperature' => 0.7,
                'timeout' => 30,
                'features' => [
                    'summarization' => true,
                    'tagging' => true,
                    'lead_scoring' => true,
                    'followup_drafts' => true,
                ],
            ],
            
            // تنظیمات Cron
            'cron' => [
                'enabled' => true,
                'secret_key' => '',
                'log_enabled' => true,
                'tasks' => [
                    'sms_queue' => ['interval' => 60, 'enabled' => true],
                    'woo_sync' => ['interval' => 300, 'enabled' => true],
                    'ai_process' => ['interval' => 120, 'enabled' => true],
                    'cleanup' => ['interval' => 86400, 'enabled' => true],
                ],
            ],
            
            // تنظیمات آپلود
            'upload' => [
                'max_size' => 10485760, // 10MB
                'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx'],
                'path' => UPLOADS_PATH,
            ],
            
            // تنظیمات صفحه‌بندی
            'pagination' => [
                'per_page' => 20,
                'max_per_page' => 100,
            ],
            
            // تنظیمات شرکت
            'company' => [
                'name' => '',
                'phone' => '',
                'email' => '',
                'address' => '',
                'tax_rate' => 9,
                'currency' => 'تومان',
                'currency_code' => 'IRR',
            ],
            
            // تنظیمات ایمیل
            'mail' => [
                'enabled' => false,
                'driver' => 'smtp',
                'host' => '',
                'port' => 587,
                'username' => '',
                'password' => '',
                'encryption' => 'tls',
                'from_address' => '',
                'from_name' => '',
            ],
            
            // تنظیمات لاگ
            'logging' => [
                'enabled' => true,
                'level' => 'info', // debug, info, warning, error, critical
                'max_files' => 30,
                'max_size' => 52428800, // 50MB
            ],
        ];
    }
    
    /**
     * بارگذاری تنظیمات از فایل config.php
     */
    private function loadFileConfig(): void
    {
        if (!file_exists($this->configFilePath)) {
            return;
        }
        
        // بارگذاری فایل
        $config = [];
        require $this->configFilePath;
        
        // تبدیل ثابت‌های تعریف شده به آرایه
        $this->fileConfig = $this->extractDefinedConstants();
    }
    
    /**
     * استخراج ثابت‌های تعریف شده در config.php
     * 
     * @return array<string, mixed>
     */
    private function extractDefinedConstants(): array
    {
        $config = [];
        
        // تنظیمات دیتابیس
        if (defined('DB_HOST')) $config['database']['host'] = DB_HOST;
        if (defined('DB_PORT')) $config['database']['port'] = DB_PORT;
        if (defined('DB_NAME')) $config['database']['name'] = DB_NAME;
        if (defined('DB_USER')) $config['database']['username'] = DB_USER;
        if (defined('DB_PASS')) $config['database']['password'] = DB_PASS;
        if (defined('DB_CHARSET')) $config['database']['charset'] = DB_CHARSET;
        if (defined('DB_PREFIX')) $config['database']['prefix'] = DB_PREFIX;
        
        // تنظیمات عمومی
        if (defined('APP_NAME')) $config['app']['name'] = APP_NAME;
        if (defined('DEBUG_MODE')) $config['app']['debug'] = DEBUG_MODE;
        if (defined('TIMEZONE')) $config['app']['timezone'] = TIMEZONE;
        
        // تنظیمات امنیتی
        if (defined('SESSION_LIFETIME')) $config['session']['lifetime'] = SESSION_LIFETIME;
        if (defined('CSRF_ENABLED')) $config['security']['csrf_enabled'] = CSRF_ENABLED;
        
        // تنظیمات آپلود
        if (defined('UPLOAD_MAX_SIZE')) $config['upload']['max_size'] = UPLOAD_MAX_SIZE;
        if (defined('UPLOAD_ALLOWED_TYPES')) $config['upload']['allowed_types'] = UPLOAD_ALLOWED_TYPES;
        if (defined('UPLOAD_PATH')) $config['upload']['path'] = UPLOAD_PATH;
        
        // تنظیمات صفحه‌بندی
        if (defined('RECORDS_PER_PAGE')) $config['pagination']['per_page'] = RECORDS_PER_PAGE;
        
        // تنظیمات شرکت
        if (defined('COMPANY_NAME')) $config['company']['name'] = COMPANY_NAME;
        if (defined('COMPANY_PHONE')) $config['company']['phone'] = COMPANY_PHONE;
        if (defined('COMPANY_EMAIL')) $config['company']['email'] = COMPANY_EMAIL;
        if (defined('CURRENCY')) $config['company']['currency'] = CURRENCY;
        if (defined('TAX_RATE')) $config['company']['tax_rate'] = TAX_RATE;
        
        // تنظیمات SMS
        if (defined('SMS_ENABLED')) $config['sms']['enabled'] = SMS_ENABLED;
        if (defined('SMS_API_KEY')) $config['sms']['api_key'] = SMS_API_KEY;
        if (defined('SMS_SENDER')) $config['sms']['sender'] = SMS_SENDER;
        
        // تنظیمات WooCommerce
        if (defined('WOO_ENABLED')) $config['woocommerce']['enabled'] = WOO_ENABLED;
        if (defined('WOO_STORE_URL')) $config['woocommerce']['store_url'] = WOO_STORE_URL;
        if (defined('WOO_CONSUMER_KEY')) $config['woocommerce']['consumer_key'] = WOO_CONSUMER_KEY;
        if (defined('WOO_CONSUMER_SECRET')) $config['woocommerce']['consumer_secret'] = WOO_CONSUMER_SECRET;
        if (defined('WOO_WEBHOOK_SECRET')) $config['woocommerce']['webhook_secret'] = WOO_WEBHOOK_SECRET;
        
        // تنظیمات AI
        if (defined('AI_ENABLED')) $config['ai']['enabled'] = AI_ENABLED;
        if (defined('AI_API_KEY')) $config['ai']['api_key'] = AI_API_KEY;
        
        // تنظیمات Cron
        if (defined('CRON_SECRET_KEY')) $config['cron']['secret_key'] = CRON_SECRET_KEY;
        
        return $config;
    }
    
    /**
     * بارگذاری تنظیمات از دیتابیس
     * 
     * @param \PDO|null $pdo اتصال دیتابیس
     * @return bool
     */
    public function loadFromDatabase(?\PDO $pdo = null): bool
    {
        if ($this->dbConfigLoaded) {
            return true;
        }
        
        // ابتدا تلاش برای بارگذاری از کش
        if ($this->loadFromCache()) {
            $this->dbConfigLoaded = true;
            return true;
        }
        
        if ($pdo === null) {
            return false;
        }
        
        try {
            $stmt = $pdo->query("SELECT setting_key, setting_value, setting_type FROM settings");
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach ($rows as $row) {
                $value = $this->castValue($row['setting_value'], $row['setting_type']);
                $this->dbConfig[$row['setting_key']] = $value;
            }
            
            // ذخیره در کش
            $this->saveToCache();
            
            $this->dbConfigLoaded = true;
            return true;
            
        } catch (\PDOException $e) {
            // لاگ خطا ولی ادامه بده
            error_log('[Config] Failed to load from database: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * تبدیل نوع مقدار
     * 
     * @param string|null $value
     * @param string $type
     * @return mixed
     */
    private function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }
        
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true) ?? [],
            'float' => (float) $value,
            default => $value,
        };
    }
    
    /**
     * بارگذاری از فایل کش
     * 
     * @return bool
     */
    private function loadFromCache(): bool
    {
        if (!file_exists($this->cacheFilePath)) {
            return false;
        }
        
        // بررسی انقضای کش (1 ساعت)
        $cacheAge = time() - filemtime($this->cacheFilePath);
        if ($cacheAge > 3600) {
            @unlink($this->cacheFilePath);
            return false;
        }
        
        $cached = include $this->cacheFilePath;
        
        if (is_array($cached) && isset($cached['data'])) {
            $this->dbConfig = $cached['data'];
            return true;
        }
        
        return false;
    }
    
    /**
     * ذخیره در فایل کش
     */
    private function saveToCache(): void
    {
        $content = "<?php\n// Generated: " . date('Y-m-d H:i:s') . "\nreturn " . 
                   var_export(['data' => $this->dbConfig, 'time' => time()], true) . ";\n";
        
        @file_put_contents($this->cacheFilePath, $content, LOCK_EX);
    }
    
    /**
     * پاکسازی کش تنظیمات
     */
    public function clearCache(): void
    {
        if (file_exists($this->cacheFilePath)) {
            @unlink($this->cacheFilePath);
        }
        
        $this->cache = [];
        $this->dbConfig = [];
        $this->dbConfigLoaded = false;
    }
    
    /**
     * دریافت مقدار تنظیم با dot notation
     * 
     * مثال: Config::get('database.host')
     * 
     * @param string $key کلید (با نقطه برای nested)
     * @param mixed $default مقدار پیش‌فرض
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // بررسی کش
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }
        
        // جستجو با اولویت: dbConfig > fileConfig > defaults
        $value = $this->findValue($key);
        
        if ($value === null) {
            $value = $default;
        }
        
        // ذخیره در کش
        $this->cache[$key] = $value;
        
        return $value;
    }
    
    /**
     * جستجوی مقدار در منابع مختلف
     * 
     * @param string $key
     * @return mixed
     */
    private function findValue(string $key): mixed
    {
        // ابتدا کلید ساده در dbConfig
        if (isset($this->dbConfig[$key])) {
            return $this->dbConfig[$key];
        }
        
        // جستجوی nested
        $keys = explode('.', $key);
        
        // جستجو در تنظیمات دیتابیس (برای کلیدهای ترکیبی مثل company.name)
        $dbKey = implode('_', $keys);
        if (isset($this->dbConfig[$dbKey])) {
            return $this->dbConfig[$dbKey];
        }
        
        // جستجو در تنظیمات فایل
        $fileValue = $this->getNestedValue($this->fileConfig, $keys);
        if ($fileValue !== null) {
            return $fileValue;
        }
        
        // جستجو در پیش‌فرض‌ها
        return $this->getNestedValue($this->defaults, $keys);
    }
    
    /**
     * دریافت مقدار nested از آرایه
     * 
     * @param array $array
     * @param array $keys
     * @return mixed
     */
    private function getNestedValue(array $array, array $keys): mixed
    {
        $current = $array;
        
        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return null;
            }
            $current = $current[$key];
        }
        
        return $current;
    }
    
    /**
     * تنظیم مقدار در زمان اجرا (فقط در حافظه)
     * 
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, mixed $value): void
    {
        $this->cache[$key] = $value;
        
        // تنظیم در آرایه fileConfig برای دسترسی nested
        $keys = explode('.', $key);
        $this->setNestedValue($this->fileConfig, $keys, $value);
    }
    
    /**
     * تنظیم مقدار nested در آرایه
     * 
     * @param array &$array
     * @param array $keys
     * @param mixed $value
     */
    private function setNestedValue(array &$array, array $keys, mixed $value): void
    {
        $current = &$array;
        
        foreach ($keys as $i => $key) {
            if ($i === count($keys) - 1) {
                $current[$key] = $value;
            } else {
                if (!isset($current[$key]) || !is_array($current[$key])) {
                    $current[$key] = [];
                }
                $current = &$current[$key];
            }
        }
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
     * دریافت همه تنظیمات یک گروه
     * 
     * مثال: Config::group('database') => ['host' => ..., 'name' => ...]
     * 
     * @param string $group
     * @return array
     */
    public function group(string $group): array
    {
        $result = [];
        
        // از پیش‌فرض‌ها شروع کن
        if (isset($this->defaults[$group]) && is_array($this->defaults[$group])) {
            $result = $this->defaults[$group];
        }
        
        // ترکیب با تنظیمات فایل
        if (isset($this->fileConfig[$group]) && is_array($this->fileConfig[$group])) {
            $result = array_merge($result, $this->fileConfig[$group]);
        }
        
        // ترکیب با تنظیمات دیتابیس
        $prefix = $group . '_';
        foreach ($this->dbConfig as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                $subKey = substr($key, strlen($prefix));
                $result[$subKey] = $value;
            }
        }
        
        return $result;
    }
    
    /**
     * دریافت همه تنظیمات
     * 
     * @param bool $includeSensitive شامل اطلاعات حساس
     * @return array
     */
    public function all(bool $includeSensitive = false): array
    {
        $all = array_replace_recursive(
            $this->defaults,
            $this->fileConfig,
        );
        
        if (!$includeSensitive) {
            $all = $this->maskSensitiveData($all);
        }
        
        return $all;
    }
    
    /**
     * پوشاندن اطلاعات حساس
     * 
     * @param array $data
     * @return array
     */
    private function maskSensitiveData(array $data): array
    {
        $sensitiveKeys = [
            'password', 'secret', 'api_key', 'consumer_key', 'consumer_secret',
            'webhook_secret', 'secret_key', 'private_key'
        ];
        
        array_walk_recursive($data, function (&$value, $key) use ($sensitiveKeys) {
            foreach ($sensitiveKeys as $sensitive) {
                if (stripos($key, $sensitive) !== false && is_string($value) && $value !== '') {
                    $value = '***MASKED***';
                    break;
                }
            }
        });
        
        return $data;
    }
    
    /**
     * ذخیره تنظیم در دیتابیس
     * 
     * @param \PDO $pdo
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @param string $description
     * @return bool
     */
    public function saveSetting(
        \PDO $pdo,
        string $key,
        mixed $value,
        string $type = 'string',
        string $description = ''
    ): bool {
        try {
            // تبدیل مقدار به رشته
            $stringValue = match ($type) {
                'json' => json_encode($value, JSON_UNESCAPED_UNICODE),
                'boolean' => $value ? '1' : '0',
                default => (string) $value,
            };
            
            $sql = "INSERT INTO settings (setting_key, setting_value, setting_type, description) 
                    VALUES (:key, :value, :type, :description)
                    ON DUPLICATE KEY UPDATE 
                    setting_value = VALUES(setting_value),
                    setting_type = VALUES(setting_type),
                    description = VALUES(description),
                    updated_at = CURRENT_TIMESTAMP";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                ':key' => $key,
                ':value' => $stringValue,
                ':type' => $type,
                ':description' => $description,
            ]);
            
            if ($result) {
                // بروزرسانی کش محلی
                $this->dbConfig[$key] = $value;
                $this->clearCache();
            }
            
            return $result;
            
        } catch (\PDOException $e) {
            error_log('[Config] Failed to save setting: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * حذف تنظیم از دیتابیس
     * 
     * @param \PDO $pdo
     * @param string $key
     * @return bool
     */
    public function deleteSetting(\PDO $pdo, string $key): bool
    {
        try {
            $stmt = $pdo->prepare("DELETE FROM settings WHERE setting_key = :key");
            $result = $stmt->execute([':key' => $key]);
            
            if ($result) {
                unset($this->dbConfig[$key]);
                $this->clearCache();
            }
            
            return $result;
            
        } catch (\PDOException $e) {
            error_log('[Config] Failed to delete setting: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * بررسی حالت Debug
     * 
     * @return bool
     */
    public function isDebug(): bool
    {
        return (bool) $this->get('app.debug', false);
    }
    
    /**
     * دریافت تنظیمات دیتابیس برای PDO
     * 
     * @return array
     */
    public function getDatabaseConfig(): array
    {
        return [
            'host' => $this->get('database.host', 'localhost'),
            'port' => $this->get('database.port', 3306),
            'name' => $this->get('database.name', ''),
            'username' => $this->get('database.username', ''),
            'password' => $this->get('database.password', ''),
            'charset' => $this->get('database.charset', 'utf8mb4'),
            'collation' => $this->get('database.collation', 'utf8mb4_persian_ci'),
            'prefix' => $this->get('database.prefix', 'crm_'),
        ];
    }
    
    /**
     * Helper استاتیک برای دسترسی سریع
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        return self::getInstance()->get($key, $default);
    }
}

// ============================================
// توابع کمکی سراسری
// ============================================

if (!function_exists('config')) {
    /**
     * دریافت مقدار تنظیم
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function config(string $key, mixed $default = null): mixed
    {
        return Config::getInstance()->get($key, $default);
    }
}

if (!function_exists('config_set')) {
    /**
     * تنظیم مقدار در زمان اجرا
     * 
     * @param string $key
     * @param mixed $value
     */
    function config_set(string $key, mixed $value): void
    {
        Config::getInstance()->set($key, $value);
    }
}
