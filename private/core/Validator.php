<?php
/**
 * ReadyCRM v2 - Validator (اعتبارسنجی پیشرفته)
 * 
 * فایل: private/core/Validator.php
 * 
 * سیستم اعتبارسنجی جامع:
 * - بیش از ۳۵ قانون اعتبارسنجی
 * - پشتیبانی از قوانین سفارشی
 * - پیام‌های خطای فارسی
 * - اعتبارسنجی شرطی
 * - اعتبارسنجی آرایه‌ها و فایل‌ها
 * - قوانین ویژه فارسی (کد ملی، موبایل، شبا و...)
 * 
 * @package    ReadyCRM
 * @version    2.0.0
 * @author     ReadyStudio.ir
 */

namespace Core;

class Validator
{
    /**
     * داده‌های ورودی
     */
    private array $data = [];
    
    /**
     * قوانین اعتبارسنجی
     */
    private array $rules = [];
    
    /**
     * خطاها
     */
    private array $errors = [];
    
    /**
     * پیام‌های سفارشی
     */
    private array $customMessages = [];
    
    /**
     * نام‌های سفارشی فیلدها
     */
    private array $customAttributes = [];
    
    /**
     * داده‌های اعتبارسنجی شده
     */
    private array $validated = [];
    
    /**
     * قوانین سفارشی ثبت شده
     */
    private static array $customRules = [];
    
    /**
     * آیا باید در اولین خطا متوقف شود؟
     */
    private bool $stopOnFirstFailure = false;
    
    /**
     * اتصال دیتابیس برای قوانین unique/exists
     */
    private ?\PDO $db = null;
    
    /**
     * پیام‌های پیش‌فرض فارسی
     */
    private const DEFAULT_MESSAGES = [
        'required'          => ':attribute الزامی است.',
        'required_if'       => ':attribute در این شرایط الزامی است.',
        'required_with'     => ':attribute در صورت وجود :values الزامی است.',
        'required_without'  => ':attribute در صورت عدم وجود :values الزامی است.',
        'required_with_all' => ':attribute در صورت وجود همه :values الزامی است.',
        'required_without_all' => ':attribute در صورت عدم وجود همه :values الزامی است.',
        'filled'            => ':attribute نباید خالی باشد.',
        'present'           => ':attribute باید موجود باشد.',
        'string'            => ':attribute باید متن باشد.',
        'numeric'           => ':attribute باید عدد باشد.',
        'integer'           => ':attribute باید عدد صحیح باشد.',
        'float'             => ':attribute باید عدد اعشاری باشد.',
        'boolean'           => ':attribute باید بله یا خیر باشد.',
        'array'             => ':attribute باید آرایه باشد.',
        'email'             => ':attribute باید یک ایمیل معتبر باشد.',
        'url'               => ':attribute باید یک آدرس وب معتبر باشد.',
        'active_url'        => ':attribute باید یک آدرس وب فعال باشد.',
        'ip'                => ':attribute باید یک آدرس IP معتبر باشد.',
        'ipv4'              => ':attribute باید یک آدرس IPv4 معتبر باشد.',
        'ipv6'              => ':attribute باید یک آدرس IPv6 معتبر باشد.',
        'date'              => ':attribute باید یک تاریخ معتبر باشد.',
        'date_format'       => ':attribute باید با فرمت :format مطابقت داشته باشد.',
        'date_equals'       => ':attribute باید برابر با :date باشد.',
        'before'            => ':attribute باید قبل از :date باشد.',
        'before_or_equal'   => ':attribute باید قبل یا برابر با :date باشد.',
        'after'             => ':attribute باید بعد از :date باشد.',
        'after_or_equal'    => ':attribute باید بعد یا برابر با :date باشد.',
        'between'           => ':attribute باید بین :min و :max باشد.',
        'min'               => ':attribute باید حداقل :min باشد.',
        'max'               => ':attribute باید حداکثر :max باشد.',
        'size'              => ':attribute باید دقیقاً :size باشد.',
        'length'            => ':attribute باید دقیقاً :length کاراکتر باشد.',
        'min_length'        => ':attribute باید حداقل :min کاراکتر باشد.',
        'max_length'        => ':attribute باید حداکثر :max کاراکتر باشد.',
        'digits'            => ':attribute باید :digits رقم باشد.',
        'digits_between'    => ':attribute باید بین :min و :max رقم باشد.',
        'in'                => ':attribute باید یکی از مقادیر مجاز باشد.',
        'not_in'            => ':attribute نباید یکی از مقادیر غیرمجاز باشد.',
        'regex'             => ':attribute فرمت نامعتبر دارد.',
        'not_regex'         => ':attribute فرمت نامعتبر دارد.',
        'confirmed'         => ':attribute با تأیید مطابقت ندارد.',
        'same'              => ':attribute باید با :other یکسان باشد.',
        'different'         => ':attribute باید با :other متفاوت باشد.',
        'gt'                => ':attribute باید بزرگتر از :value باشد.',
        'gte'               => ':attribute باید بزرگتر یا مساوی :value باشد.',
        'lt'                => ':attribute باید کوچکتر از :value باشد.',
        'lte'               => ':attribute باید کوچکتر یا مساوی :value باشد.',
        'unique'            => ':attribute قبلاً استفاده شده است.',
        'exists'            => ':attribute در سیستم وجود ندارد.',
        'alpha'             => ':attribute فقط باید شامل حروف باشد.',
        'alpha_num'         => ':attribute فقط باید شامل حروف و اعداد باشد.',
        'alpha_dash'        => ':attribute فقط باید شامل حروف، اعداد، خط تیره و زیرخط باشد.',
        'alpha_space'       => ':attribute فقط باید شامل حروف و فاصله باشد.',
        'slug'              => ':attribute فقط باید شامل حروف، اعداد و خط تیره باشد.',
        'mobile'            => ':attribute باید یک شماره موبایل معتبر ایرانی باشد.',
        'phone'             => ':attribute باید یک شماره تلفن معتبر باشد.',
        'national_code'     => ':attribute باید یک کد ملی معتبر باشد.',
        'postal_code'       => ':attribute باید یک کد پستی معتبر باشد.',
        'sheba'             => ':attribute باید یک شماره شبا معتبر باشد.',
        'card_number'       => ':attribute باید یک شماره کارت بانکی معتبر باشد.',
        'persian'           => ':attribute فقط باید شامل حروف فارسی باشد.',
        'persian_num'       => ':attribute فقط باید شامل حروف فارسی و اعداد باشد.',
        'persian_alpha_num' => ':attribute فقط باید شامل حروف فارسی، انگلیسی و اعداد باشد.',
        'jalali'            => ':attribute باید یک تاریخ شمسی معتبر باشد.',
        'file'              => ':attribute باید یک فایل باشد.',
        'image'             => ':attribute باید یک تصویر باشد.',
        'mimes'             => ':attribute باید از نوع :values باشد.',
        'mimetypes'         => ':attribute باید از نوع :values باشد.',
        'max_size'          => ':attribute نباید بیشتر از :max کیلوبایت باشد.',
        'min_size'          => ':attribute باید حداقل :min کیلوبایت باشد.',
        'dimensions'        => ':attribute ابعاد تصویر نامعتبر است.',
        'json'              => ':attribute باید یک رشته JSON معتبر باشد.',
        'uuid'              => ':attribute باید یک UUID معتبر باشد.',
        'accepted'          => ':attribute باید پذیرفته شود.',
        'accepted_if'       => ':attribute باید پذیرفته شود.',
        'declined'          => ':attribute باید رد شود.',
        'declined_if'       => ':attribute باید رد شود.',
        'starts_with'       => ':attribute باید با :values شروع شود.',
        'ends_with'         => ':attribute باید با :values پایان یابد.',
        'doesnt_start_with' => ':attribute نباید با :values شروع شود.',
        'doesnt_end_with'   => ':attribute نباید با :values پایان یابد.',
        'contains'          => ':attribute باید شامل :value باشد.',
        'not_contains'      => ':attribute نباید شامل :value باشد.',
        'password'          => ':attribute باید حداقل ۸ کاراکتر و شامل حروف بزرگ، کوچک و عدد باشد.',
        'strong_password'   => ':attribute باید حداقل ۱۲ کاراکتر و شامل حروف بزرگ، کوچک، عدد و نماد باشد.',
        'current_password'  => ':attribute رمز عبور فعلی صحیح نیست.',
        'prohibited'        => ':attribute مجاز نیست.',
        'prohibited_if'     => ':attribute در این شرایط مجاز نیست.',
        'prohibited_unless' => ':attribute مجاز نیست مگر :other برابر :values باشد.',
    ];
    
    // ═══════════════════════════════════════════════════════════
    // سازنده و Factory Methods
    // ═══════════════════════════════════════════════════════════
    
    /**
     * سازنده
     */
    public function __construct(array $data = [], array $rules = [], array $messages = [], array $attributes = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->customMessages = $messages;
        $this->customAttributes = $attributes;
    }
    
