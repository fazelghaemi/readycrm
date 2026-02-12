<?php
/**
 * ReadyCRM v2 - Logger (سیستم ثبت وقایع)
 * 
 * فایل: private/core/Logger.php
 * 
 * ویژگی‌ها:
 * - سطوح استاندارد PSR-3 (emergency, alert, critical, error, warning, notice, info, debug)
 * - چندین کانال خروجی (فایل، دیتابیس، سیستم عامل، ایمیل)
 * - چرخش خودکار فایل‌های لاگ (Log Rotation)
 * - فرمت‌دهی سفارشی
 * - Context و Metadata
 * - Stack Trace برای خطاها
 * - فیلتر بر اساس سطح
 * 
 * @package ReadyCRM
 * @version 2.0
 * @author Vahid Valizadeh
 */

namespace App\Core;

use PDO;

// ═══════════════════════════════════════════════════════════════════
// سطوح لاگ (PSR-3 Compatible)
// ═══════════════════════════════════════════════════════════════════

class LogLevel
{
    public const EMERGENCY = 'emergency'; // سیستم غیرقابل استفاده
    public const ALERT     = 'alert';     // نیاز به اقدام فوری
    public const CRITICAL  = 'critical';  // شرایط بحرانی
    public const ERROR     = 'error';     // خطاهای زمان اجرا
    public const WARNING   = 'warning';   // هشدار (نه خطا)
    public const NOTICE    = 'notice';    // رویدادهای عادی ولی مهم
    public const INFO      = 'info';      // اطلاعات عمومی
    public const DEBUG     = 'debug';     // اطلاعات دیباگ
    
    /**
     * اولویت سطوح (عدد کمتر = اهمیت بیشتر)
     */
    public const PRIORITIES = [
        self::EMERGENCY => 0,
        self::ALERT     => 1,
        self::CRITICAL  => 2,
        self::ERROR     => 3,
        self::WARNING   => 4,
        self::NOTICE    => 5,
        self::INFO      => 6,
        self::DEBUG     => 7,
    ];
    
    /**
     * برچسب‌های فارسی
     */
    public const LABELS_FA = [
        self::EMERGENCY => 'بحران',
        self::ALERT     => 'هشدار فوری',
        self::CRITICAL  => 'بحرانی',
        self::ERROR     => 'خطا',
        self::WARNING   => 'هشدار',
        self::NOTICE    => 'توجه',
        self::INFO      => 'اطلاعات',
        self::DEBUG     => 'دیباگ',
    ];
    
    /**
     * رنگ‌ها برای CLI
     */
    public const COLORS = [
        self::EMERGENCY => "\033[1;37;41m", // سفید روی قرمز
        self::ALERT     => "\033[1;31m",    // قرمز پررنگ
        self::CRITICAL  => "\033[0;31m",    // قرمز
        self::ERROR     => "\033[0;91m",    // قرمز روشن
        self::WARNING   => "\033[0;33m",    // زرد
        self::NOTICE    => "\033[0;36m",    // فیروزه‌ای
        self::INFO      => "\033[0;32m",    // سبز
        self::DEBUG     => "\033[0;90m",    // خاکستری
    ];
    
    public const COLOR_RESET = "\033[0m";
    
    /**
     * آیا سطح معتبر است؟
     */
    public static function isValid(string $level): bool
    {
        return isset(self::PRIORITIES[$level]);
    }
    
    /**
     * دریافت اولویت سطح
     */
    public static function getPriority(string $level): int
    {
        return self::PRIORITIES[$level] ?? 7;
    }
    
    /**
     * تمام سطوح
     */
    public static function all(): array
    {
        return array_keys(self::PRIORITIES);
    }
}

// ═══════════════════════════════════════════════════════════════════
// اینترفیس LogHandler - رابط برای هندلرها
// ═══════════════════════════════════════════════════════════════════

interface LogHandlerInterface
{
    /**
     * ثبت یک رکورد لاگ
     */
    public function handle(array $record): bool;
    
    /**
     * آیا این هندلر این سطح را پردازش می‌کند؟
     */
    public function isHandling(string $level): bool;
    
    /**
     * بستن هندلر
     */
    public function close(): void;
}

// ═══════════════════════════════════════════════════════════════════
// کلاس اصلی Logger
// ═══════════════════════════════════════════════════════════════════

class Logger
{
    private string $channel;
    private array $handlers = [];
    private array $processors = [];
    private array $context = [];
    private static ?Logger $instance = null;
    
    /**
     * سازنده
     */
    public function __construct(string $channel = 'app')
    {
        $this->channel = $channel;
    }
    
    /**
     * دریافت نمونه Singleton
     */
    public static function getInstance(string $channel = 'app'): self
    {
        if (self::$instance === null || self::$instance->channel !== $channel) {
            self::$instance = new self($channel);
        }
        return self::$instance;
    }
    
    /**
     * ایجاد لاگر جدید با کانال مشخص
     */
    public static function channel(string $channel): self
    {
        return new self($channel);
    }
    
