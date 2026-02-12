<?php
/**
 * ReadyCRM v2 - Request Handler (مدیریت درخواست)
 * 
 * فایل: private/core/Request.php
 * 
 * مدیریت کامل درخواست‌های HTTP:
 * - دسترسی امن به GET, POST, FILES
 * - Sanitization و Validation خودکار
 * - مدیریت آپلود فایل
 * - پشتیبانی از JSON Input
 * - CSRF Protection
 * 
 * @package    ReadyCRM
 * @version    2.0.0
 * @author     ReadyStudio.ir
 */

namespace Core;

class Request
{
    /**
     * نمونه Singleton
     */
    private static ?Request $instance = null;
    
    /**
     * داده‌های GET
     * @var array
     */
    private array $query = [];
    
    /**
     * داده‌های POST
     * @var array
     */
    private array $post = [];
    
    /**
     * داده‌های JSON (برای API)
     * @var array
     */
    private array $json = [];
    
    /**
     * فایل‌های آپلود شده
     * @var array
     */
    private array $files = [];
    
    /**
     * هدرهای HTTP
     * @var array
     */
    private array $headers = [];
    
    /**
     * کوکی‌ها
     * @var array
     */
    private array $cookies = [];
    
    /**
     * Server variables
     * @var array
     */
    private array $server = [];
    
    /**
     * متد HTTP
     * @var string
     */
    private string $method = 'GET';
    
    /**
     * URI درخواست
     * @var string
     */
    private string $uri = '/';
    
    /**
     * خطاهای Validation
     * @var array
     */
    private array $validationErrors = [];
    
    /**
     * Constructor خصوصی (Singleton)
     */
    private function __construct()
    {
        $this->initialize();
    }
    
    /**
     * جلوگیری از Clone
     */
    private function __clone() {}
    
    /**
     * جلوگیری از Unserialize
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
    
    /**
     * دریافت نمونه Singleton
     */
    public static function getInstance(): Request
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * مقداردهی اولیه
     */
    private function initialize(): void
    {
        // Server variables
        $this->server = $_SERVER;
        
        // متد HTTP
        $this->method = $this->detectMethod();
        
        // URI
        $this->uri = $this->detectUri();
        
        // Query string (GET)
        $this->query = $this->sanitizeArray($_GET);
        
        // POST data
        $this->post = $this->sanitizeArray($_POST);
        
        // JSON input (برای API)
        $this->parseJsonInput();
        
        // Files
        $this->files = $this->normalizeFiles($_FILES);
        
        // Headers
        $this->headers = $this->parseHeaders();
        
        // Cookies
        $this->cookies = $this->sanitizeArray($_COOKIE);
    }
    
    // ═══════════════════════════════════════════════════════════
    // تشخیص متد و URI
    // ═══════════════════════════════════════════════════════════
    