    /**
     * ایجاد نمونه جدید (Factory)
     */
    public static function make(array $data, array $rules, array $messages = [], array $attributes = []): self
    {
        return new self($data, $rules, $messages, $attributes);
    }
    
    /**
     * اعتبارسنجی سریع با پرتاب استثنا
     */
    public static function validate(array $data, array $rules, array $messages = [], array $attributes = []): array
    {
        $validator = new self($data, $rules, $messages, $attributes);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        
        return $validator->validated();
    }
    
    /**
     * اعتبارسنجی سریع بدون پرتاب استثنا
     */
    public static function check(array $data, array $rules): bool
    {
        return (new self($data, $rules))->passes();
    }
    
    // ═══════════════════════════════════════════════════════════
    // تنظیمات و پیکربندی
    // ═══════════════════════════════════════════════════════════
    
    /**
     * تنظیم اتصال دیتابیس
     */
    public function setDatabase(\PDO $db): self
    {
        $this->db = $db;
        return $this;
    }
    
    /**
     * تنظیم توقف در اولین خطا
     */
    public function stopOnFirstFailure(bool $stop = true): self
    {
        $this->stopOnFirstFailure = $stop;
        return $this;
    }
    
    /**
     * افزودن قانون سفارشی سراسری
     */
    public static function extend(string $rule, callable $callback, string $message = ''): void
    {
        self::$customRules[$rule] = [
            'callback' => $callback,
            'message' => $message ?: ":attribute نامعتبر است."
        ];
    }
    
    /**
     * افزودن قانون سفارشی با کلاس
     */
    public static function extendWithClass(string $rule, string $className): void
    {
        if (!class_exists($className) || !method_exists($className, 'passes')) {
            throw new \InvalidArgumentException("کلاس قانون باید متد passes داشته باشد.");
        }
        
        self::$customRules[$rule] = [
            'class' => $className,
            'message' => method_exists($className, 'message') ? null : ":attribute نامعتبر است."
        ];
    }
    
    /**
     * تنظیم داده‌ها
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        $this->reset();
        return $this;
    }
    
    /**
     * تنظیم قوانین
     */
    public function setRules(array $rules): self
    {
        $this->rules = $rules;
        $this->reset();
        return $this;
    }
    
    /**
     * افزودن قانون به فیلد
     */
    public function addRule(string $field, $rules): self
    {
        if (isset($this->rules[$field])) {
            $existing = is_array($this->rules[$field]) ? $this->rules[$field] : explode('|', $this->rules[$field]);
            $new = is_array($rules) ? $rules : explode('|', $rules);
            $this->rules[$field] = array_merge($existing, $new);
        } else {
            $this->rules[$field] = $rules;
        }
        return $this;
    }
    
    /**
     * حذف قانون از فیلد
     */
    public function removeRule(string $field, ?string $rule = null): self
    {
        if ($rule === null) {
            unset($this->rules[$field]);
        } else {
            if (isset($this->rules[$field])) {
                $rules = is_array($this->rules[$field]) ? $this->rules[$field] : explode('|', $this->rules[$field]);
                $this->rules[$field] = array_filter($rules, fn($r) => !str_starts_with($r, $rule));
            }
        }
        return $this;
    }
    
    /**
     * تنظیم پیام‌های سفارشی
     */
    public function setMessages(array $messages): self
    {
        $this->customMessages = $messages;
        return $this;
    }
    
    /**
     * افزودن پیام سفارشی
     */
    public function addMessage(string $key, string $message): self
    {
        $this->customMessages[$key] = $message;
        return $this;
    }
    
    /**
     * تنظیم نام فیلدها
     */
    public function setAttributes(array $attributes): self
    {
        $this->customAttributes = $attributes;
        return $this;
    }
    
    /**
     * افزودن نام فیلد
     */
    public function addAttribute(string $field, string $name): self
    {
        $this->customAttributes[$field] = $name;
        return $this;
    }
    
    /**
     * ریست کردن نتایج
     */
    private function reset(): void
    {
        $this->errors = [];
        $this->validated = [];
    }
    
    // ═══════════════════════════════════════════════════════════
    // اجرای اعتبارسنجی
    // ═══════════════════════════════════════════════════════════
    
    /**
     * اجرای اعتبارسنجی
     */
    public function run(): self
    {
        $this->reset();
        
        foreach ($this->rules as $field => $rules) {
            // پشتیبانی از wildcard (مثل items.*.price)
            if (strpos($field, '*') !== false) {
                $this->validateWildcardField($field, $rules);
            } else {
                $this->validateField($field, $rules);
            }
            
            if ($this->stopOnFirstFailure && !empty($this->errors)) {
                break;
            }
        }
        
        return $this;
    }
    
    /**
     * اعتبارسنجی فیلدهای wildcard
     */
    private function validateWildcardField(string $pattern, $rules): void
    {
        $fields = $this->expandWildcard($pattern);
        
        foreach ($fields as $field) {
            $this->validateField($field, $rules);
        }
    }
    
    /**
     * گسترش wildcard به فیلدهای واقعی
     */
    private function expandWildcard(string $pattern): array
    {
        $fields = [];
        $parts = explode('.', $pattern);
        
        $this->expandWildcardRecursive($this->data, $parts, '', $fields);
        
        return $fields;
    }
    
    /**
     * گسترش بازگشتی wildcard
     */
    private function expandWildcardRecursive(array $data, array $parts, string $prefix, array &$fields): void
    {
        if (empty($parts)) {
            if ($prefix !== '') {
                $fields[] = rtrim($prefix, '.');
            }
            return;
        }
        
        $part = array_shift($parts);
        
        if ($part === '*') {
            if (is_array($data)) {
                foreach (array_keys($data) as $key) {
                    $newPrefix = $prefix . $key . '.';
                    $this->expandWildcardRecursive(
                        is_array($data[$key]) ? $data[$key] : [],
                        $parts,
                        $newPrefix,
                        $fields
                    );
                }
            }
        } else {
            if (isset($data[$part])) {
                $newPrefix = $prefix . $part . '.';
                $this->expandWildcardRecursive(
                    is_array($data[$part]) ? $data[$part] : [],
                    $parts,
                    $newPrefix,
                    $fields
                );
            }
        }
    }
    
    /**
     * اعتبارسنجی یک فیلد
     */
    private function validateField(string $field, $rules): void
    {
        // تبدیل قوانین رشته‌ای به آرایه
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }
        
        // دریافت مقدار فیلد (پشتیبانی از nested)
        $value = $this->getValue($field);
        
        // بررسی nullable
        $isNullable = $this->hasRule($rules, 'nullable');
        if ($isNullable && $this->isEmpty($value)) {
            return;
        }
        
        // بررسی sometimes
        $isSometimes = $this->hasRule($rules, 'sometimes');
        if ($isSometimes && !$this->hasValue($field)) {
            return;
        }
        
        // بررسی bail (توقف در اولین خطای این فیلد)
        $hasBail = $this->hasRule($rules, 'bail');
        
        foreach ($rules as $rule) {
            // رد کردن قوانین خاص
            if (in_array($rule, ['nullable', 'sometimes', 'bail'], true)) {
                continue;
            }
            
            $result = $this->applyRule($field, $value, $rule);
            
            if ($result === false && $hasBail) {
                break;
            }
        }
        