    /**
     * افزودن هندلر
     */
    public function pushHandler(LogHandlerInterface $handler): self
    {
        array_unshift($this->handlers, $handler);
        return $this;
    }
    
    /**
     * افزودن پردازنده
     */
    public function pushProcessor(callable $processor): self
    {
        array_unshift($this->processors, $processor);
        return $this;
    }
    
    /**
     * تنظیم context پیش‌فرض
     */
    public function withContext(array $context): self
    {
        $this->context = array_merge($this->context, $context);
        return $this;
    }
    
    /**
     * دریافت نام کانال
     */
    public function getName(): string
    {
        return $this->channel;
    }
    
    // ═══════════════════════════════════════════════════════════
    // متدهای ثبت لاگ (PSR-3)
    // ═══════════════════════════════════════════════════════════
    
    /**
     * بحران - سیستم غیرقابل استفاده
     */
    public function emergency(string $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }
    
    /**
     * هشدار فوری - نیاز به اقدام فوری
     */
    public function alert(string $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }
    
    /**
     * بحرانی - شرایط بحرانی
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }
    
    /**
     * خطا - خطاهای زمان اجرا
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }
    
    /**
     * هشدار - هشدار (نه خطا)
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }
    
    /**
     * توجه - رویدادهای عادی ولی مهم
     */
    public function notice(string $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }
    
    /**
     * اطلاعات - اطلاعات عمومی
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }
    
    /**
     * دیباگ - اطلاعات دیباگ
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }
    
    /**
     * ثبت لاگ با سطح دلخواه
     */
    public function log(string $level, string $message, array $context = []): void
    {
        if (!LogLevel::isValid($level)) {
            throw new \InvalidArgumentException("سطح لاگ نامعتبر: {$level}");
        }
        
        // ساخت رکورد
        $record = [
            'channel'    => $this->channel,
            'level'      => $level,
            'level_name' => strtoupper($level),
            'message'    => $message,
            'context'    => array_merge($this->context, $context),
            'datetime'   => new \DateTimeImmutable(),
            'extra'      => [],
        ];
        
        // افزودن اطلاعات اضافی
        $record['extra'] = $this->addExtraInfo($record);
        
        // اعمال پردازنده‌ها
        foreach ($this->processors as $processor) {
            $record = $processor($record);
        }
        
        // ارسال به هندلرها
        foreach ($this->handlers as $handler) {
            if ($handler->isHandling($level)) {
                $handler->handle($record);
            }
        }
    }
    