    /**
     * تشخیص متد HTTP واقعی
     */
    private function detectMethod(): string
    {
        $method = $this->server['REQUEST_METHOD'] ?? 'GET';
        
        // Method Override از POST
        if ($method === 'POST') {
            // از فیلد فرم
            if (isset($_POST['_method'])) {
                $method = strtoupper($_POST['_method']);
            }
            // از هدر
            elseif (isset($this->server['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
                $method = strtoupper($this->server['HTTP_X_HTTP_METHOD_OVERRIDE']);
            }
        }
        
        return $method;
    }
    
    /**
     * تشخیص URI درخواست
     */
    private function detectUri(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        
        // حذف query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        // حذف base path
        if (defined('BASE_PATH') && BASE_PATH) {
            $basePath = rtrim(BASE_PATH, '/');
            if (strpos($uri, $basePath) === 0) {
                $uri = substr($uri, strlen($basePath));
            }
        }
        
        return '/' . trim($uri, '/');
    }
    
    /**
     * پارس کردن JSON Input
     */
    private function parseJsonInput(): void
    {
        $contentType = $this->server['CONTENT_TYPE'] ?? '';
        
        if (stripos($contentType, 'application/json') !== false) {
            $rawInput = file_get_contents('php://input');
            if ($rawInput) {
                $decoded = json_decode($rawInput, true);
                if (is_array($decoded)) {
                    $this->json = $this->sanitizeArray($decoded);
                }
            }
        }
    }
    
    /**
     * پارس کردن هدرها
     */
    private function parseHeaders(): array
    {
        $headers = [];
        
        foreach ($this->server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                // HTTP_CONTENT_TYPE → Content-Type
                $name = str_replace('_', '-', substr($key, 5));
                $name = ucwords(strtolower($name), '-');
                $headers[$name] = $value;
            }
        }
        
        // هدرهای خاص
        if (isset($this->server['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $this->server['CONTENT_TYPE'];
        }
        if (isset($this->server['CONTENT_LENGTH'])) {
            $headers['Content-Length'] = $this->server['CONTENT_LENGTH'];
        }
        
        return $headers;
    }
    
    // ═══════════════════════════════════════════════════════════
    // Sanitization (پاکسازی داده‌ها)
    // ═══════════════════════════════════════════════════════════
    
    /**
     * پاکسازی آرایه به صورت بازگشتی
     */
    private function sanitizeArray(array $data): array
    {
        $clean = [];
        
        foreach ($data as $key => $value) {
            // پاکسازی کلید
            $key = $this->sanitizeKey($key);
            
            if (is_array($value)) {
                $clean[$key] = $this->sanitizeArray($value);
            } else {
                $clean[$key] = $this->sanitizeValue($value);
            }
        }
        
        return $clean;
    }
    
    /**
     * پاکسازی کلید
     */
    private function sanitizeKey($key): string
    {
        return preg_replace('/[^a-zA-Z0-9_\-\[\]]/', '', (string) $key);
    }
    
    /**
     * پاکسازی مقدار
     */
    private function sanitizeValue($value): string
    {
        if ($value === null) {
            return '';
        }
        
        $value = (string) $value;
        
        // حذف کاراکترهای NULL
        $value = str_replace(chr(0), '', $value);
        
        // Trim whitespace
        $value = trim($value);
        
        return $value;
    }
    
    /**
     * نرمال‌سازی آرایه فایل‌ها
     */
    private function normalizeFiles(array $files): array
    {
        $normalized = [];
        
        foreach ($files as $key => $file) {
            if (is_array($file['name'])) {
                // Multiple files
                $normalized[$key] = [];
                foreach ($file['name'] as $i => $name) {
                    if ($file['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                        $normalized[$key][] = new UploadedFile(
                            $file['tmp_name'][$i],
                            $file['name'][$i],
                            $file['type'][$i],
                            $file['size'][$i],
                            $file['error'][$i]
                        );
                    }
                }
            } else {
                // Single file
                if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                    $normalized[$key] = new UploadedFile(
                        $file['tmp_name'],
                        $file['name'],
                        $file['type'],
                        $file['size'],
                        $file['error']
                    );
                }
            }
        }
        
        return $normalized;
    }
    
    // ═══════════════════════════════════════════════════════════
    // دسترسی به داده‌ها (Data Access)
    // ═══════════════════════════════════════════════════════════
    
    /**
     * دریافت مقدار از ورودی (POST, JSON, GET به ترتیب)
     * 
     * @param string|null $key کلید (null = همه)
     * @param mixed $default مقدار پیش‌فرض
     * @return mixed
     */
    public function input(?string $key = null, $default = null)
    {
        // ادغام همه منابع (اولویت: POST > JSON > GET)
        $all = array_merge($this->query, $this->json, $this->post);
        
        if ($key === null) {
            return $all;
        }
        
        return $this->getNestedValue($all, $key, $default);
    }
    
    /**
     * دریافت فقط از GET
     */
    public function query(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->getNestedValue($this->query, $key, $default);
    }
    
    /**
     * دریافت فقط از POST
     */
    public function post(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->post;
        }
        return $this->getNestedValue($this->post, $key, $default);
    }
    
    /**
     * دریافت از JSON body
     */
    public function json(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->json;
        }
        return $this->getNestedValue($this->json, $key, $default);
    }
    
    /**
     * دریافت فقط کلیدهای مشخص شده
     * 
     * @param array $keys ['name', 'email', 'phone']
     * @return array
     */
    public function only(array $keys): array
    {
        $all = $this->input();
        $result = [];
        
        foreach ($keys as $key) {
            if (array_key_exists($key, $all)) {
                $result[$key] = $all[$key];
            }
        }
        
        return $result;
    }
    
    /**
     * دریافت همه به جز کلیدهای مشخص شده
     */
    public function except(array $keys): array
    {
        $all = $this->input();
        
        foreach ($keys as $key) {
            unset($all[$key]);
        }
        
        return $all;
    }
    