        // اگر خطایی نداشت، به validated اضافه کن
        if (!isset($this->errors[$field])) {
            $this->setValidatedValue($field, $value);
        }
    }
    
    /**
     * بررسی وجود قانون در لیست
     */
    private function hasRule(array $rules, string $ruleName): bool
    {
        foreach ($rules as $rule) {
            if ($rule === $ruleName || str_starts_with($rule, $ruleName . ':')) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * اعمال یک قانون
     */
    private function applyRule(string $field, $value, string $rule): bool
    {
        // پارس کردن قانون و پارامترها
        $parameters = [];
        if (strpos($rule, ':') !== false) {
            [$rule, $paramStr] = explode(':', $rule, 2);
            $parameters = str_getcsv($paramStr); // پشتیبانی از کاما در پارامترها
        }
        
        // نام متد
        $method = 'validate' . $this->studly($rule);
        
        // بررسی قوانین سفارشی
        if (isset(self::$customRules[$rule])) {
            return $this->applyCustomRule($field, $value, $rule, $parameters);
        }
        
        // بررسی وجود متد
        if (!method_exists($this, $method)) {
            throw new \InvalidArgumentException("قانون اعتبارسنجی '$rule' وجود ندارد.");
        }
        
        // اجرای متد
        $passes = $this->$method($field, $value, $parameters);
        
        if (!$passes) {
            $this->addError($field, $rule, $parameters);
            return false;
        }
        
        return true;
    }
    
    /**
     * اعمال قانون سفارشی
     */
    private function applyCustomRule(string $field, $value, string $rule, array $parameters): bool
    {
        $custom = self::$customRules[$rule];
        
        // قانون با callback
        if (isset($custom['callback'])) {
            $passes = call_user_func($custom['callback'], $field, $value, $parameters, $this);
        }
        // قانون با کلاس
        else {
            $instance = new $custom['class']();
            $passes = $instance->passes($field, $value, $parameters);
            
            if (!$passes && method_exists($instance, 'message')) {
                $this->customMessages[$rule] = $instance->message();
            }
        }
        
        if (!$passes) {
            $this->addError($field, $rule, $parameters);
            return false;
        }
        
        return true;
    }
    
    /**
     * افزودن خطا
     */
    private function addError(string $field, string $rule, array $parameters = []): void
    {
        $message = $this->getMessage($field, $rule, $parameters);
        
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $this->errors[$field][] = $message;
    }
    
    /**
     * دریافت پیام خطا
     */
    private function getMessage(string $field, string $rule, array $parameters): string
    {
        // پیام سفارشی برای فیلد و قانون خاص
        $customKey = "{$field}.{$rule}";
        if (isset($this->customMessages[$customKey])) {
            $message = $this->customMessages[$customKey];
        }
        // پیام سفارشی برای قانون
        elseif (isset($this->customMessages[$rule])) {
            $message = $this->customMessages[$rule];
        }
        // پیام قانون سفارشی
        elseif (isset(self::$customRules[$rule]['message'])) {
            $message = self::$customRules[$rule]['message'];
        }
        // پیام پیش‌فرض
        else {
            $message = self::DEFAULT_MESSAGES[$rule] ?? ':attribute نامعتبر است.';
        }
        
        // جایگزینی متغیرها
        return $this->replaceMessagePlaceholders($message, $field, $parameters);
    }
    
    /**
     * جایگزینی placeholderهای پیام
     */
    private function replaceMessagePlaceholders(string $message, string $field, array $parameters): string
    {
        $attribute = $this->customAttributes[$field] ?? $this->humanize($field);
        
        $replacements = [
            ':attribute' => $attribute,
            ':field' => $field,
            ':ATTRIBUTE' => mb_strtoupper($attribute),
            ':Attribute' => mb_ucfirst($attribute),
        ];
        
        // افزودن پارامترها
        foreach ($parameters as $i => $param) {
            $replacements[':param' . $i] = $param;
        }
        
        // جایگزینی‌های خاص برای قوانین مختلف
        if (isset($parameters[0])) {
            $replacements[':min'] = $parameters[0];
            $replacements[':max'] = $parameters[1] ?? $parameters[0];
            $replacements[':size'] = $parameters[0];
            $replacements[':length'] = $parameters[0];
            $replacements[':digits'] = $parameters[0];
            $replacements[':format'] = $parameters[0];
            $replacements[':date'] = $parameters[0];
            $replacements[':value'] = $parameters[0];
            $replacements[':values'] = implode(', ', $parameters);
            
            // برای قوانین مقایسه‌ای
            if (isset($this->customAttributes[$parameters[0]])) {
                $replacements[':other'] = $this->customAttributes[$parameters[0]];
            } else {
                $replacements[':other'] = $this->humanize($parameters[0]);
            }
        }
        
        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }
    
    // ═══════════════════════════════════════════════════════════
    // نتایج اعتبارسنجی
    // ═══════════════════════════════════════════════════════════
    
    /**
     * آیا اعتبارسنجی موفق بود؟
     */
    public function passes(): bool
    {
        if (empty($this->errors) && empty($this->validated)) {
            $this->run();
        }
        return empty($this->errors);
    }
    
    /**
     * آیا اعتبارسنجی ناموفق بود؟
     */
    public function fails(): bool
    {
        return !$this->passes();
    }
    
    /**
     * دریافت تمام خطاها
     */
    public function errors(): ValidationErrorBag
    {
        return new ValidationErrorBag($this->errors);
    }
    
    /**
     * دریافت خطاها به صورت آرایه
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * دریافت اولین خطای یک فیلد
     */
    public function error(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }
    
    /**
     * دریافت تمام خطاها به صورت تخت
     */
    public function allErrors(): array
    {
        $all = [];
        foreach ($this->errors as $messages) {
            foreach ($messages as $message) {
                $all[] = $message;
            }
        }
        return $all;
    }
    
    /**
     * دریافت اولین خطا
     */
    public function firstError(): ?string
    {
        foreach ($this->errors as $messages) {
            return $messages[0] ?? null;
        }
        return null;
    }
    
    /**
     * آیا فیلد خطا دارد؟
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]) && count($this->errors[$field]) > 0;
    }
    
    /**
     * دریافت داده‌های اعتبارسنجی شده
     */
    public function validated(): array
    {
        if (empty($this->validated) && empty($this->errors)) {
            $this->run();
        }
        return $this->validated;
    }
    
    /**
     * دریافت داده‌های امن
     */
    public function safe(): ValidatedInput
    {
        return new ValidatedInput($this->validated());
    }
    
    /**
     * دریافت فقط فیلدهای مشخص شده
     */
    public function only(array $keys): array
    {
        $validated = $this->validated();
        return array_intersect_key($validated, array_flip($keys));
    }
    
    /**
     * دریافت همه به جز فیلدهای مشخص شده
     */
    public function except(array $keys): array
    {
        $validated = $this->validated();
        return array_diff_key($validated, array_flip($keys));
    }
    
    // ═══════════════════════════════════════════════════════════
    // توابع کمکی داخلی
    // ═══════════════════════════════════════════════════════════
    
    /**
     * دریافت مقدار فیلد (پشتیبانی از nested)
     */
    private function getValue(string $field)
    {
        // پشتیبانی از dot notation
        if (strpos($field, '.') === false) {
            return $this->data[$field] ?? null;
        }
        
        $keys = explode('.', $field);
        $value = $this->data;
        
        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }
        
        return $value;
    }
    
    /**
     * تنظیم مقدار در validated (پشتیبانی از nested)
     */
    private function setValidatedValue(string $field, $value): void
    {
        if (strpos($field, '.') === false) {
            $this->validated[$field] = $value;
            return;
        }
        
        $keys = explode('.', $field);
        $lastKey = array_pop($keys);
        $ref = &$this->validated;
        
        foreach ($keys as $key) {
            if (!isset($ref[$key]) || !is_array($ref[$key])) {
                $ref[$key] = [];
            }
            $ref = &$ref[$key];
        }
        
        $ref[$lastKey] = $value;
    }
    
    /**
     * آیا فیلد مقدار دارد (در data موجود است)؟
     */
    private function hasValue(string $field): bool
    {
        if (strpos($field, '.') === false) {
            return array_key_exists($field, $this->data);
        }
        
        $keys = explode('.', $field);
        $value = $this->data;
        
        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return false;
            }
            $value = $value[$key];
        }
        
        return true;
    }
    
    /**
     * آیا مقدار خالی است؟
     */
    private function isEmpty($value): bool
    {
        if ($value === null) {
            return true;
        }
        
        if (is_string($value) && trim($value) === '') {
            return true;
        }
        
        if (is_array($value) && count($value) === 0) {
            return true;
        }
        
        return false;
    }
    
    /**
     * تبدیل snake_case به StudlyCase
     */
    private function studly(string $value): string
    {
        $value = str_replace(['-', '_'], ' ', $value);
        return str_replace(' ', '', ucwords($value));
    }
    
    /**
     * تبدیل نام فیلد به قابل خواندن
     */
    private function humanize(string $field): string
    {
        // حذف prefix های nested
        if (strpos($field, '.') !== false) {
            $parts = explode('.', $field);
            $field = end($parts);
        }
        
        // تبدیل snake_case و camelCase
        $field = preg_replace('/([a-z])([A-Z])/', '$1 $2', $field);
        $field = str_replace(['_', '-'], ' ', $field);
        
        return $field;
    }
    
    /**
     * دریافت اندازه یک مقدار
     */
    private function getSize($value, string $type = 'string'): float
    {
        if (is_numeric($value) && $type !== 'string') {
            return (float) $value;
        }
        
        if (is_array($value)) {
            return count($value);
        }
        
        if (is_string($value)) {
            return mb_strlen($value, 'UTF-8');
        }
        
        // برای فایل‌ها
        if (isset($value['size'])) {
            return $value['size'] / 1024; // تبدیل به کیلوبایت
        }
        
        return 0;
    }
    
    /**
     * دسترسی به داده‌ها برای قوانین
     */
    public function getData(): array
    {
        return $this->data;
    }
    
    // ═══════════════════════════════════════════════════════════
    // قوانین اعتبارسنجی - الزامی و وجود
    // ═══════════════════════════════════════════════════════════
    
    /**
     * الزامی
     */
    protected function validateRequired(string $field, $value, array $params): bool
    {
        if ($value === null) {
            return false;
        }
        
        if (is_string($value) && trim($value) === '') {
            return false;
        }
        
        if (is_array($value) && count($value) === 0) {
            return false;
        }
        
        // برای فایل‌ها
        if (is_array($value) && isset($value['tmp_name']) && empty($value['tmp_name'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * الزامی اگر فیلد دیگر مقدار خاصی داشته باشد
     */
    protected function validateRequiredIf(string $field, $value, array $params): bool
    {
        if (count($params) < 2) {
            return true;
        }
        
        $otherField = $params[0];
        $otherValues = array_slice($params, 1);
        $actualValue = $this->getValue($otherField);
        
        // اگر فیلد دیگر یکی از مقادیر مورد نظر را دارد
        if (in_array($actualValue, $otherValues, true) || in_array((string)$actualValue, $otherValues, true)) {
            return $this->validateRequired($field, $value, []);
        }
        
        return true;
    }
    
    /**
     * الزامی اگر فیلد دیگر مقدار خاصی نداشته باشد
     */
    protected function validateRequiredUnless(string $field, $value, array $params): bool
    {
        if (count($params) < 2) {
            return true;
        }
        
        $otherField = $params[0];
        $otherValues = array_slice($params, 1);
        $actualValue = $this->getValue($otherField);
        
        // اگر فیلد دیگر یکی از مقادیر مورد نظر را ندارد
        if (!in_array($actualValue, $otherValues, true) && !in_array((string)$actualValue, $otherValues, true)) {
            return $this->validateRequired($field, $value, []);
        }
        
        return true;
    }
    
    /**
     * الزامی در صورت وجود هر یک از فیلدهای دیگر
     */
    protected function validateRequiredWith(string $field, $value, array $params): bool
    {
        foreach ($params as $otherField) {
            $otherValue = $this->getValue($otherField);
            if (!$this->isEmpty($otherValue)) {
                return $this->validateRequired($field, $value, []);
            }
        }
        return true;
    }
    
    /**
     * الزامی در صورت وجود همه فیلدهای دیگر
     */
    protected function validateRequiredWithAll(string $field, $value, array $params): bool
    {
        foreach ($params as $otherField) {
            $otherValue = $this->getValue($otherField);
            if ($this->isEmpty($otherValue)) {
                return true;
            }
        }
        return $this->validateRequired($field, $value, []);
    }
    
    /**
     * الزامی در صورت عدم وجود هر یک از فیلدهای دیگر
     */
    protected function validateRequiredWithout(string $field, $value, array $params): bool
    {
        foreach ($params as $otherField) {
            $otherValue = $this->getValue($otherField);
            if ($this->isEmpty($otherValue)) {
                return $this->validateRequired($field, $value, []);
            }
        }
        return true;
    }
    
    /**
     * الزامی در صورت عدم وجود همه فیلدهای دیگر
     */
    protected function validateRequiredWithoutAll(string $field, $value, array $params): bool
    {
        foreach ($params as $otherField) {
            $otherValue = $this->getValue($otherField);
            if (!$this->isEmpty($otherValue)) {
                return true;
            }
        }
        return $this->validateRequired($field, $value, []);
    }
    
    /**
     * نباید خالی باشد (اگر موجود است)
     */
    protected function validateFilled(string $field, $value, array $params): bool
    {
        if (!$this->hasValue($field)) {
            return true;
        }
        return $this->validateRequired($field, $value, []);
    }
    
    /**
     * باید در داده‌ها موجود باشد
     */
    protected function validatePresent(string $field, $value, array $params): bool
    {
        return $this->hasValue($field);
    }
    
    // ═══════════════════════════════════════════════════════════
    // قوانین اعتبارسنجی - نوع داده
    // ═══════════════════════════════════════════════════════════
    
    /**
     * رشته
     */
    protected function validateString(string $field, $value, array $params): bool
    {
        return is_string($value);
    }
    
    /**
     * عددی
     */
    protected function validateNumeric(string $field, $value, array $params): bool
    {
        return is_numeric($value);
    }
    
    /**
     * عدد صحیح
     */
    protected function validateInteger(string $field, $value, array $params): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
    
    /**
     * عدد اعشاری
     */
    protected function validateFloat(string $field, $value, array $params): bool
    {
        return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
    }
    
    /**
     * عدد اعشاری (نام جایگزین)
     */
    protected function validateDecimal(string $field, $value, array $params): bool
    {
        if (!$this->validateNumeric($field, $value, [])) {
            return false;
        }
        
        // اگر تعداد اعشار مشخص شده
        if (!empty($params)) {
            $decimals = isset($params[1]) ? range($params[0], $params[1]) : [$params[0]];
            $parts = explode('.', (string) $value);
            $actualDecimals = isset($parts[1]) ? strlen($parts[1]) : 0;
            return in_array($actualDecimals, $decimals);
        }
        
        return true;
    }
    
    /**
     * بولین
     */
    protected function validateBoolean(string $field, $value, array $params): bool
    {
        $acceptable = [true, false, 0, 1, '0', '1', 'true', 'false', 'yes', 'no', 'on', 'off'];
        return in_array($value, $acceptable, true);
    }
    
    /**
     * آرایه
     */
    protected function validateArray(string $field, $value, array $params): bool
    {
        if (!is_array($value)) {
            return false;
        }
        
        // اگر کلیدهای مجاز مشخص شده
        if (!empty($params)) {
            $keys = array_keys($value);
            foreach ($keys as $key) {
                if (!in_array((string) $key, $params, true)) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * JSON معتبر
     */
    protected function validateJson(string $field, $value, array $params): bool
    {
        if (!is_string($value)) {
            return false;
        }
        
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }
    
    // ═══════════════════════════════════════════════════════════
    // قوانین اعتبارسنجی - اندازه و طول
    // ═══════════════════════════════════════════════════════════
    
    /**
     * حداقل (عدد/طول/تعداد)
     */
    protected function validateMin(string $field, $value, array $params): bool
    {
        $min = (float) ($params[0] ?? 0);
        $size = $this->getSize($value, is_numeric($value) ? 'numeric' : 'string');
        
        return $size >= $min;
    }
    
    /**
     * حداکثر (عدد/طول/تعداد)
     */
    protected function validateMax(string $field, $value, array $params): bool
    {
        $max = (float) ($params[0] ?? PHP_INT_MAX);
        $size = $this->getSize($value, is_numeric($value) ? 'numeric' : 'string');
        
        return $size <= $max;
    }
    
    /**
     * بین دو مقدار
     */
    protected function validateBetween(string $field, $value, array $params): bool
    {
        $min = (float) ($params[0] ?? 0);
        $max = (float) ($params[1] ?? PHP_INT_MAX);
        $size = $this->getSize($value, is_numeric($value) ? 'numeric' : 'string');
        
        return $size >= $min && $size <= $max;
    }
    
    /**
     * اندازه دقیق
     */
    protected function validateSize(string $field, $value, array $params): bool
    {
        $size = (float) ($params[0] ?? 0);
        $actualSize = $this->getSize($value, is_numeric($value) ? 'numeric' : 'string');
        
        return $actualSize == $size;
    }
    
    /**
     * طول دقیق رشته
     */
    protected function validateLength(string $field, $value, array $params): bool
    {
        if (!is_string($value)) {
            return false;
        }
        
        $length = (int) ($params[0] ?? 0);
        return mb_strlen($value, 'UTF-8') === $length;
    }
    
    /**
     * حداقل طول رشته
     */
    protected function validateMinLength(string $field, $value, array $params): bool
    {
        if (!is_string($value)) {
            return false;
        }
        
        $min = (int) ($params[0] ?? 0);
        return mb_strlen($value, 'UTF-8') >= $min;
    }
    
    /**
     * حداکثر طول رشته
     */
    protected function validateMaxLength(string $field, $value, array $params): bool
    {
        if (!is_string($value)) {
            return false;
        }
        
        $max = (int) ($params[0] ?? PHP_INT_MAX);
        return mb_strlen($value, 'UTF-8') <= $max;
    }
    
    /**
     * تعداد رقم دقیق
     */
    protected function validateDigits(string $field, $value, array $params): bool
    {
        $digits = (int) ($params[0] ?? 0);
        return is_numeric($value) && strlen((string) $value) === $digits;
    }
    
    /**
     * تعداد رقم بین
     */
    protected function validateDigitsBetween(string $field, $value, array $params): bool
    {
        $min = (int) ($params[0] ?? 0);
        $max = (int) ($params[1] ?? PHP_INT_MAX);
        $length = strlen((string) $value);
        
        return is_numeric($value) && $length >= $min && $length <= $max;
    }
    
    // ═══════════════════════════════════════════════════════════
    // قوانین اعتبارسنجی - مقایسه
    // ═══════════════════════════════════════════════════════════
    
    /**
     * بزرگتر از
     */
    protected function validateGt(string $field, $value, array $params): bool
    {
        $other = $params[0] ?? null;
        
        // اگر نام فیلد است
        if ($this->hasValue($other)) {
            $otherValue = $this->getValue($other);
            return $this->getSize($value, 'numeric') > $this->getSize($otherValue, 'numeric');
        }
        
        // اگر مقدار مستقیم است
        return $this->getSize($value, 'numeric') > (float) $other;
    }
    
    /**
     * بزرگتر یا مساوی
     */
    protected function validateGte(string $field, $value, array $params): bool
    {
        $other = $params[0] ?? null;
        
        if ($this->hasValue($other)) {
            $otherValue = $this->getValue($other);
            return $this->getSize($value, 'numeric') >= $this->getSize($otherValue, 'numeric');
        }
        
        return $this->getSize($value, 'numeric') >= (float) $other;
    }
    
    /**
     * کوچکتر از
     */
    protected function validateLt(string $field, $value, array $params): bool
    {
        $other = $params[0] ?? null;
        
        if ($this->hasValue($other)) {
            $otherValue = $this->getValue($other);
            return $this->getSize($value, 'numeric') < $this->getSize($otherValue, 'numeric');
        }
        
        return $this->getSize($value, 'numeric') < (float) $other;
    }
    
    /**
     * کوچکتر یا مساوی
     */
    protected function validateLte(string $field, $value, array $params): bool
    {
        $other = $params[0] ?? null;
        
        if ($this->hasValue($other)) {
            $otherValue = $this->getValue($other);
            return $this->getSize($value, 'numeric') <= $this->getSize($otherValue, 'numeric');
        }
        
        return $this->getSize($value, 'numeric') <= (float) $other;
    }
    
    /**
     * تأیید مطابقت (password_confirmation)
     */
    protected function validateConfirmed(string $field, $value, array $params): bool
    {
        $confirmField = $field . '_confirmation';
        return $value === $this->getValue($confirmField);
    }
    
    /**
     * یکسان با فیلد دیگر
     */
    protected function validateSame(string $field, $value, array $params): bool
    {
        $other = $params[0] ?? null;
        return $value === $this->getValue($other);
    }
    
    /**
     * متفاوت با فیلد دیگر
     */
    protected function validateDifferent(string $field, $value, array $params): bool
    {
        $other = $params[0] ?? null;
        return $value !== $this->getValue($other);
    }
    
    // ═══════════════════════════════════════════════════════════
    // قوانین اعتبارسنجی - فرمت و الگو
    // ═══════════════════════════════════════════════════════════
    
    /**
     * ایمیل
     */
    protected function validateEmail(string $field, $value, array $params): bool
    {
        $mode = $params[0] ?? 'filter';
        
        switch ($mode) {
            case 'strict':
                return (bool) preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $value);
            case 'dns':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return false;
                }
                $domain = substr($value, strpos($value, '@') + 1);
                return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
            case 'spoof':
                // بررسی ساده spoofing
                return filter_var($value, FILTER_VALIDATE_EMAIL) && !preg_match('/[\x00-\x1F\x7F]/', $value);
            case 'filter':
            default:
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
        }
    }
    
    /**
     * URL
     */
    protected function validateUrl(string $field, $value, array $params): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * URL فعال
     */
    protected function validateActiveUrl(string $field, $value, array $params): bool
    {
        if (!$this->validateUrl($field, $value, [])) {
            return false;
        }
        
        $parsed = parse_url($value);
        if (!isset($parsed['host'])) {
            return false;
        }
        
        return checkdnsrr($parsed['host'], 'A') || checkdnsrr($parsed['host'], 'AAAA');
    }
    
    /**
     * IP
     */
    protected function validateIp(string $field, $value, array $params): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }
    
    /**
     * IPv4
     */
    protected function validateIpv4(string $field, $value, array $params): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }
    
    /**
     * IPv6
     */
    protected function validateIpv6(string $field, $value, array $params): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }
    
    /**
     * UUID
     */
    protected function validateUuid(string $field, $value, array $params): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value);
    }
    
    /**
     * Regex
     */
    protected function validateRegex(string $field, $value, array $params): bool
    {
        if (!isset($params[0])) {
            return false;
        }
        
        return (bool) preg_match($params[0], $value);
    }
    
    /**
     * Not Regex
     */
    protected function validateNotRegex(string $field, $value, array $params): bool
    {
        return !$this->validateRegex($field, $value, $params);
    }
    
    // ═══════════════════════════════════════════════════════════
    // قوانین اعتبارسنجی - رشته‌ها
    // ═══════════════════════════════════════════════════════════
    
    /**
     * فقط حروف
     */
    protected function validateAlpha(string $field, $value, array $params): bool
    {
        return (bool) preg_match('/^[\pL\pM]+$/u', $value);
    }
    
    /**
     * فقط حروف و اعداد
     */
    protected function validateAlphaNum(string $field, $value, array $params): bool
    {
        return (bool) preg_match('/^[\pL\pM\pN]+$/u', $value);
    }
    
    /**
     * حروف، اعداد، خط تیره و زیرخط
     */
    protected function validateAlphaDash(string $field, $value, array $params): bool
    {
        return (bool) preg_match('/^[\pL\pM\pN_-]+$/u', $value);
    }
    
    /**
     * حروف و فاصله
     */
    protected function validateAlphaSpace(string $field, $value, array $params): bool
    {
        return (bool) preg_match('/^[\pL\pM\s]+$/u', $value);
    }
    
    /**
     * اسلاگ URL
     */
    protected function validateSlug(string $field, $value, array $params): bool
    {
        return (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value);
    }
    
    /**
     * شروع با
     */
    protected function validateStartsWith(string $field, $value, array $params): bool
    {
        foreach ($params as $prefix) {
            if (str_starts_with((string) $value, $prefix)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * پایان با
     */
    protected function validateEndsWith(string $field, $value, array $params): bool
    {
        foreach ($params as $suffix) {
            if (str_ends_with((string) $value, $suffix)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * شروع نشود با
     */
    protected function validateDoesntStartWith(string $field, $value, array $params): bool
    {
        return !$this->validateStartsWith($field, $value, $params);
    }
    
    /**
     * پایان نیابد با
     */
    protected function validateDoesntEndWith(string $field, $value, array $params): bool
    {
        return !$this->validateEndsWith($field, $value, $params);
    }
    
    /**
     * شامل باشد
     */
    protected function validateContains(string $field, $value, array $params): bool
    {
        foreach ($params as $needle) {
            if (str_contains((string) $value, $needle)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * شامل نباشد
     */
    protected function validateNotContains(string $field, $value, array $params): bool
    {
        foreach ($params as $needle) {
            if (str_contains((string) $value, $needle)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * حروف کوچک
     */
    protected function validateLowercase(string $field, $value, array $params): bool
    {
        return $value === mb_strtolower($value, 'UTF-8');
    }
    
    /**
     * حروف بزرگ
     */
    protected function validateUppercase(string $field, $value, array $params): bool
    {
        return $value === mb_strtoupper($value, 'UTF-8');
    }
    
    // ═══════════════════════════════════════════════════════════
    // قوانین اعتبارسنجی - لیست و انتخاب
    // ═══════════════════════════════════════════════════════════
    
    /**
     * یکی از مقادیر مجاز
     */
    protected function validateIn(string $field, $value, array $params): bool
    {
        return in_array($value, $params, false) || in_array((string) $value, $params, true);
    }
    
    /**
     * هیچکدام از مقادیر غیرمجاز
     */
    protected function validateNotIn(string $field, $value, array $params): bool
    {
        return !$this->validateIn($field, $value, $params);
    }
    
    /**
     * پذیرفته شده
     */
    protected function validateAccepted(string $field, $value, array $params): bool
    {
        $acceptable = ['yes', 'on', '1', 1, true, 'true'];
        return in_array($value, $acceptable, true);
    }
    
    /**
     * پذیرفته شده اگر...
     */
    protected function validateAcceptedIf(string $field, $value, array $params): bool
    {
        if (count($params) < 2) {
            return true;
        }
        
        $otherField = $params[0];
        $otherValues = array_slice($params, 1);
        $actualValue = $this->getValue($otherField);
        
        if (in_array($actualValue, $otherValues, true) || in_array((string)$actualValue, $otherValues, true)) {
            return $this->validateAccepted($field, $value, []);
        }
        
        return true;
    }
    
    /**
     * رد شده اگر...
     */
    protected function validateDeclinedIf(string $field, $value, array $params): bool
    {
        if (count($params) < 2) {
            return true;
        }
        
        $otherField = $params[0];
        $otherValues = array_slice($params, 1);
        $actualValue = $this->getValue($otherField);
        
        if (in_array($actualValue, $otherValues, true) || in_array((string)$actualValue, $otherValues, true)) {
            return $this->validateDeclined($field, $value, []);
        }
        
        return true;
    }
    
    /**
     * ممنوع
     */
    protected function validateProhibited(string $field, $value, array $params): bool
    {
        return $this->isEmpty($value);
    }
    
    /**
     * ممنوع اگر...
     */
    protected function validateProhibitedIf(string $field, $value, array $params): bool
    {
        if (count($params) < 2) {
            return true;
        }
        
        $otherField = $params[0];
        $otherValues = array_slice($params, 1);
        $actualValue = $this->getValue($otherField);
        
        if (in_array($actualValue, $otherValues, true) || in_array((string)$actualValue, $otherValues, true)) {
            return $this->validateProhibited($field, $value, []);
        }
        
        return true;
    }
    
    /**
     * ممنوع مگر...
     */
    protected function validateProhibitedUnless(string $field, $value, array $params): bool
    {
        if (count($params) < 2) {
            return true;
        }
        
        $otherField = $params[0];
        $otherValues = array_slice($params, 1);
        $actualValue = $this->getValue($otherField);
        
        if (!in_array($actualValue, $otherValues, true) && !in_array((string)$actualValue, $otherValues, true)) {
            return $this->validateProhibited($field, $value, []);
        }
        
        return true;
    }
    
    // ═══════════════════════════════════════════════════════════
    // قوانین اعتبارسنجی - تاریخ و زمان
    // ═══════════════════════════════════════════════════════════
    
    /**
     * تاریخ معتبر
     */
    protected function validateDate(string $field, $value, array $params): bool
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }
        
        $timestamp = strtotime($value);
        return $timestamp !== false;
    }
    
    /**
     * تاریخ با فرمت خاص
     */
    protected function validateDateFormat(string $field, $value, array $params): bool
    {
        $format = $params[0] ?? 'Y-m-d';
        $date = \DateTime::createFromFormat($format, $value);
        
        return $date && $date->format($format) === $value;
    }
    
    /**
     * تاریخ برابر با
     */
    protected function validateDateEquals(string $field, $value, array $params): bool
    {
        $compareDate = $params[0] ?? 'today';
        
        // اگر نام فیلد است
        if ($this->hasValue($compareDate)) {
            $compareDate = $this->getValue($compareDate);
        }
        
        $valueTime = strtotime($value);
        $compareTime = strtotime($compareDate);
        
        if ($valueTime === false || $compareTime === false) {
            return false;
        }
        
        return date('Y-m-d', $valueTime) === date('Y-m-d', $compareTime);
    }
    
    /**
     * تاریخ قبل از
     */
    protected function validateBefore(string $field, $value, array $params): bool
    {
        $compareDate = $params[0] ?? 'today';
        
        if ($this->hasValue($compareDate)) {
            $compareDate = $this->getValue($compareDate);
        }
        
        $valueTime = strtotime($value);
        $compareTime = strtotime($compareDate);
        
        if ($valueTime === false || $compareTime === false) {
            return false;
        }
        
        return $valueTime < $compareTime;
    }
    
    /**
     * تاریخ قبل یا برابر
     */
    protected function validateBeforeOrEqual(string $field, $value, array $params): bool
    {
        $compareDate = $params[0] ?? 'today';
        
        if ($this->hasValue($compareDate)) {
            $compareDate = $this->getValue($compareDate);
        }
        
        $valueTime = strtotime($value);
        $compareTime = strtotime($compareDate);
        
        if ($valueTime === false || $compareTime === false) {
            return false;
        }
        
        return date('Y-m-d', $valueTime) <= date('Y-m-d', $compareTime);
    }
    
    /**
     * تاریخ بعد از
     */
    protected function validateAfter(string $field, $value, array $params): bool
    {
        $compareDate = $params[0] ?? 'today';
        
        if ($this->hasValue($compareDate)) {
            $compareDate = $this->getValue($compareDate);
        }
        
        $valueTime = strtotime($value);
        $compareTime = strtotime($compareDate);
        
        if ($valueTime === false || $compareTime === false) {
            return false;
        }
        
        return $valueTime > $compareTime;
    }
    
    /**
     * تاریخ بعد یا برابر
     */
    protected function validateAfterOrEqual(string $field, $value, array $params): bool
    {
        $compareDate = $params[0] ?? 'today';
        
        if ($this->hasValue($compareDate)) {
            $compareDate = $this->getValue($compareDate);
        }
        
        $valueTime = strtotime($value);
        $compareTime = strtotime($compareDate);
        
        if ($valueTime === false || $compareTime === false) {
            return false;
        }
        
        return date('Y-m-d', $valueTime) >= date('Y-m-d', $compareTime);
    }
    
    /**
     * تاریخ شمسی (جلالی)
     */
    protected function validateJalali(string $field, $value, array $params): bool
    {
        // فرمت پیش‌فرض: 1403/01/01
        $pattern = '/^[1-4]\d{3}\/(0[1-9]|1[0-2])\/(0[1-9]|[12]\d|3[01])$/';
        
        if (!preg_match($pattern, $value)) {
            return false;
        }
        
        [$year, $month, $day] = array_map('intval', explode('/', $value));
        
        // اعتبارسنجی تعداد روزهای ماه
        if ($month >= 1 && $month <= 6) {
            // ماه‌های اول تا ششم: 31 روز
            return $day <= 31;
        } elseif ($month >= 7 && $month <= 11) {
            // ماه‌های هفتم تا یازدهم: 30 روز
            return $day <= 30;
        } else {
            // اسفند: 29 یا 30 روز (کبیسه)
            $isLeap = $this->isJalaliLeapYear($year);
            return $day <= ($isLeap ? 30 : 29);
        }
    }
    
    /**
     * بررسی سال کبیسه شمسی
     */
    private function isJalaliLeapYear(int $year): bool
    {
        $a = 0.025;
        $b = 266;
        $leapDays0 = [1, 5, 9, 13, 17, 22, 26, 30];
        $leapDays1 = [1, 5, 9, 13, 17, 21, 26, 30];
        $leapDays2 = [1, 5, 9, 13, 17, 22, 26, 30];
        $leapDays3 = [1, 5, 9, 13, 17, 22, 26, 30];
        
        $cycle = floor($year / 2820);
        $yearInCycle = $year - $cycle * 2820;
        
        if ($yearInCycle < 474) {
            $yearInCycle += 2820;
        }
        
        $yearInCycle -= 474;
        $grand = floor($yearInCycle / 2820);
        $yearInGrand = $yearInCycle - $grand * 2820;
        
        $cycle128 = floor($yearInGrand / 128);
        $yearIn128 = $yearInGrand - $cycle128 * 128;
        
        // روش ساده‌تر
        $mod = ($year - 474) % 2820;
        $mod = ($mod + 2820) % 2820;
        
        return (($mod + 38) * 31 % 128) < 31;
    }
    
    // ═══════════════════════════════════════════════════════════
    // قوانین اعتبارسنجی - دیتابیس
    // ═══════════════════════════════════════════════════════════
    
    /**
     * یکتا در دیتابیس
     * فرمت: unique:table,column,except_id,id_column
     */
    protected function validateUnique(string $field, $value, array $params): bool
    {
        if ($this->db === null) {
            throw new \RuntimeException("برای استفاده از قانون unique باید اتصال دیتابیس تنظیم شود.");
        }
        
        $table = $params[0] ?? null;
        $column = $params[1] ?? $field;
        $exceptId = $params[2] ?? null;
        $idColumn = $params[3] ?? 'id';
        
        if (!$table) {
            throw new \InvalidArgumentException("نام جدول برای قانون unique الزامی است.");
        }
        
        // ایمن‌سازی نام جدول و ستون
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
        $idColumn = preg_replace('/[^a-zA-Z0-9_]/', '', $idColumn);
        
        $sql = "SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = ?";
        $bindings = [$value];
        
        // استثنا کردن رکورد فعلی (برای ویرایش)
        if ($exceptId !== null && $exceptId !== '') {
            $sql .= " AND `{$idColumn}` != ?";
            $bindings[] = $exceptId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        
        return (int) $stmt->fetchColumn() === 0;
    }
    
    /**
     * وجود در دیتابیس
     * فرمت: exists:table,column
     */
    protected function validateExists(string $field, $value, array $params): bool
    {
        if ($this->db === null) {
            throw new \RuntimeException("برای استفاده از قانون exists باید اتصال دیتابیس تنظیم شود.");
        }
        
        $table = $params[0] ?? null;
        $column = $params[1] ?? $field;
        
        if (!$table) {
            throw new \InvalidArgumentException("نام جدول برای قانون exists الزامی است.");
        }
        
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
        
        $sql = "SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$value]);
        
        return (int) $stmt->fetchColumn() > 0;
    }
    
    // ═══════════════════════════════════════════════════════════
    // قوانین اعتبارسنجی - فایل
    // ═══════════════════════════════════════════════════════════
    
    /**
     * فایل آپلود شده
     */
    protected function validateFile(string $field, $value, array $params): bool
    {
        if (!is_array($value)) {
            return false;
        }
        
        return isset($value['tmp_name']) && 
               is_uploaded_file($value['tmp_name']) &&
               $value['error'] === UPLOAD_ERR_OK;
    }
    
    /**
     * فایل تصویر
     */
    protected function validateImage(string $field, $value, array $params): bool
    {
        if (!$this->validateFile($field, $value, [])) {
            return false;
        }
        
        $imageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'image/bmp'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($value['tmp_name']);
        
        return in_array($mimeType, $imageTypes, true);
    }
    
    /**
     * نوع فایل (پسوند)
     */
    protected function validateMimes(string $field, $value, array $params): bool
    {
        if (!$this->validateFile($field, $value, [])) {
            return false;
        }
        
        $extension = strtolower(pathinfo($value['name'], PATHINFO_EXTENSION));
        return in_array($extension, array_map('strtolower', $params), true);
    }
    
    /**
     * نوع MIME
     */
    protected function validateMimetypes(string $field, $value, array $params): bool
    {
        if (!$this->validateFile($field, $value, [])) {
            return false;
        }
        
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($value['tmp_name']);
        
        foreach ($params as $allowedType) {
            // پشتیبانی از wildcard (مثل image/*)
            if (str_contains($allowedType, '*')) {
                $pattern = str_replace('*', '.*', $allowedType);
                if (preg_match('#^' . $pattern . '$#', $mimeType)) {
                    return true;
                }
            } elseif ($mimeType === $allowedType) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * حداکثر حجم فایل (کیلوبایت)
     */
    protected function validateMaxSize(string $field, $value, array $params): bool
    {
        if (!$this->validateFile($field, $value, [])) {
            return false;
        }
        
        $maxKb = (int) ($params[0] ?? 0);
        $fileSizeKb = $value['size'] / 1024;
        
        return $fileSizeKb <= $maxKb;
    }
    
    /**
     * حداقل حجم فایل (کیلوبایت)
     */
    protected function validateMinSize(string $field, $value, array $params): bool
    {
        if (!$this->validateFile($field, $value, [])) {
            return false;
        }
        
        $minKb = (int) ($params[0] ?? 0);
        $fileSizeKb = $value['size'] / 1024;
        
        return $fileSizeKb >= $minKb;
    }
    
    /**
     * ابعاد تصویر
     * فرمت: dimensions:min_width=100,min_height=100,max_width=1000,max_height=1000,width=500,height=500,ratio=3/2
     */
    protected function validateDimensions(string $field, $value, array $params): bool
    {
        if (!$this->validateImage($field, $value, [])) {
            return false;
        }
        
        $dimensions = getimagesize($value['tmp_name']);
        if ($dimensions === false) {
            return false;
        }
        
        [$width, $height] = $dimensions;
        
        // پارس کردن پارامترها
        $constraints = [];
        foreach ($params as $param) {
            if (strpos($param, '=') !== false) {
                [$key, $val] = explode('=', $param, 2);
                $constraints[$key] = $val;
            }
        }
        
        // بررسی محدودیت‌ها
        if (isset($constraints['width']) && $width != $constraints['width']) {
            return false;
        }
        if (isset($constraints['height']) && $height != $constraints['height']) {
            return false;
        }
        if (isset($constraints['min_width']) && $width < $constraints['min_width']) {
            return false;
        }
        if (isset($constraints['min_height']) && $height < $constraints['min_height']) {
            return false;
        }
        if (isset($constraints['max_width']) && $width > $constraints['max_width']) {
            return false;
        }
        if (isset($constraints['max_height']) && $height > $constraints['max_height']) {
            return false;
        }
        
        // بررسی نسبت تصویر
        if (isset($constraints['ratio'])) {
            $ratio = $constraints['ratio'];
            if (strpos($ratio, '/') !== false) {
                [$rw, $rh] = explode('/', $ratio);
                $expectedRatio = (float) $rw / (float) $rh;
            } else {
                $expectedRatio = (float) $ratio;
            }
            
            $actualRatio = $width / $height;
            // تلرانس 0.01
            if (abs($actualRatio - $expectedRatio) > 0.01) {
                return false;
            }
        }
        
        return true;
    }
    
    // ═══════════════════════════════════════════════════════════
    // قوانین اعتبارسنجی - ایران (فارسی)
    // ═══════════════════════════════════════════════════════════
    
    /**
     * شماره موبایل ایران
     */
    protected function validateMobile(string $field, $value, array $params): bool
    {
        // پذیرش فرمت‌های مختلف: 09123456789, +989123456789, 989123456789
        $normalized = preg_replace('/[\s\-\(\)]/', '', $value);
        
        // تبدیل اعداد فارسی/عربی به انگلیسی
        $normalized = $this->convertPersianNumbers($normalized);
        
        // حذف + از ابتدا
        $normalized = ltrim($normalized, '+');
        
        // بررسی فرمت‌های مختلف
        if (preg_match('/^09[0-9]{9}$/', $normalized)) {
            return true;
        }
        if (preg_match('/^989[0-9]{9}$/', $normalized)) {
            return true;
        }
        if (preg_match('/^9[0-9]{9}$/', $normalized)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * شماره تلفن ثابت ایران
     */
    protected function validatePhone(string $field, $value, array $params): bool
    {
        $normalized = preg_replace('/[\s\-\(\)]/', '', $value);
        $normalized = $this->convertPersianNumbers($normalized);
        
        // فرمت: 02112345678 یا 02112345678
        return (bool) preg_match('/^0[1-8][1-9][0-9]{7,8}$/', $normalized);
    }
    
    /**
     * کد ملی ایران
     */
    protected function validateNationalCode(string $field, $value, array $params): bool
    {
        // تبدیل اعداد فارسی به انگلیسی
        $code = $this->convertPersianNumbers($value);
        
        // حذف خط تیره و فاصله
        $code = preg_replace('/[\s\-]/', '', $code);
        
        // باید 10 رقم باشد
        if (!preg_match('/^[0-9]{10}$/', $code)) {
            return false;
        }
        
        // بررسی کدهای تکراری (مثل 1111111111)
        if (preg_match('/^(.)\1{9}$/', $code)) {
            return false;
        }
        
        // محاسبه رقم کنترل
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $code[$i] * (10 - $i);
        }
        
        $remainder = $sum % 11;
        $checkDigit = (int) $code[9];
        
        if ($remainder < 2) {
            return $checkDigit === $remainder;
        }
        
        return $checkDigit === (11 - $remainder);
    }
    
    /**
     * کد پستی ایران
     */
    protected function validatePostalCode(string $field, $value, array $params): bool
    {
        $normalized = $this->convertPersianNumbers($value);
        $normalized = preg_replace('/[\s\-]/', '', $normalized);
        
        // کد پستی 10 رقمی که با 0 شروع نمی‌شود
        return (bool) preg_match('/^[1-9][0-9]{9}$/', $normalized);
    }
    
    /**
     * شماره شبا
     */
    protected function validateSheba(string $field, $value, array $params): bool
    {
        // حذف فاصله و IR
        $sheba = strtoupper(preg_replace('/[\s\-]/', '', $value));
        
        // حذف IR از ابتدا اگر وجود دارد
        if (str_starts_with($sheba, 'IR')) {
            $sheba = substr($sheba, 2);
        }
        
        $sheba = $this->convertPersianNumbers($sheba);
        
        // باید 24 رقم باشد
        if (!preg_match('/^[0-9]{24}$/', $sheba)) {
            return false;
        }
        
        // الگوریتم بررسی شبا
        // انتقال IR و 2 رقم اول به انتها
        $check = $sheba . '1827'; // IR = 18, 27 (I=18, R=27)
        
        // محاسبه باقیمانده تقسیم بر 97
        $remainder = bcmod($check, '97');
        
        return $remainder === '1';
    }
    
    /**
     * شماره کارت بانکی
     */
    protected function validateCardNumber(string $field, $value, array $params): bool
    {
        // حذف فاصله و خط تیره
        $card = preg_replace('/[\s\-]/', '', $value);
        $card = $this->convertPersianNumbers($card);
        
        // باید 16 رقم باشد
        if (!preg_match('/^[0-9]{16}$/', $card)) {
            return false;
        }
        
        // الگوریتم لون (Luhn)
        $sum = 0;
        $length = strlen($card);
        
        for ($i = 0; $i < $length; $i++) {
            $digit = (int) $card[$length - 1 - $i];
            
            if ($i % 2 === 1) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            
            $sum += $digit;
        }
        
        return $sum % 10 === 0;
    }
    
    /**
     * فقط حروف فارسی
     */
    protected function validatePersian(string $field, $value, array $params): bool
    {
        // حروف فارسی و عربی و فاصله
        return (bool) preg_match('/^[\x{0600}-\x{06FF}\x{200C}\s]+$/u', $value);
    }
    
    /**
     * حروف فارسی و اعداد
     */
    protected function validatePersianNum(string $field, $value, array $params): bool
    {
        return (bool) preg_match('/^[\x{0600}-\x{06FF}\x{200C}\s0-9\x{06F0}-\x{06F9}]+$/u', $value);
    }
    
    /**
     * حروف فارسی، انگلیسی و اعداد
     */
    protected function validatePersianAlphaNum(string $field, $value, array $params): bool
    {
        return (bool) preg_match('/^[\x{0600}-\x{06FF}\x{200C}\sa-zA-Z0-9\x{06F0}-\x{06F9}]+$/u', $value);
    }
    
    /**
     * تبدیل اعداد فارسی/عربی به انگلیسی
     */
    private function convertPersianNumbers(string $value): string
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        
        $value = str_replace($persian, $english, $value);
        $value = str_replace($arabic, $english, $value);
        
        return $value;
    }
    
    // ═══════════════════════════════════════════════════════════
    // قوانین اعتبارسنجی - رمز عبور
    // ═══════════════════════════════════════════════════════════
    
    /**
     * رمز عبور معمولی
     * حداقل 8 کاراکتر، شامل حروف بزرگ، کوچک و عدد
     */
    protected function validatePassword(string $field, $value, array $params): bool
    {
        if (strlen($value) < 8) {
            return false;
        }
        
        // باید شامل حرف کوچک باشد
        if (!preg_match('/[a-z]/', $value)) {
            return false;
        }
        
        // باید شامل حرف بزرگ باشد
        if (!preg_match('/[A-Z]/', $value)) {
            return false;
        }
        
        // باید شامل عدد باشد
        if (!preg_match('/[0-9]/', $value)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * رمز عبور قوی
     * حداقل 12 کاراکتر، شامل حروف بزرگ، کوچک، عدد و نماد
     */
    protected function validateStrongPassword(string $field, $value, array $params): bool
    {
        if (strlen($value) < 12) {
            return false;
        }
        
        if (!preg_match('/[a-z]/', $value)) {
            return false;
        }
        
        if (!preg_match('/[A-Z]/', $value)) {
            return false;
        }
        
        if (!preg_match('/[0-9]/', $value)) {
            return false;
        }
        
        // باید شامل نماد باشد
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{}|;:\'",.<>\/?\\\\`~]/', $value)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * رمز عبور فعلی صحیح است
     * نیاز به callback سفارشی دارد
     */
    protected function validateCurrentPassword(string $field, $value, array $params): bool
    {
        // این قانون باید توسط برنامه‌نویس سفارشی‌سازی شود
        // به صورت پیش‌فرض همیشه true برمی‌گرداند
        // می‌توان از Validator::extend برای پیاده‌سازی استفاده کرد
        return true;
    }
}

// ═══════════════════════════════════════════════════════════════════
// کلاس ValidationErrorBag - مدیریت خطاها
// ═══════════════════════════════════════════════════════════════════

class ValidationErrorBag implements \Countable, \IteratorAggregate, \JsonSerializable
{
    private array $messages = [];
    
    public function __construct(array $messages = [])
    {
        $this->messages = $messages;
    }
    
    /**
     * آیا خطایی وجود دارد؟
     */
    public function any(): bool
    {
        return count($this->messages) > 0;
    }
    
    /**
     * آیا خطایی وجود ندارد؟
     */
    public function isEmpty(): bool
    {
        return count($this->messages) === 0;
    }
    
    /**
     * آیا فیلد خطا دارد؟
     */
    public function has(string $field): bool
    {
        return isset($this->messages[$field]) && count($this->messages[$field]) > 0;
    }
    
    /**
     * دریافت اولین خطای یک فیلد
     */
    public function first(?string $field = null): ?string
    {
        if ($field === null) {
            foreach ($this->messages as $messages) {
                return $messages[0] ?? null;
            }
            return null;
        }
        
        return $this->messages[$field][0] ?? null;
    }
    
    /**
     * دریافت تمام خطاهای یک فیلد
     */
    public function get(string $field): array
    {
        return $this->messages[$field] ?? [];
    }
    
    /**
     * دریافت تمام خطاها
     */
    public function all(): array
    {
        $all = [];
        foreach ($this->messages as $messages) {
            foreach ($messages as $message) {
                $all[] = $message;
            }
        }
        return $all;
    }
    
    /**
     * دریافت خطاها به صورت آرایه
     */
    public function toArray(): array
    {
        return $this->messages;
    }
    
    /**
     * دریافت خطاها به صورت JSON
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->messages, $options | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * دریافت فیلدهای دارای خطا
     */
    public function keys(): array
    {
        return array_keys($this->messages);
    }
    
    /**
     * افزودن خطا
     */
    public function add(string $field, string $message): self
    {
        if (!isset($this->messages[$field])) {
            $this->messages[$field] = [];
        }
        $this->messages[$field][] = $message;
        return $this;
    }
    
    /**
     * ادغام خطاها
     */
    public function merge(array $messages): self
    {
        foreach ($messages as $field => $fieldMessages) {
            foreach ((array) $fieldMessages as $message) {
                $this->add($field, $message);
            }
        }
        return $this;
    }
    
    /**
     * تعداد خطاها
     */
    public function count(): int
    {
        return count($this->all());
    }
    
    /**
     * تعداد فیلدهای دارای خطا
     */
    public function fieldCount(): int
    {
        return count($this->messages);
    }
    
    /**
     * Iterator برای foreach
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->messages);
    }
    
    /**
     * JSON Serializable
     */
    public function jsonSerialize(): array
    {
        return $this->messages;
    }
    
    /**
     * تبدیل به رشته
     */
    public function __toString(): string
    {
        return implode("\n", $this->all());
    }
}

// ═══════════════════════════════════════════════════════════════════
// کلاس ValidatedInput - داده‌های اعتبارسنجی شده
// ═══════════════════════════════════════════════════════════════════

class ValidatedInput implements \ArrayAccess, \Countable, \IteratorAggregate
{
    private array $data;
    
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }
    
    /**
     * دریافت مقدار
     */
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }
    
    /**
     * دریافت تمام داده‌ها
     */
    public function all(): array
    {
        return $this->data;
    }
    
    /**
     * فقط کلیدهای مشخص شده
     */
    public function only(array $keys): array
    {
        return array_intersect_key($this->data, array_flip($keys));
    }
    
    /**
     * همه به جز کلیدهای مشخص شده
     */
    public function except(array $keys): array
    {
        return array_diff_key($this->data, array_flip($keys));
    }
    
    /**
     * آیا کلید وجود دارد؟
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }
    
    /**
     * آیا کلید مقدار دارد؟
     */
    public function filled(string $key): bool
    {
        $value = $this->data[$key] ?? null;
        return $value !== null && $value !== '' && $value !== [];
    }
    
    /**
     * ادغام داده‌ها
     */
    public function merge(array $data): self
    {
        return new self(array_merge($this->data, $data));
    }
    
    /**
     * تبدیل به آرایه
     */
    public function toArray(): array
    {
        return $this->data;
    }
    
    /**
     * دریافت به صورت object
     */
    public function toObject(): object
    {
        return (object) $this->data;
    }
    
    // ArrayAccess
    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }
    
    public function offsetGet($offset): mixed
    {
        return $this->data[$offset] ?? null;
    }
    
    public function offsetSet($offset, $value): void
    {
        $this->data[$offset] = $value;
    }
    
    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }
    
    // Countable
    public function count(): int
    {
        return count($this->data);
    }
    
    // IteratorAggregate
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->data);
    }
    
    // Magic methods
    public function __get(string $key)
    {
        return $this->data[$key] ?? null;
    }
    
    public function __set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }
    
    public function __isset(string $key): bool
    {
        return isset($this->data[$key]);
    }
}

// ═══════════════════════════════════════════════════════════════════
// کلاس ValidationException - استثنای اعتبارسنجی
// ═══════════════════════════════════════════════════════════════════

class ValidationException extends \Exception
{
    public Validator $validator;
    public ValidationErrorBag $errors;
    public string $redirectTo = '';
    
    public function __construct(Validator $validator, ?string $message = null)
    {
        parent::__construct($message ?? 'داده‌های ورودی نامعتبر است.');
        
        $this->validator = $validator;
        $this->errors = $validator->errors();
    }
    
    /**
     * دریافت خطاها
     */
    public function errors(): ValidationErrorBag
    {
        return $this->errors;
    }
    
    /**
     * تنظیم URL ریدایرکت
     */
    public function redirectTo(string $url): self
    {
        $this->redirectTo = $url;
        return $this;
    }
    
    /**
     * دریافت خطاها به صورت آرایه
     */
    public function getErrors(): array
    {
        return $this->errors->toArray();
    }
    
    /**
     * پاسخ JSON
     */
    public function toResponse(): array
    {
        return [
            'success' => false,
            'message' => $this->getMessage(),
            'errors' => $this->errors->toArray()
        ];
    }
}