    /**
     * افزودن اطلاعات اضافی به رکورد
     */
    private function addExtraInfo(array $record): array
    {
        $extra = $record['extra'] ?? [];
        
        // اطلاعات درخواست
        if (php_sapi_name() !== 'cli') {
            $extra['ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $extra['method'] = $_SERVER['REQUEST_METHOD'] ?? 'unknown';
            $extra['uri'] = $_SERVER['REQUEST_URI'] ?? 'unknown';
            $extra['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        }
        
        // اطلاعات کاربر (اگر لاگین کرده)
        if (isset($_SESSION['user_id'])) {
            $extra['user_id'] = $_SESSION['user_id'];
            $extra['username'] = $_SESSION['username'] ?? 'unknown';
        }
        
        // اطلاعات حافظه و زمان اجرا
        $extra['memory_usage'] = memory_get_usage(true);
        $extra['memory_peak'] = memory_get_peak_usage(true);
        
        if (defined('APP_START_TIME')) {
            $extra['execution_time'] = microtime(true) - APP_START_TIME;
        }
        
        return $extra;
    }
    
    /**
     * ثبت استثنا
     */
    public function exception(\Throwable $e, array $context = []): void
    {
        $context['exception'] = [
            'class'   => get_class($e),
            'message' => $e->getMessage(),
            'code'    => $e->getCode(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => $e->getTraceAsString(),
        ];
        
        if ($e->getPrevious()) {
            $context['exception']['previous'] = [
                'class'   => get_class($e->getPrevious()),
                'message' => $e->getPrevious()->getMessage(),
            ];
        }
        
        $this->error($e->getMessage(), $context);
    }
    
    /**
     * ثبت کوئری دیتابیس
     */
    public function query(string $sql, array $bindings = [], float $time = 0): void
    {
        $this->debug('Database Query', [
            'sql'      => $sql,
            'bindings' => $bindings,
            'time_ms'  => round($time * 1000, 2),
        ]);
    }
    
    /**
     * ثبت شروع و پایان یک عملیات
     */
    public function timing(string $operation, callable $callback, array $context = [])
    {
        $start = microtime(true);
        
        try {
            $result = $callback();
            $duration = microtime(true) - $start;
            
            $this->info("عملیات {$operation} با موفقیت انجام شد", array_merge($context, [
                'duration_ms' => round($duration * 1000, 2),
            ]));
            
            return $result;
        } catch (\Throwable $e) {
            $duration = microtime(true) - $start;
            
            $this->error("عملیات {$operation} با خطا مواجه شد", array_merge($context, [
                'duration_ms' => round($duration * 1000, 2),
                'error'       => $e->getMessage(),
            ]));
            
            throw $e;
        }
    }
    
    /**
     * بستن همه هندلرها
     */
    public function close(): void
    {
        foreach ($this->handlers as $handler) {
            $handler->close();
        }
    }
    
    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->close();
    }
}

// ═══════════════════════════════════════════════════════════════════
// هندلر پایه (Abstract)
// ═══════════════════════════════════════════════════════════════════

abstract class AbstractLogHandler implements LogHandlerInterface
{
    protected string $minLevel;
    protected ?LogFormatterInterface $formatter = null;
    protected bool $bubble = true;
    
    public function __construct(string $minLevel = LogLevel::DEBUG, bool $bubble = true)
    {
        $this->minLevel = $minLevel;
        $this->bubble = $bubble;
    }
    
    /**
     * آیا این سطح پردازش می‌شود؟
     */
    public function isHandling(string $level): bool
    {
        return LogLevel::getPriority($level) <= LogLevel::getPriority($this->minLevel);
    }
    
    /**
     * تنظیم فرمت‌دهنده
     */
    public function setFormatter(LogFormatterInterface $formatter): self
    {
        $this->formatter = $formatter;
        return $this;
    }
    
    /**
     * دریافت فرمت‌دهنده
     */
    public function getFormatter(): LogFormatterInterface
    {
        if ($this->formatter === null) {
            $this->formatter = $this->getDefaultFormatter();
        }
        return $this->formatter;
    }
    
    /**
     * فرمت‌دهنده پیش‌فرض
     */
    abstract protected function getDefaultFormatter(): LogFormatterInterface;
    
    /**
     * بستن هندلر
     */
    public function close(): void
    {
        // پیاده‌سازی پیش‌فرض - خالی
    }
}

// ═══════════════════════════════════════════════════════════════════
// اینترفیس فرمت‌دهنده
// ═══════════════════════════════════════════════════════════════════

interface LogFormatterInterface
{
    /**
     * فرمت‌دهی یک رکورد
     */
    public function format(array $record): string;
    
    /**
     * فرمت‌دهی چند رکورد
     */
    public function formatBatch(array $records): string;
}

// ═══════════════════════════════════════════════════════════════════
// فرمت‌دهنده خطی (Line Formatter)
// ═══════════════════════════════════════════════════════════════════

class LineFormatter implements LogFormatterInterface
{
    private string $format;
    private string $dateFormat;
    private bool $allowInlineLineBreaks;
    private bool $includeStacktraces;
    
    public const DEFAULT_FORMAT = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
    public const DEFAULT_DATE_FORMAT = 'Y-m-d H:i:s';
    
    public function __construct(
        ?string $format = null,
        ?string $dateFormat = null,
        bool $allowInlineLineBreaks = false,
        bool $includeStacktraces = false
    ) {
        $this->format = $format ?? self::DEFAULT_FORMAT;
        $this->dateFormat = $dateFormat ?? self::DEFAULT_DATE_FORMAT;
        $this->allowInlineLineBreaks = $allowInlineLineBreaks;
        $this->includeStacktraces = $includeStacktraces;
    }
    
    /**
     * فرمت‌دهی رکورد
     */
    public function format(array $record): string
    {
        $output = $this->format;
        
        // جایگزینی متغیرها
        $output = str_replace('%datetime%', $record['datetime']->format($this->dateFormat), $output);
        $output = str_replace('%channel%', $record['channel'], $output);
        $output = str_replace('%level_name%', $record['level_name'], $output);
        $output = str_replace('%message%', $this->normalizeMessage($record['message']), $output);
        
        // Context
        $context = $record['context'];
        if (empty($context)) {
            $output = str_replace('%context%', '', $output);
        } else {
            $output = str_replace('%context%', $this->stringify($context), $output);
        }
        
        // Extra
        $extra = $record['extra'];
        if (empty($extra)) {
            $output = str_replace('%extra%', '', $output);
        } else {
            $output = str_replace('%extra%', $this->stringify($extra), $output);
        }
        
        // پاک‌سازی فضاهای اضافی
        $output = preg_replace('/\s+/', ' ', $output);
        $output = trim($output) . "\n";
        
        return $output;
    }
    
    /**
     * فرمت‌دهی چند رکورد
     */
    public function formatBatch(array $records): string
    {
        $output = '';
        foreach ($records as $record) {
            $output .= $this->format($record);
        }
        return $output;
    }
    
    /**
     * نرمال‌سازی پیام
     */
    private function normalizeMessage(string $message): string
    {
        if (!$this->allowInlineLineBreaks) {
            $message = str_replace(["\r\n", "\r", "\n"], ' ', $message);
        }
        return $message;
    }
    
    /**
     * تبدیل آرایه به رشته
     */
    private function stringify($data): string
    {
        if (is_array($data) || is_object($data)) {
            return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return (string) $data;
    }
}

// ═══════════════════════════════════════════════════════════════════
// فرمت‌دهنده JSON
// ═══════════════════════════════════════════════════════════════════

class JsonFormatter implements LogFormatterInterface
{
    private bool $prettyPrint;
    private bool $appendNewline;
    
    public function __construct(bool $prettyPrint = false, bool $appendNewline = true)
    {
        $this->prettyPrint = $prettyPrint;
        $this->appendNewline = $appendNewline;
    }
    
    /**
     * فرمت‌دهی رکورد
     */
    public function format(array $record): string
    {
        $data = [
            'datetime' => $record['datetime']->format('c'),
            'channel'  => $record['channel'],
            'level'    => $record['level'],
            'message'  => $record['message'],
            'context'  => $record['context'],
            'extra'    => $record['extra'],
        ];
        
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        if ($this->prettyPrint) {
            $flags |= JSON_PRETTY_PRINT;
        }
        
        $json = json_encode($data, $flags);
        
        return $this->appendNewline ? $json . "\n" : $json;
    }
    
    /**
     * فرمت‌دهی چند رکورد
     */
    public function formatBatch(array $records): string
    {
        $output = '';
        foreach ($records as $record) {
            $output .= $this->format($record);
        }
        return $output;
    }
}

// ═══════════════════════════════════════════════════════════════════
// هندلر فایل (File Handler)
// ═══════════════════════════════════════════════════════════════════

class FileHandler extends AbstractLogHandler
{
    private string $filename;
    private ?string $filenameFormat;
    private ?string $dateFormat;
    private int $maxFiles;
    private int $maxFileSize; // بایت
    private int $filePermission;
    private ?resource $stream = null;
    private string $currentFilename;
    
    public function __construct(
        string $filename,
        string $minLevel = LogLevel::DEBUG,
        bool $bubble = true,
        int $maxFiles = 30,
        int $maxFileSize = 10485760, // 10MB
        int $filePermission = 0644
    ) {
        parent::__construct($minLevel, $bubble);
        
        $this->filename = $filename;
        $this->maxFiles = $maxFiles;
        $this->maxFileSize = $maxFileSize;
        $this->filePermission = $filePermission;
        $this->currentFilename = $filename;
    }
    
    /**
     * پردازش رکورد
     */
    public function handle(array $record): bool
    {
        if (!$this->isHandling($record['level'])) {
            return $this->bubble;
        }
        
        // بررسی چرخش فایل
        $this->rotate();
        
        // باز کردن فایل
        if ($this->stream === null) {
            $this->openFile();
        }
        
        // نوشتن
        $formatted = $this->getFormatter()->format($record);
        fwrite($this->stream, $formatted);
        
        return $this->bubble;
    }
    
    /**
     * باز کردن فایل
     */
    private function openFile(): void
    {
        $dir = dirname($this->currentFilename);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $this->stream = fopen($this->currentFilename, 'a');
        
        if ($this->stream === false) {
            throw new \RuntimeException("امکان باز کردن فایل لاگ وجود ندارد: {$this->currentFilename}");
        }
        
        // تنظیم دسترسی
        chmod($this->currentFilename, $this->filePermission);
    }
    
    /**
     * چرخش فایل لاگ
     */
    private function rotate(): void
    {
        if (!file_exists($this->currentFilename)) {
            return;
        }
        
        // بررسی حجم فایل
        if (filesize($this->currentFilename) < $this->maxFileSize) {
            return;
        }
        
        // بستن فایل فعلی
        if ($this->stream !== null) {
            fclose($this->stream);
            $this->stream = null;
        }
        
        // چرخش فایل‌ها
        $this->rotateFiles();
    }
    
    /**
     * چرخش فایل‌های قدیمی
     */
    private function rotateFiles(): void
    {
        // حذف قدیمی‌ترین فایل
        $oldestFile = $this->filename . '.' . $this->maxFiles;
        if (file_exists($oldestFile)) {
            unlink($oldestFile);
        }
        
        // جابجایی فایل‌ها
        for ($i = $this->maxFiles - 1; $i >= 1; $i--) {
            $currentFile = $this->filename . '.' . $i;
            $nextFile = $this->filename . '.' . ($i + 1);
            
            if (file_exists($currentFile)) {
                rename($currentFile, $nextFile);
            }
        }
        
        // انتقال فایل فعلی به .1
        if (file_exists($this->filename)) {
            rename($this->filename, $this->filename . '.1');
        }
    }
    
    /**
     * فرمت‌دهنده پیش‌فرض
     */
    protected function getDefaultFormatter(): LogFormatterInterface
    {
        return new LineFormatter();
    }
    
    /**
     * بستن هندلر
     */
    public function close(): void
    {
        if ($this->stream !== null) {
            fclose($this->stream);
            $this->stream = null;
        }
    }
}

// ═══════════════════════════════════════════════════════════════════
// هندلر فایل روزانه (Daily File Handler)
// ═══════════════════════════════════════════════════════════════════

class DailyFileHandler extends AbstractLogHandler
{
    private string $basePath;
    private string $filenameFormat;
    private int $maxDays;
    private int $filePermission;
    private ?resource $stream = null;
    private string $currentDate = '';
    
    public function __construct(
        string $basePath,
        string $filenameFormat = '{date}.log',
        string $minLevel = LogLevel::DEBUG,
        bool $bubble = true,
        int $maxDays = 30,
        int $filePermission = 0644
    ) {
        parent::__construct($minLevel, $bubble);
        
        $this->basePath = rtrim($basePath, '/');
        $this->filenameFormat = $filenameFormat;
        $this->maxDays = $maxDays;
        $this->filePermission = $filePermission;
    }
    
    /**
     * پردازش رکورد
     */
    public function handle(array $record): bool
    {
        if (!$this->isHandling($record['level'])) {
            return $this->bubble;
        }
        
        // بررسی تغییر تاریخ
        $today = date('Y-m-d');
        if ($today !== $this->currentDate) {
            $this->currentDate = $today;
            $this->close();
            $this->cleanup();
        }
        
        // باز کردن فایل
        if ($this->stream === null) {
            $this->openFile();
        }
        
        // نوشتن
        $formatted = $this->getFormatter()->format($record);
        fwrite($this->stream, $formatted);
        
        return $this->bubble;
    }
    
    /**
     * دریافت نام فایل فعلی
     */
    private function getCurrentFilename(): string
    {
        $filename = str_replace('{date}', $this->currentDate, $this->filenameFormat);
        return $this->basePath . '/' . $filename;
    }
    
    /**
     * باز کردن فایل
     */
    private function openFile(): void
    {
        $filename = $this->getCurrentFilename();
        $dir = dirname($filename);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $this->stream = fopen($filename, 'a');
        
        if ($this->stream === false) {
            throw new \RuntimeException("امکان باز کردن فایل لاگ وجود ندارد: {$filename}");
        }
        
        chmod($filename, $this->filePermission);
    }
    
    /**
     * پاکسازی فایل‌های قدیمی
     */
    private function cleanup(): void
    {
        $pattern = $this->basePath . '/*.log';
        $files = glob($pattern);
        
        if ($files === false) {
            return;
        }
        
        // مرتب‌سازی بر اساس زمان
        usort($files, function ($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        // حذف فایل‌های اضافی
        $deleteCount = count($files) - $this->maxDays;
        if ($deleteCount > 0) {
            for ($i = 0; $i < $deleteCount; $i++) {
                unlink($files[$i]);
            }
        }
    }
    
    /**
     * فرمت‌دهنده پیش‌فرض
     */
    protected function getDefaultFormatter(): LogFormatterInterface
    {
        return new LineFormatter();
    }
    
    /**
     * بستن هندلر
     */
    public function close(): void
    {
        if ($this->stream !== null) {
            fclose($this->stream);
            $this->stream = null;
        }
    }
}

// ═══════════════════════════════════════════════════════════════════
// هندلر دیتابیس (Database Handler)
// ═══════════════════════════════════════════════════════════════════

class DatabaseHandler extends AbstractLogHandler
{
    private PDO $pdo;
    private string $table;
    private array $columns;
    private ?\PDOStatement $stmt = null;
    
    public function __construct(
        PDO $pdo,
        string $table = 'system_logs',
        string $minLevel = LogLevel::DEBUG,
        bool $bubble = true
    ) {
        parent::__construct($minLevel, $bubble);
        
        $this->pdo = $pdo;
        $this->table = $table;
        $this->columns = [
            'channel'     => 'channel',
            'level'       => 'level',
            'message'     => 'message',
            'context'     => 'context',
            'extra'       => 'extra',
            'created_at'  => 'created_at',
        ];
    }
    
    /**
     * تنظیم نقشه ستون‌ها
     */
    public function setColumnMapping(array $columns): self
    {
        $this->columns = array_merge($this->columns, $columns);
        return $this;
    }
    
    /**
     * پردازش رکورد
     */
    public function handle(array $record): bool
    {
        if (!$this->isHandling($record['level'])) {
            return $this->bubble;
        }
        
        $data = [
            $this->columns['channel']    => $record['channel'],
            $this->columns['level']      => $record['level'],
            $this->columns['message']    => $record['message'],
            $this->columns['context']    => json_encode($record['context'], JSON_UNESCAPED_UNICODE),
            $this->columns['extra']      => json_encode($record['extra'], JSON_UNESCAPED_UNICODE),
            $this->columns['created_at'] => $record['datetime']->format('Y-m-d H:i:s'),
        ];
        
        // افزودن user_id اگر موجود است
        if (isset($record['extra']['user_id']) && isset($this->columns['user_id'])) {
            $data[$this->columns['user_id']] = $record['extra']['user_id'];
        }
        
        // افزودن IP اگر موجود است
        if (isset($record['extra']['ip']) && isset($this->columns['ip_address'])) {
            $data[$this->columns['ip_address']] = $record['extra']['ip'];
        }
        
        $this->insert($data);
        
        return $this->bubble;
    }
    
    /**
     * درج در دیتابیس
     */
    private function insert(array $data): void
    {
        $columns = implode(', ', array_map(fn($c) => "`{$c}`", array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO `{$this->table}` ({$columns}) VALUES ({$placeholders})";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_values($data));
        } catch (\PDOException $e) {
            // در صورت خطا، سعی در لاگ نکنیم تا حلقه بی‌نهایت نشود
            error_log("Database Logger Error: " . $e->getMessage());
        }
    }
    
    /**
     * فرمت‌دهنده پیش‌فرض (استفاده نمی‌شود)
     */
    protected function getDefaultFormatter(): LogFormatterInterface
    {
        return new JsonFormatter();
    }
}

// ═══════════════════════════════════════════════════════════════════
// هندلر ایمیل (Email Handler)
// ═══════════════════════════════════════════════════════════════════

class EmailHandler extends AbstractLogHandler
{
    private string $to;
    private string $subject;
    private string $from;
    private array $headers;
    private array $buffer = [];
    private int $bufferSize;
    
    public function __construct(
        string $to,
        string $subject = '[CRM Log] Alert',
        string $from = 'noreply@example.com',
        string $minLevel = LogLevel::ERROR,
        bool $bubble = true,
        int $bufferSize = 1
    ) {
        parent::__construct($minLevel, $bubble);
        
        $this->to = $to;
        $this->subject = $subject;
        $this->from = $from;
        $this->bufferSize = $bufferSize;
        $this->headers = [
            'From'         => $from,
            'Content-Type' => 'text/html; charset=UTF-8',
            'MIME-Version' => '1.0',
        ];
    }
    
    /**
     * پردازش رکورد
     */
    public function handle(array $record): bool
    {
        if (!$this->isHandling($record['level'])) {
            return $this->bubble;
        }
        
        $this->buffer[] = $record;
        
        // ارسال اگر بافر پر شد
        if (count($this->buffer) >= $this->bufferSize) {
            $this->send();
        }
        
        return $this->bubble;
    }
    
    /**
     * ارسال ایمیل
     */
    private function send(): void
    {
        if (empty($this->buffer)) {
            return;
        }
        
        $content = $this->buildEmailContent();
        $headers = $this->buildHeaders();
        
        // تنظیم subject بر اساس سطح
        $highestLevel = $this->getHighestLevel();
        $subject = "[{$highestLevel}] " . $this->subject;
        
        mail($this->to, $subject, $content, $headers);
        
        $this->buffer = [];
    }
    
    /**
     * ساخت محتوای ایمیل
     */
    private function buildEmailContent(): string
    {
        $html = '<!DOCTYPE html><html dir="rtl" lang="fa"><head><meta charset="UTF-8"></head><body style="font-family: Tahoma, sans-serif; direction: rtl;">';
        $html .= '<h1 style="color: #dc3545;">گزارش خطاهای سیستم CRM</h1>';
        $html .= '<p>زمان: ' . date('Y-m-d H:i:s') . '</p>';
        $html .= '<hr>';
        
        foreach ($this->buffer as $record) {
            $levelColor = $this->getLevelColor($record['level']);
            $html .= '<div style="border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-right: 4px solid ' . $levelColor . ';">';
            $html .= '<p><strong>سطح:</strong> <span style="color: ' . $levelColor . ';">' . strtoupper($record['level']) . '</span></p>';
            $html .= '<p><strong>کانال:</strong> ' . htmlspecialchars($record['channel']) . '</p>';
            $html .= '<p><strong>پیام:</strong> ' . htmlspecialchars($record['message']) . '</p>';
            $html .= '<p><strong>زمان:</strong> ' . $record['datetime']->format('Y-m-d H:i:s') . '</p>';
            
            if (!empty($record['context'])) {
                $html .= '<p><strong>جزئیات:</strong></p>';
                $html .= '<pre style="background: #f8f9fa; padding: 10px; overflow-x: auto;">' . 
                         htmlspecialchars(json_encode($record['context'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . 
                         '</pre>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</body></html>';
        return $html;
    }
    
    /**
     * ساخت هدرها
     */
    private function buildHeaders(): string
    {
        $headers = '';
        foreach ($this->headers as $name => $value) {
            $headers .= "{$name}: {$value}\r\n";
        }
        return $headers;
    }
    
    /**
     * بالاترین سطح در بافر
     */
    private function getHighestLevel(): string
    {
        $highest = LogLevel::DEBUG;
        $highestPriority = LogLevel::getPriority(LogLevel::DEBUG);
        
        foreach ($this->buffer as $record) {
            $priority = LogLevel::getPriority($record['level']);
            if ($priority < $highestPriority) {
                $highestPriority = $priority;
                $highest = $record['level'];
            }
        }
        
        return strtoupper($highest);
    }
    
    /**
     * رنگ سطح
     */
    private function getLevelColor(string $level): string
    {
        $colors = [
            LogLevel::EMERGENCY => '#000000',
            LogLevel::ALERT     => '#8B0000',
            LogLevel::CRITICAL  => '#DC143C',
            LogLevel::ERROR     => '#dc3545',
            LogLevel::WARNING   => '#ffc107',
            LogLevel::NOTICE    => '#17a2b8',
            LogLevel::INFO      => '#28a745',
            LogLevel::DEBUG     => '#6c757d',
        ];
        
        return $colors[$level] ?? '#6c757d';
    }
    
    /**
     * فرمت‌دهنده پیش‌فرض
     */
    protected function getDefaultFormatter(): LogFormatterInterface
    {
        return new LineFormatter();
    }
    
    /**
     * بستن هندلر - ارسال بافر باقیمانده
     */
    public function close(): void
    {
        if (!empty($this->buffer)) {
            $this->send();
        }
    }
}

// ═══════════════════════════════════════════════════════════════════
// هندلر Syslog
// ═══════════════════════════════════════════════════════════════════

class SyslogHandler extends AbstractLogHandler
{
    private string $ident;
    private int $facility;
    private bool $opened = false;
    
    private const SYSLOG_LEVELS = [
        LogLevel::EMERGENCY => LOG_EMERG,
        LogLevel::ALERT     => LOG_ALERT,
        LogLevel::CRITICAL  => LOG_CRIT,
        LogLevel::ERROR     => LOG_ERR,
        LogLevel::WARNING   => LOG_WARNING,
        LogLevel::NOTICE    => LOG_NOTICE,
        LogLevel::INFO      => LOG_INFO,
        LogLevel::DEBUG     => LOG_DEBUG,
    ];
    
    public function __construct(
        string $ident = 'CRM',
        int $facility = LOG_USER,
        string $minLevel = LogLevel::DEBUG,
        bool $bubble = true
    ) {
        parent::__construct($minLevel, $bubble);
        
        $this->ident = $ident;
        $this->facility = $facility;
    }
    
    /**
     * پردازش رکورد
     */
    public function handle(array $record): bool
    {
        if (!$this->isHandling($record['level'])) {
            return $this->bubble;
        }
        
        if (!$this->opened) {
            openlog($this->ident, LOG_PID, $this->facility);
            $this->opened = true;
        }
        
        $priority = self::SYSLOG_LEVELS[$record['level']] ?? LOG_INFO;
        $message = $this->getFormatter()->format($record);
        
        syslog($priority, $message);
        
        return $this->bubble;
    }
    
    /**
     * فرمت‌دهنده پیش‌فرض
     */
    protected function getDefaultFormatter(): LogFormatterInterface
    {
        return new LineFormatter("%channel%.%level_name%: %message% %context%");
    }
    
    /**
     * بستن هندلر
     */
    public function close(): void
    {
        if ($this->opened) {
            closelog();
            $this->opened = false;
        }
    }
}

// ═══════════════════════════════════════════════════════════════════
// هندلر Stream (برای stderr, php://output, ...)
// ═══════════════════════════════════════════════════════════════════

class StreamHandler extends AbstractLogHandler
{
    /** @var resource|null */
    private $stream;
    private ?string $url;
    
    /**
     * @param resource|string $stream
     */
    public function __construct(
        $stream,
        string $minLevel = LogLevel::DEBUG,
        bool $bubble = true
    ) {
        parent::__construct($minLevel, $bubble);
        
        if (is_resource($stream)) {
            $this->stream = $stream;
            $this->url = null;
        } elseif (is_string($stream)) {
            $this->url = $stream;
            $this->stream = null;
        } else {
            throw new \InvalidArgumentException('پارامتر stream باید resource یا string باشد.');
        }
    }
    
    /**
     * پردازش رکورد
     */
    public function handle(array $record): bool
    {
        if (!$this->isHandling($record['level'])) {
            return $this->bubble;
        }
        
        if ($this->stream === null && $this->url !== null) {
            $this->stream = fopen($this->url, 'a');
            if ($this->stream === false) {
                throw new \RuntimeException("امکان باز کردن استریم وجود ندارد: {$this->url}");
            }
        }
        
        $formatted = $this->getFormatter()->format($record);
        fwrite($this->stream, $formatted);
        
        return $this->bubble;
    }
    
    /**
     * فرمت‌دهنده پیش‌فرض
     */
    protected function getDefaultFormatter(): LogFormatterInterface
    {
        return new LineFormatter();
    }
    
    /**
     * بستن هندلر
     */
    public function close(): void
    {
        if ($this->stream !== null && $this->url !== null) {
            fclose($this->stream);
            $this->stream = null;
        }
    }
}

// ═══════════════════════════════════════════════════════════════════
// هندلر گروهی (Group Handler)
// ═══════════════════════════════════════════════════════════════════

class GroupHandler extends AbstractLogHandler
{
    /** @var LogHandlerInterface[] */
    private array $handlers;
    
    public function __construct(array $handlers = [], bool $bubble = true)
    {
        parent::__construct(LogLevel::DEBUG, $bubble);
        $this->handlers = $handlers;
    }
    
    /**
     * افزودن هندلر
     */
    public function pushHandler(LogHandlerInterface $handler): self
    {
        $this->handlers[] = $handler;
        return $this;
    }
    
    /**
     * پردازش رکورد
     */
    public function handle(array $record): bool
    {
        foreach ($this->handlers as $handler) {
            if ($handler->isHandling($record['level'])) {
                $handler->handle($record);
            }
        }
        
        return $this->bubble;
    }
    
    /**
     * آیا هیچ هندلری این سطح را پردازش می‌کند؟
     */
    public function isHandling(string $level): bool
    {
        foreach ($this->handlers as $handler) {
            if ($handler->isHandling($level)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * فرمت‌دهنده پیش‌فرض
     */
    protected function getDefaultFormatter(): LogFormatterInterface
    {
        return new LineFormatter();
    }
    
    /**
     * بستن همه هندلرها
     */
    public function close(): void
    {
        foreach ($this->handlers as $handler) {
            $handler->close();
        }
    }
}

// ═══════════════════════════════════════════════════════════════════
// پردازنده‌ها (Processors)
// ═══════════════════════════════════════════════════════════════════

class Processors
{
    /**
     * افزودن اطلاعات Git
     */
    public static function git(): callable
    {
        return function (array $record): array {
            static $gitInfo = null;
            
            if ($gitInfo === null) {
                $gitInfo = [];
                $gitDir = dirname(__DIR__, 2) . '/.git';
                
                if (is_dir($gitDir)) {
                    // خواندن branch
                    $headFile = $gitDir . '/HEAD';
                    if (file_exists($headFile)) {
                        $head = trim(file_get_contents($headFile));
                        if (strpos($head, 'ref:') === 0) {
                            $gitInfo['branch'] = str_replace('ref: refs/heads/', '', $head);
                        }
                    }
                    
                    // خواندن commit hash
                    $output = shell_exec('git rev-parse --short HEAD 2>/dev/null');
                    if ($output) {
                        $gitInfo['commit'] = trim($output);
                    }
                }
            }
            
            if (!empty($gitInfo)) {
                $record['extra']['git'] = $gitInfo;
            }
            
            return $record;
        };
    }
    
    /**
     * افزودن اطلاعات سیستم
     */
    public static function system(): callable
    {
        return function (array $record): array {
            $record['extra']['system'] = [
                'hostname'    => gethostname(),
                'php_version' => PHP_VERSION,
                'os'          => PHP_OS,
                'pid'         => getmypid(),
            ];
            return $record;
        };
    }
    
    /**
     * افزودن UUID به هر رکورد
     */
    public static function uid(): callable
    {
        return function (array $record): array {
            $record['extra']['uid'] = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            return $record;
        };
    }
    
    /**
     * افزودن Backtrace
     */
    public static function backtrace(int $skipFirst = 5, int $limit = 10): callable
    {
        return function (array $record) use ($skipFirst, $limit): array {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $skipFirst + $limit);
            $trace = array_slice($trace, $skipFirst);
            
            $formatted = [];
            foreach ($trace as $frame) {
                $formatted[] = sprintf(
                    '%s%s%s() at %s:%d',
                    $frame['class'] ?? '',
                    $frame['type'] ?? '',
                    $frame['function'] ?? 'unknown',
                    $frame['file'] ?? 'unknown',
                    $frame['line'] ?? 0
                );
            }
            
            $record['extra']['backtrace'] = $formatted;
            return $record;
        };
    }
}

// ═══════════════════════════════════════════════════════════════════
// تابع کمکی سراسری
// ═══════════════════════════════════════════════════════════════════

if (!function_exists('logger')) {
    /**
     * دریافت یا استفاده از Logger
     * 
     * @param string|null $message پیام (اختیاری)
     * @param array $context کانتکست (اختیاری)
     * @param string $level سطح (اختیاری)
     * @return Logger
     */
    function logger(?string $message = null, array $context = [], string $level = LogLevel::INFO): Logger
    {
        $logger = Logger::getInstance();
        
        if ($message !== null) {
            $logger->log($level, $message, $context);
        }
        
        return $logger;
    }
}

if (!function_exists('log_info')) {
    function log_info(string $message, array $context = []): void
    {
        Logger::getInstance()->info($message, $context);
    }
}

if (!function_exists('log_error')) {
    function log_error(string $message, array $context = []): void
    {
        Logger::getInstance()->error($message, $context);
    }
}

if (!function_exists('log_warning')) {
    function log_warning(string $message, array $context = []): void
    {
        Logger::getInstance()->warning($message, $context);
    }
}

if (!function_exists('log_debug')) {
    function log_debug(string $message, array $context = []): void
    {
        Logger::getInstance()->debug($message, $context);
    }
}

if (!function_exists('log_exception')) {
    function log_exception(\Throwable $e, array $context = []): void
    {
        Logger::getInstance()->exception($e, $context);
    }
}