    /**
     * بررسی وجود کلید
     */
    public function has(string $key): bool
    {
        return $this->input($key) !== null;
    }
    
    /**
     * بررسی پر بودن کلید
     */
    public function filled(string $key): bool
    {
        $value = $this->input($key);
        return $value !== null && $value !== '' && $value !== [];
    }
    
    /**
     * دریافت مقدار با dot notation
     */
    private function getNestedValue(array $data, string $key, $default = null)
    {
        if (array_key_exists($key, $data)) {
            return $data[$key];
        }
        
        // Dot notation support
        if (strpos($key, '.') !== false) {
            $segments = explode('.', $key);
            $value = $data;
            
            foreach ($segments as $segment) {
                if (!is_array($value) || !array_key_exists($segment, $value)) {
                    return $default;
                }
                $value = $value[$segment];
            }
            
            return $value;
        }
        
        return $default;
    }
    
    // ═══════════════════════════════════════════════════════════
    // فیلتر و تبدیل نوع (Type Casting)
    // ═══════════════════════════════════════════════════════════
    
    /**
     * دریافت به صورت عدد صحیح
     */
    public function integer(string $key, int $default = 0): int
    {
        $value = $this->input($key);
        return is_numeric($value) ? (int) $value : $default;
    }
    
    /**
     * دریافت به صورت عدد اعشاری
     */
    public function float(string $key, float $default = 0.0): float
    {
        $value = $this->input($key);
        return is_numeric($value) ? (float) $value : $default;
    }
    
    /**
     * دریافت به صورت Boolean
     */
    public function boolean(string $key, bool $default = false): bool
    {
        $value = $this->input($key);
        
        if ($value === null) {
            return $default;
        }
        
        // مقادیر true
        if (in_array($value, [true, 1, '1', 'true', 'yes', 'on', 'بله'], true)) {
            return true;
        }
        
        // مقادیر false
        if (in_array($value, [false, 0, '0', 'false', 'no', 'off', 'خیر'], true)) {
            return false;
        }
        
        return $default;
    }
    
    /**
     * دریافت به صورت آرایه
     */
    public function array(string $key, array $default = []): array
    {
        $value = $this->input($key);
        
        if (is_array($value)) {
            return $value;
        }
        
        if (is_string($value) && $value !== '') {
            // تلاش برای پارس JSON
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
            // جداکننده کاما
            return array_map('trim', explode(',', $value));
        }
        
        return $default;
    }
    
    /**
     * دریافت تاریخ (تبدیل شمسی به میلادی اگر لازم باشد)
     */
    public function date(string $key, ?string $default = null): ?string
    {
        $value = $this->input($key);
        
        if (empty($value)) {
            return $default;
        }
        
        // تشخیص تاریخ شمسی (1404/01/15 یا 1404-01-15)
        if (preg_match('/^[1-4][0-9]{3}[\/-](0[1-9]|1[0-2])[\/-](0[1-9]|[12][0-9]|3[01])$/', $value)) {
            // تبدیل به میلادی - نیاز به کلاس Jalalian
            if (class_exists('Core\Jalalian')) {
                return \Core\Jalalian::toGregorian($value);
            }
        }
        
        // تاریخ میلادی
        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }
        
        return $default;
    }
    
    /**
     * پاکسازی HTML
     */
    public function clean(string $key, $default = null): ?string
    {
        $value = $this->input($key, $default);
        
        if ($value === null) {
            return null;
        }
        
        // حذف تگ‌های HTML
        $value = strip_tags((string) $value);
        
        // تبدیل کاراکترهای خاص
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        
        return $value;
    }
    
    /**
     * دریافت ایمیل معتبر
     */
    public function email(string $key): ?string
    {
        $value = $this->input($key);
        
        if (empty($value)) {
            return null;
        }
        
        $email = filter_var($value, FILTER_VALIDATE_EMAIL);
        return $email !== false ? $email : null;
    }
    
    /**
     * دریافت URL معتبر
     */
    public function url(string $key): ?string
    {
        $value = $this->input($key);
        
        if (empty($value)) {
            return null;
        }
        
        $url = filter_var($value, FILTER_VALIDATE_URL);
        return $url !== false ? $url : null;
    }
    
    /**
     * نرمال‌سازی شماره تلفن ایرانی
     */
    public function phone(string $key): ?string
    {
        $value = $this->input($key);
        
        if (empty($value)) {
            return null;
        }
        
        // حذف فاصله و خط تیره
        $phone = preg_replace('/[\s\-\(\)]/', '', $value);
        
        // تبدیل +98 به 0
        $phone = preg_replace('/^\+98/', '0', $phone);
        
        // تبدیل 98 به 0
        $phone = preg_replace('/^98/', '0', $phone);
        
        // اضافه کردن 0 اگر نداشت
        if (preg_match('/^9[0-9]{9}$/', $phone)) {
            $phone = '0' . $phone;
        }
        
        // اعتبارسنجی فرمت نهایی
        if (preg_match('/^09[0-9]{9}$/', $phone)) {
            return $phone;
        }
        
        // تلفن ثابت
        if (preg_match('/^0[1-8][0-9]{9}$/', $phone)) {
            return $phone;
        }
        
        return null;
    }
    
    // ═══════════════════════════════════════════════════════════
    // Validation (اعتبارسنجی)
    // ═══════════════════════════════════════════════════════════
    
    /**
     * اعتبارسنجی ورودی‌ها
     * 
     * @param array $rules قوانین به فرمت ['field' => 'required|email|max:255']
     * @param array $messages پیام‌های سفارشی
     * @return bool
     */
    public function validate(array $rules, array $messages = []): bool
    {
        $this->validationErrors = [];
        
        foreach ($rules as $field => $ruleString) {
            $value = $this->input($field);
            $fieldRules = is_array($ruleString) ? $ruleString : explode('|', $ruleString);
            
            foreach ($fieldRules as $rule) {
                $result = $this->applyRule($field, $value, $rule, $messages);
                
                if ($result !== true) {
                    if (!isset($this->validationErrors[$field])) {
                        $this->validationErrors[$field] = [];
                    }
                    $this->validationErrors[$field][] = $result;
                }
            }
        }
        
        return empty($this->validationErrors);
    }
    
    /**
     * اعمال یک قانون
     */
    private function applyRule(string $field, $value, string $rule, array $messages): mixed
    {
        // استخراج پارامتر (مثلاً max:255)
        $params = [];
        if (strpos($rule, ':') !== false) {
            [$rule, $paramString] = explode(':', $rule, 2);
            $params = explode(',', $paramString);
        }
        
        // نام فیلد فارسی
        $fieldLabel = $messages["{$field}.label"] ?? $field;
        
        // کلید پیام سفارشی
        $messageKey = "{$field}.{$rule}";
        
        switch ($rule) {
            case 'required':
                if ($value === null || $value === '' || $value === []) {
                    return $messages[$messageKey] ?? "فیلد {$fieldLabel} الزامی است";
                }
                break;
                
            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return $messages[$messageKey] ?? "فرمت ایمیل نامعتبر است";
                }
                break;
                
            case 'url':
                if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
                    return $messages[$messageKey] ?? "فرمت URL نامعتبر است";
                }
                break;
                
            case 'numeric':
                if ($value && !is_numeric($value)) {
                    return $messages[$messageKey] ?? "فیلد {$fieldLabel} باید عدد باشد";
                }
                break;
                
            case 'integer':
                if ($value && !filter_var($value, FILTER_VALIDATE_INT)) {
                    return $messages[$messageKey] ?? "فیلد {$fieldLabel} باید عدد صحیح باشد";
                }
                break;
                
            case 'min':
                $min = (int) ($params[0] ?? 0);
                if (is_numeric($value) && $value < $min) {
                    return $messages[$messageKey] ?? "فیلد {$fieldLabel} باید حداقل {$min} باشد";
                }
                if (is_string($value) && mb_strlen($value) < $min) {
                    return $messages[$messageKey] ?? "فیلد {$fieldLabel} باید حداقل {$min} کاراکتر باشد";
                }
                break;
                
            case 'max':
                $max = (int) ($params[0] ?? 0);
                if (is_numeric($value) && $value > $max) {
                    return $messages[$messageKey] ?? "فیلد {$fieldLabel} نباید بیشتر از {$max} باشد";
                }
                if (is_string($value) && mb_strlen($value) > $max) {
                    return $messages[$messageKey] ?? "فیلد {$fieldLabel} نباید بیشتر از {$max} کاراکتر باشد";
                }
                break;
                
            case 'between':
                $min = (int) ($params[0] ?? 0);
                $max = (int) ($params[1] ?? 0);
                $len = is_string($value) ? mb_strlen($value) : $value;
                if ($value && ($len < $min || $len > $max)) {
                    return $messages[$messageKey] ?? "فیلد {$fieldLabel} باید بین {$min} و {$max} باشد";
                }
                break;
                
            case 'in':
                if ($value && !in_array($value, $params)) {
                    $options = implode('، ', $params);
                    return $messages[$messageKey] ?? "فیلد {$fieldLabel} باید یکی از: {$options} باشد";
                }
                break;
                
            case 'not_in':
                if ($value && in_array($value, $params)) {
                    return $messages[$messageKey] ?? "مقدار فیلد {$fieldLabel} مجاز نیست";
                }
                break;
                
            case 'regex':
                $pattern = $params[0] ?? '';
                if ($value && !preg_match($pattern, $value)) {
                    return $messages[$messageKey] ?? "فرمت فیلد {$fieldLabel} نامعتبر است";
                }
                break;
                
            case 'phone':
            case 'mobile':
                if ($value && !preg_match('/^09[0-9]{9}$/', preg_replace('/[\s\-]/', '', $value))) {
                    return $messages[$messageKey] ?? "شماره موبایل نامعتبر است";
                }
                break;
                
            case 'national_code':
                if ($value && !$this->validateNationalCode($value)) {
                    return $messages[$messageKey] ?? "کد ملی نامعتبر است";
                }
                break;
                
            case 'persian':
                if ($value && !preg_match('/^[\x{0600}-\x{06FF}\s]+$/u', $value)) {
                    return $messages[$messageKey] ?? "فیلد {$fieldLabel} باید فارسی باشد";
                }
                break;
                
            case 'alpha':
                if ($value && !preg_match('/^[a-zA-Z]+$/', $value)) {
                    return $messages[$messageKey] ?? "فیلد {$fieldLabel} فقط باید شامل حروف باشد";
                }
                break;
                
            case 'alpha_num':
                if ($value && !preg_match('/^[a-zA-Z0-9]+$/', $value)) {
                    return $messages[$messageKey] ?? "فیلد {$fieldLabel} فقط باید شامل حروف و اعداد باشد";
                }
                break;
                
            case 'confirmed':
                $confirmation = $this->input("{$field}_confirmation");
                if ($value !== $confirmation) {
                    return $messages[$messageKey] ?? "تکرار {$fieldLabel} مطابقت ندارد";
                }
                break;
                
            case 'unique':
                // پارامتر: table,column,except_id
                if ($value && count($params) >= 2) {
                    $table = $params[0];
                    $column = $params[1];
                    $exceptId = $params[2] ?? null;
                    
                    if (function_exists('db')) {
                        $query = db()->table($table)->where($column, $value);
                        if ($exceptId) {
                            $query->where('id', '!=', $exceptId);
                        }
                        if ($query->exists()) {
                            return $messages[$messageKey] ?? "این {$fieldLabel} قبلاً استفاده شده است";
                        }
                    }
                }
                break;
                
            case 'exists':
                // پارامتر: table,column
                if ($value && count($params) >= 2) {
                    $table = $params[0];
                    $column = $params[1];
                    
                    if (function_exists('db')) {
                        if (!db()->table($table)->where($column, $value)->exists()) {
                            return $messages[$messageKey] ?? "{$fieldLabel} یافت نشد";
                        }
                    }
                }
                break;
        }
        
        return true;
    }
    
    /**
     * اعتبارسنجی کد ملی ایرانی
     */
    private function validateNationalCode(string $code): bool
    {
        // حذف خط تیره
        $code = str_replace('-', '', $code);
        
        // باید 10 رقم باشد
        if (!preg_match('/^[0-9]{10}$/', $code)) {
            return false;
        }
        
        // همه ارقام یکسان نباشد
        if (preg_match('/^(.)\1{9}$/', $code)) {
            return false;
        }
        
        // محاسبه کنترل
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $code[$i] * (10 - $i);
        }
        
        $remainder = $sum % 11;
        $check = (int) $code[9];
        
        return ($remainder < 2 && $check === $remainder) || 
               ($remainder >= 2 && $check === (11 - $remainder));
    }
    
    /**
     * دریافت خطاهای Validation
     */
    public function errors(): array
    {
        return $this->validationErrors;
    }
    
    /**
     * دریافت اولین خطای یک فیلد
     */
    public function error(string $field): ?string
    {
        return $this->validationErrors[$field][0] ?? null;
    }
    
    /**
     * آیا Validation موفق بود؟
     */
    public function validated(): bool
    {
        return empty($this->validationErrors);
    }
    
    // ═══════════════════════════════════════════════════════════
    // فایل‌ها (File Upload)
    // ═══════════════════════════════════════════════════════════
    
    /**
     * دریافت فایل آپلود شده
     * 
     * @param string $key نام فیلد
     * @return UploadedFile|array|null
     */
    public function file(string $key)
    {
        return $this->files[$key] ?? null;
    }
    
    /**
     * بررسی وجود فایل
     */
    public function hasFile(string $key): bool
    {
        $file = $this->file($key);
        
        if ($file instanceof UploadedFile) {
            return $file->isValid();
        }
        
        if (is_array($file)) {
            return !empty($file);
        }
        
        return false;
    }
    
    /**
     * دریافت همه فایل‌ها
     */
    public function allFiles(): array
    {
        return $this->files;
    }
    
    // ═══════════════════════════════════════════════════════════
    // هدرها و متادیتا
    // ═══════════════════════════════════════════════════════════
    
    /**
     * دریافت هدر
     */
    public function header(string $key, $default = null): ?string
    {
        // نرمال‌سازی نام هدر
        $key = str_replace('_', '-', $key);
        $key = ucwords(strtolower($key), '-');
        
        return $this->headers[$key] ?? $default;
    }
    
    /**
     * دریافت همه هدرها
     */
    public function headers(): array
    {
        return $this->headers;
    }
    
    /**
     * دریافت Bearer Token
     */
    public function bearerToken(): ?string
    {
        $auth = $this->header('Authorization');
        
        if ($auth && preg_match('/Bearer\s+(.+)/i', $auth, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * دریافت متد HTTP
     */
    public function method(): string
    {
        return $this->method;
    }
    
    /**
     * بررسی متد
     */
    public function isMethod(string $method): bool
    {
        return strtoupper($method) === $this->method;
    }
    
    /**
     * دریافت URI
     */
    public function uri(): string
    {
        return $this->uri;
    }
    
    /**
     * دریافت URL کامل
     */
    public function fullUrl(): string
    {
        $scheme = $this->isSecure() ? 'https' : 'http';
        $host = $this->server['HTTP_HOST'] ?? 'localhost';
        $uri = $this->server['REQUEST_URI'] ?? '/';
        
        return "{$scheme}://{$host}{$uri}";
    }
    
    /**
     * آیا HTTPS است؟
     */
    public function isSecure(): bool
    {
        return isset($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off';
    }
    
    /**
     * آیا درخواست AJAX است؟
     */
    public function isAjax(): bool
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }
    
    /**
     * آیا درخواست JSON می‌خواهد؟
     */
    public function wantsJson(): bool
    {
        $accept = $this->header('Accept', '');
        return stripos($accept, 'application/json') !== false || $this->isAjax();
    }
    
    /**
     * آیا Content-Type JSON است؟
     */
    public function isJson(): bool
    {
        $contentType = $this->header('Content-Type', '');
        return stripos($contentType, 'application/json') !== false;
    }
    
    /**
     * دریافت IP کاربر
     */
    public function ip(): string
    {
        // بررسی پروکسی‌ها
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        ];
        
        foreach ($headers as $header) {
            if (!empty($this->server[$header])) {
                $ips = explode(',', $this->server[$header]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * دریافت User Agent
     */
    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }
    
    /**
     * دریافت Referer
     */
    public function referer(): ?string
    {
        return $this->server['HTTP_REFERER'] ?? null;
    }
    
    // ═══════════════════════════════════════════════════════════
    // CSRF Protection
    // ═══════════════════════════════════════════════════════════
    
    /**
     * بررسی CSRF Token
     */
    public function validateCsrf(): bool
    {
        // متدهایی که نیاز به بررسی دارند
        if (in_array($this->method, ['GET', 'HEAD', 'OPTIONS'])) {
            return true;
        }
        
        $token = $this->input('_token') ?? $this->header('X-CSRF-TOKEN');
        
        if (!$token) {
            return false;
        }
        
        // مقایسه با توکن session
        if (function_exists('csrf_token')) {
            return hash_equals(csrf_token(), $token);
        }
        
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    // ═══════════════════════════════════════════════════════════
    // کوکی‌ها
    // ═══════════════════════════════════════════════════════════
    
    /**
     * دریافت کوکی
     */
    public function cookie(string $key, $default = null)
    {
        return $this->cookies[$key] ?? $default;
    }
    
    /**
     * همه کوکی‌ها
     */
    public function cookies(): array
    {
        return $this->cookies;
    }
}

// ═══════════════════════════════════════════════════════════════════
// کلاس UploadedFile (فایل آپلود شده)
// ═══════════════════════════════════════════════════════════════════

class UploadedFile
{
    private string $tmpPath;
    private string $originalName;
    private string $mimeType;
    private int $size;
    private int $error;
    
    /**
     * MIME types مجاز به تفکیک دسته
     */
    private const MIME_TYPES = [
        'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'],
        'document' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv',
        ],
        'archive' => ['application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed'],
        'video' => ['video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo'],
        'audio' => ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp3'],
    ];
    
    public function __construct(string $tmpPath, string $name, string $type, int $size, int $error)
    {
        $this->tmpPath = $tmpPath;
        $this->originalName = $name;
        $this->mimeType = $type;
        $this->size = $size;
        $this->error = $error;
    }
    
    /**
     * آیا فایل معتبر است؟
     */
    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK && is_uploaded_file($this->tmpPath);
    }
    
    /**
     * دریافت خطای آپلود
     */
    public function getError(): int
    {
        return $this->error;
    }
    
    /**
     * دریافت پیام خطا
     */
    public function getErrorMessage(): string
    {
        $messages = [
            UPLOAD_ERR_OK         => 'فایل با موفقیت آپلود شد',
            UPLOAD_ERR_INI_SIZE   => 'حجم فایل بیش از حد مجاز است',
            UPLOAD_ERR_FORM_SIZE  => 'حجم فایل بیش از حد مجاز فرم است',
            UPLOAD_ERR_PARTIAL    => 'فایل به طور ناقص آپلود شد',
            UPLOAD_ERR_NO_FILE    => 'فایلی انتخاب نشده است',
            UPLOAD_ERR_NO_TMP_DIR => 'پوشه موقت یافت نشد',
            UPLOAD_ERR_CANT_WRITE => 'خطا در نوشتن فایل',
            UPLOAD_ERR_EXTENSION  => 'آپلود توسط افزونه متوقف شد',
        ];
        
        return $messages[$this->error] ?? 'خطای ناشناخته';
    }
    
    /**
     * نام اصلی فایل
     */
    public function getOriginalName(): string
    {
        return $this->originalName;
    }
    
    /**
     * پسوند فایل
     */
    public function getExtension(): string
    {
        return strtolower(pathinfo($this->originalName, PATHINFO_EXTENSION));
    }
    
    /**
     * نوع MIME
     */
    public function getMimeType(): string
    {
        // تشخیص واقعی MIME
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $this->tmpPath);
            finfo_close($finfo);
            return $mime ?: $this->mimeType;
        }
        
        return $this->mimeType;
    }
    
    /**
     * حجم فایل (بایت)
     */
    public function getSize(): int
    {
        return $this->size;
    }
    
    /**
     * حجم فایل (فرمت انسانی)
     */
    public function getHumanSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->size;
        $unit = 0;
        
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        
        return round($size, 2) . ' ' . $units[$unit];
    }
    
    /**
     * مسیر موقت
     */
    public function getTmpPath(): string
    {
        return $this->tmpPath;
    }
    
    /**
     * آیا تصویر است؟
     */
    public function isImage(): bool
    {
        return in_array($this->getMimeType(), self::MIME_TYPES['image']);
    }
    
    /**
     * آیا سند است؟
     */
    public function isDocument(): bool
    {
        return in_array($this->getMimeType(), self::MIME_TYPES['document']);
    }
    
    /**
     * اعتبارسنجی نوع فایل
     * 
     * @param array $allowedTypes ['image', 'document'] یا ['image/jpeg', 'image/png']
     */
    public function validateType(array $allowedTypes): bool
    {
        $mime = $this->getMimeType();
        
        foreach ($allowedTypes as $type) {
            // دسته‌بندی
            if (isset(self::MIME_TYPES[$type])) {
                if (in_array($mime, self::MIME_TYPES[$type])) {
                    return true;
                }
            }
            // MIME مستقیم
            elseif ($mime === $type) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * اعتبارسنجی حجم
     * 
     * @param int $maxSize حداکثر حجم (بایت)
     */
    public function validateSize(int $maxSize): bool
    {
        return $this->size <= $maxSize;
    }
    
    /**
     * انتقال فایل به مقصد
     * 
     * @param string $directory پوشه مقصد
     * @param string|null $filename نام فایل (null = تولید خودکار)
     * @return string|false مسیر نهایی یا false
     */
    public function store(string $directory, ?string $filename = null)
    {
        if (!$this->isValid()) {
            return false;
        }
        
        // ایجاد پوشه اگر نبود
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        // تولید نام فایل
        if ($filename === null) {
            $filename = $this->generateFilename();
        } else {
            // اطمینان از پسوند
            $ext = $this->getExtension();
            if (!str_ends_with($filename, '.' . $ext)) {
                $filename .= '.' . $ext;
            }
        }
        
        $destination = rtrim($directory, '/') . '/' . $filename;
        
        if (move_uploaded_file($this->tmpPath, $destination)) {
            return $destination;
        }
        
        return false;
    }
    
    /**
     * تولید نام فایل یکتا
     */
    private function generateFilename(): string
    {
        $ext = $this->getExtension();
        $name = pathinfo($this->originalName, PATHINFO_FILENAME);
        
        // پاکسازی نام
        $name = preg_replace('/[^a-zA-Z0-9\-_]/', '', $name);
        $name = substr($name, 0, 50);
        
        // اضافه کردن timestamp و random
        $unique = time() . '_' . bin2hex(random_bytes(4));
        
        return "{$name}_{$unique}.{$ext}";
    }
    
    /**
     * خواندن محتوای فایل
     */
    public function getContents(): string
    {
        return file_get_contents($this->tmpPath);
    }
    
    /**
     * Hash فایل (برای تشخیص تکراری)
     */
    public function hash(string $algo = 'md5'): string
    {
        return hash_file($algo, $this->tmpPath);
    }
}

// ═══════════════════════════════════════════════════════════════════
// توابع کمکی سراسری (Global Helper Functions)
// ═══════════════════════════════════════════════════════════════════

if (!function_exists('request')) {
    /**
     * دسترسی به نمونه Request
     * 
     * @param string|null $key کلید (اختیاری)
     * @param mixed $default مقدار پیش‌فرض
     * @return Request|mixed
     */
    function request(?string $key = null, $default = null)
    {
        $request = Request::getInstance();
        
        if ($key === null) {
            return $request;
        }
        
        return $request->input($key, $default);
    }
}

if (!function_exists('input')) {
    /**
     * دریافت مقدار ورودی
     */
    function input(string $key, $default = null)
    {
        return Request::getInstance()->input($key, $default);
    }
}

if (!function_exists('old')) {
    /**
     * دریافت مقدار قبلی (برای فرم‌ها)
     * اگر Session::old موجود نبود از input بخوان
     */
    function old(string $key, $default = null)
    {
        // اول از session
        if (function_exists('session')) {
            $session = session();
            if (method_exists($session, 'getOld')) {
                $value = $session->getOld($key);
                if ($value !== null) {
                    return $value;
                }
            }
        }
        
        // سپس از flash data
        if (isset($_SESSION['_old_input'][$key])) {
            return $_SESSION['_old_input'][$key];
        }
        
        return $default;
    }
}

if (!function_exists('validate')) {
    /**
     * اعتبارسنجی سریع
     * 
     * @param array $rules
     * @param array $messages
     * @return bool
     */
    function validate(array $rules, array $messages = []): bool
    {
        return Request::getInstance()->validate($rules, $messages);
    }
}

if (!function_exists('validation_errors')) {
    /**
     * دریافت خطاهای اعتبارسنجی
     */
    function validation_errors(): array
    {
        return Request::getInstance()->errors();
    }
}

if (!function_exists('is_ajax')) {
    /**
     * آیا درخواست AJAX است؟
     */
    function is_ajax(): bool
    {
        return Request::getInstance()->isAjax();
    }
}

if (!function_exists('client_ip')) {
    /**
     * دریافت IP کاربر
     */
    function client_ip(): string
    {
        return Request::getInstance()->ip();
    }
}
