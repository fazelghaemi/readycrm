<?php
/**
 * ReadyCRM v2 - Response Handler (مدیریت پاسخ)
 * 
 * فایل: private/core/Response.php
 * 
 * مدیریت کامل پاسخ‌های HTTP:
 * - خروجی HTML, JSON, XML, File Download
 * - مدیریت هدرها و کوکی‌ها
 * - Redirect با Flash Messages
 * - HTTP Status Codes استاندارد
 * - Content Negotiation
 * - Caching Headers
 * - Streaming Response برای فایل‌های بزرگ
 * 
 * @package    ReadyCRM
 * @version    2.0.0
 * @author     ReadyStudio.ir
 */

namespace Core;

class Response
{
    /**
     * نمونه Singleton
     */
    private static ?Response $instance = null;
    
    /**
     * محتوای پاسخ
     */
    private ?string $content = null;
    
    /**
     * کد وضعیت HTTP
     */
    private int $statusCode = 200;
    
    /**
     * هدرها
     */
    private array $headers = [];
    
    /**
     * کوکی‌هایی که باید ست شوند
     */
    private array $cookies = [];
    
    /**
     * آیا پاسخ ارسال شده؟
     */
    private bool $sent = false;
    
    /**
     * نوع محتوا
     */
    private string $contentType = 'text/html';
    
    /**
     * Charset
     */
    private string $charset = 'UTF-8';
    
    /**
     * Protocol Version
     */
    private string $protocolVersion = '1.1';
    
    /**
     * HTTP Status Messages
     */
    private const STATUS_TEXTS = [
        // 1xx Informational
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',
        
        // 2xx Success
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        
        // 3xx Redirection
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        
        // 4xx Client Error
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => "I'm a teapot",
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        
        // 5xx Server Error
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];
    
    /**
     * MIME Types رایج
     */
    private const MIME_TYPES = [
        'html'  => 'text/html',
        'txt'   => 'text/plain',
        'css'   => 'text/css',
        'js'    => 'application/javascript',
        'json'  => 'application/json',
        'xml'   => 'application/xml',
        'pdf'   => 'application/pdf',
        'zip'   => 'application/zip',
        'doc'   => 'application/msword',
        'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'   => 'application/vnd.ms-excel',
        'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'svg'   => 'image/svg+xml',
        'webp'  => 'image/webp',
        'ico'   => 'image/x-icon',
        'mp3'   => 'audio/mpeg',
        'mp4'   => 'video/mp4',
        'webm'  => 'video/webm',
        'csv'   => 'text/csv',
    ];
    
    /**
     * سازنده - خصوصی برای Singleton
     */
    private function __construct()
    {
        // تنظیمات اولیه
        $this->headers = [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ];
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
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * ایجاد یک Response جدید
     */
    public static function make($content = '', int $status = 200, array $headers = []): self
    {
        $instance = self::getInstance();
        $instance->setContent($content);
        $instance->setStatusCode($status);
        
        foreach ($headers as $name => $value) {
            $instance->setHeader($name, $value);
        }
        
        return $instance;
    }
    
    // ═══════════════════════════════════════════════════════════
    // تنظیم محتوا و وضعیت
    // ═══════════════════════════════════════════════════════════
    
    /**
     * تنظیم محتوا
     */
    public function setContent($content): self
    {
        if ($content !== null && !is_string($content) && !is_numeric($content) && !is_callable([$content, '__toString'])) {
            throw new \InvalidArgumentException('Content must be a string or object implementing __toString()');
        }
        
        $this->content = (string) $content;
        return $this;
    }
    
    /**
     * دریافت محتوا
     */
    public function getContent(): ?string
    {
        return $this->content;
    }
    
    /**
     * افزودن به محتوا
     */
    public function appendContent(string $content): self
    {
        $this->content .= $content;
        return $this;
    }
    
    /**
     * افزودن به ابتدای محتوا
     */
    public function prependContent(string $content): self
    {
        $this->content = $content . $this->content;
        return $this;
    }
    
    /**
     * تنظیم کد وضعیت HTTP
     */
    public function setStatusCode(int $code, ?string $text = null): self
    {
        if ($code < 100 || $code >= 600) {
            throw new \InvalidArgumentException("Invalid HTTP status code: $code");
        }
        
        $this->statusCode = $code;
        return $this;
    }
    
    /**
     * دریافت کد وضعیت
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
    
    /**
     * دریافت متن وضعیت
     */
    public function getStatusText(): string
    {
        return self::STATUS_TEXTS[$this->statusCode] ?? 'Unknown';
    }
    
    // ═══════════════════════════════════════════════════════════
    // مدیریت هدرها
    // ═══════════════════════════════════════════════════════════
    
    /**
     * تنظیم یک هدر
     */
    public function setHeader(string $name, string $value, bool $replace = true): self
    {
        $name = $this->normalizeHeaderName($name);
        
        if ($replace || !isset($this->headers[$name])) {
            $this->headers[$name] = $value;
        }
        
        return $this;
    }
    
    /**
     * تنظیم چندین هدر
     */
    public function setHeaders(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
        return $this;
    }
    
    /**
     * دریافت یک هدر
     */
    public function getHeader(string $name): ?string
    {
        $name = $this->normalizeHeaderName($name);
        return $this->headers[$name] ?? null;
    }
    
    /**
     * دریافت تمام هدرها
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
    
    /**
     * بررسی وجود هدر
     */
    public function hasHeader(string $name): bool
    {
        $name = $this->normalizeHeaderName($name);
        return isset($this->headers[$name]);
    }
    
    /**
     * حذف یک هدر
     */
    public function removeHeader(string $name): self
    {
        $name = $this->normalizeHeaderName($name);
        unset($this->headers[$name]);
        return $this;
    }
    
    /**
     * نرمال‌سازی نام هدر
     */
    private function normalizeHeaderName(string $name): string
    {
        return str_replace(' ', '-', ucwords(str_replace('-', ' ', strtolower($name))));
    }
    
    /**
     * تنظیم Content-Type
     */
    public function setContentType(string $type, ?string $charset = null): self
    {
        $this->contentType = $type;
        
        if ($charset) {
            $this->charset = $charset;
        }
        
        $value = $type;
        if (strpos($type, 'text/') === 0 || $type === 'application/json' || $type === 'application/xml') {
            $value .= '; charset=' . $this->charset;
        }
        
        return $this->setHeader('Content-Type', $value);
    }
    
    // ═══════════════════════════════════════════════════════════
    // مدیریت کوکی‌ها
    // ═══════════════════════════════════════════════════════════
    
    /**
     * تنظیم کوکی
     */
    public function setCookie(
        string $name,
        string $value = '',
        int $expires = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = 'Lax'
    ): self {
        $this->cookies[$name] = [
            'value' => $value,
            'expires' => $expires,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httpOnly' => $httpOnly,
            'sameSite' => $sameSite,
        ];
        
        return $this;
    }
    
    /**
     * حذف کوکی
     */
    public function removeCookie(string $name, string $path = '/', string $domain = ''): self
    {
        return $this->setCookie($name, '', time() - 3600, $path, $domain);
    }
    
    /**
     * دریافت تمام کوکی‌ها
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }
    
    // ═══════════════════════════════════════════════════════════
    // Response Types (انواع پاسخ)
    // ═══════════════════════════════════════════════════════════
    
    /**
     * پاسخ JSON
     */
    public function json($data, int $status = 200, int $options = 0): self
    {
        if ($options === 0) {
            $options = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        }
        
        $json = json_encode($data, $options);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('JSON encoding error: ' . json_last_error_msg());
        }
        
        $this->setContent($json);
        $this->setStatusCode($status);
        $this->setContentType('application/json');
        
        return $this;
    }
    
    /**
     * پاسخ JSON موفق (API)
     */
    public function success($data = null, string $message = 'عملیات موفق', int $status = 200): self
    {
        return $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c'),
        ], $status);
    }
    
    /**
     * پاسخ JSON خطا (API)
     */
    public function error(string $message = 'خطایی رخ داد', int $status = 400, $errors = null, ?string $code = null): self
    {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('c'),
        ];
        
        if ($code) {
            $response['code'] = $code;
        }
        
        if ($errors) {
            $response['errors'] = $errors;
        }
        
        return $this->json($response, $status);
    }
    
    /**
     * پاسخ JSON با صفحه‌بندی (API)
     */
    public function paginated(array $items, int $total, int $page, int $perPage, string $message = ''): self
    {
        $totalPages = (int) ceil($total / $perPage);
        
        return $this->json([
            'success' => true,
            'message' => $message,
            'data' => $items,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $totalPages,
                'from' => ($page - 1) * $perPage + 1,
                'to' => min($page * $perPage, $total),
                'has_more' => $page < $totalPages,
            ],
            'timestamp' => date('c'),
        ]);
    }
    
    /**
     * پاسخ XML
     */
    public function xml($data, int $status = 200, string $rootElement = 'response'): self
    {
        $xml = $this->arrayToXml($data, $rootElement);
        
        $this->setContent($xml);
        $this->setStatusCode($status);
        $this->setContentType('application/xml');
        
        return $this;
    }
    
    /**
     * تبدیل آرایه به XML
     */
    private function arrayToXml($data, string $rootElement = 'root', ?\SimpleXMLElement $xml = null): string
    {
        if ($xml === null) {
            $xml = new \SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><{$rootElement}/>");
        }
        
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $key = 'item';
            }
            
            if (is_array($value)) {
                $this->arrayToXml($value, $key, $xml->addChild($key));
            } else {
                $xml->addChild($key, htmlspecialchars((string) $value));
            }
        }
        
        return $xml->asXML();
    }
    
    /**
     * پاسخ HTML
     */
    public function html(string $content, int $status = 200): self
    {
        $this->setContent($content);
        $this->setStatusCode($status);
        $this->setContentType('text/html');
        
        return $this;
    }
    
    /**
     * پاسخ متنی ساده
     */
    public function text(string $content, int $status = 200): self
    {
        $this->setContent($content);
        $this->setStatusCode($status);
        $this->setContentType('text/plain');
        
        return $this;
    }
    
    /**
     * پاسخ خالی (No Content)
     */
    public function noContent(): self
    {
        $this->setContent('');
        $this->setStatusCode(204);
        
        return $this;
    }
    
    // ═══════════════════════════════════════════════════════════
    // Redirect (هدایت)
    // ═══════════════════════════════════════════════════════════
    
    /**
     * هدایت به URL
     */
    public function redirect(string $url, int $status = 302): self
    {
        if ($status < 300 || $status >= 400) {
            throw new \InvalidArgumentException("Invalid redirect status code: $status");
        }
        
        $this->setStatusCode($status);
        $this->setHeader('Location', $url);
        $this->setContent('');
        
        return $this;
    }
    
    /**
     * هدایت دائمی (301)
     */
    public function redirectPermanent(string $url): self
    {
        return $this->redirect($url, 301);
    }
    
    /**
     * هدایت به صفحه قبلی
     */
    public function back(?string $fallback = null): self
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? $fallback ?? '/';
        return $this->redirect($referer);
    }
    
    /**
     * هدایت به روت
     */
    public function redirectToRoute(string $name, array $params = [], int $status = 302): self
    {
        if (function_exists('route')) {
            $url = route($name, $params);
        } else {
            $url = '/' . $name;
        }
        
        return $this->redirect($url, $status);
    }
    
    /**
     * هدایت با پیام Flash
     */
    public function redirectWith(string $url, string $message, string $type = 'success'): self
    {
        if (class_exists('\Core\Session')) {
            \Core\Session::getInstance()->flash($type, $message);
        } elseif (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION["flash_{$type}"] = $message;
        }
        
        return $this->redirect($url);
    }
    
    /**
     * هدایت با خطاهای اعتبارسنجی
     */
    public function redirectWithErrors(string $url, array $errors, array $old = []): self
    {
        if (class_exists('\Core\Session')) {
            $session = \Core\Session::getInstance();
            $session->flash('errors', $errors);
            $session->flash('old', $old);
        } elseif (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['flash_errors'] = $errors;
            $_SESSION['flash_old'] = $old;
        }
        
        return $this->redirect($url);
    }
    
    // ═══════════════════════════════════════════════════════════
    // File Response (دانلود فایل)
    // ═══════════════════════════════════════════════════════════
    
    /**
     * دانلود فایل
     */
    public function download(string $filePath, ?string $fileName = null, array $headers = []): self
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: $filePath");
        }
        
        if (!is_readable($filePath)) {
            throw new \RuntimeException("File not readable: $filePath");
        }
        
        $fileName = $fileName ?? basename($filePath);
        $fileSize = filesize($filePath);
        $mimeType = $this->getMimeType($filePath);
        
        // هدرهای پایه دانلود
        $this->setHeader('Content-Description', 'File Transfer');
        $this->setHeader('Content-Type', $mimeType);
        $this->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        $this->setHeader('Content-Transfer-Encoding', 'binary');
        $this->setHeader('Content-Length', (string) $fileSize);
        $this->setHeader('Cache-Control', 'must-revalidate');
        $this->setHeader('Pragma', 'public');
        $this->setHeader('Expires', '0');
        
        // هدرهای اضافی
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
        
        // ارسال هدرها و فایل
        $this->sendHeaders();
        
        // ارسال محتوای فایل با streaming
        $this->streamFile($filePath);
        
        $this->sent = true;
        exit;
    }
    
    /**
     * نمایش فایل (inline)
     */
    public function file(string $filePath, ?string $fileName = null, array $headers = []): self
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: $filePath");
        }
        
        $fileName = $fileName ?? basename($filePath);
        $mimeType = $this->getMimeType($filePath);
        $fileSize = filesize($filePath);
        
        $this->setHeader('Content-Type', $mimeType);
        $this->setHeader('Content-Disposition', 'inline; filename="' . $fileName . '"');
        $this->setHeader('Content-Length', (string) $fileSize);
        
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
        
        $this->sendHeaders();
        $this->streamFile($filePath);
        
        $this->sent = true;
        exit;
    }
    
    /**
     * Stream فایل
     */
    private function streamFile(string $filePath, int $chunkSize = 8192): void
    {
        $handle = fopen($filePath, 'rb');
        
        if ($handle === false) {
            throw new \RuntimeException("Cannot open file: $filePath");
        }
        
        while (!feof($handle)) {
            echo fread($handle, $chunkSize);
            
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }
        
        fclose($handle);
    }
    
    /**
     * دریافت MIME Type فایل
     */
    private function getMimeType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if (isset(self::MIME_TYPES[$extension])) {
            return self::MIME_TYPES[$extension];
        }
        
        // استفاده از finfo
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);
            
            if ($mimeType) {
                return $mimeType;
            }
        }
        
        return 'application/octet-stream';
    }
    
    /**
     * Stream محتوا (برای فایل‌های بزرگ یا تولید پویا)
     */
    public function stream(callable $callback, int $status = 200, array $headers = []): void
    {
        $this->setStatusCode($status);
        
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
        
        $this->sendHeaders();
        
        $callback();
        
        $this->sent = true;
        exit;
    }
    
    // ═══════════════════════════════════════════════════════════
    // Cache Headers (هدرهای کش)
    // ═══════════════════════════════════════════════════════════
    
    /**
     * تنظیم هدرهای کش
     */
    public function cache(int $seconds, bool $public = true): self
    {
        $visibility = $public ? 'public' : 'private';
        
        $this->setHeader('Cache-Control', "{$visibility}, max-age={$seconds}");
        $this->setHeader('Expires', gmdate('D, d M Y H:i:s', time() + $seconds) . ' GMT');
        
        return $this;
    }
    
    /**
     * غیرفعال کردن کش
     */
    public function noCache(): self
    {
        $this->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->setHeader('Pragma', 'no-cache');
        $this->setHeader('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');
        
        return $this;
    }
    
    /**
     * تنظیم ETag
     */
    public function etag(string $etag, bool $weak = false): self
    {
        $etag = $weak ? 'W/"' . $etag . '"' : '"' . $etag . '"';
        $this->setHeader('ETag', $etag);
        
        return $this;
    }
    
    /**
     * تنظیم Last-Modified
     */
    public function lastModified(\DateTime $date): self
    {
        $this->setHeader('Last-Modified', $date->format('D, d M Y H:i:s') . ' GMT');
        return $this;
    }
    
    /**
     * بررسی و اعمال 304 Not Modified
     */
    public function isNotModified(): bool
    {
        // بررسی ETag
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $this->hasHeader('ETag')) {
            $clientEtag = trim($_SERVER['HTTP_IF_NONE_MATCH'], '"');
            $serverEtag = trim($this->getHeader('ETag'), '"W/');
            
            if ($clientEtag === $serverEtag = trim($this->getHeader('ETag'), '"W/');
            
            if ($clientEtag === $serverEtag) {
                return true;
            }
        }
        
        // بررسی Last-Modified
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $this->hasHeader('Last-Modified')) {
            $clientTime = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
            $serverTime = strtotime($this->getHeader('Last-Modified'));
            
            if ($clientTime >= $serverTime) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * ارسال 304 Not Modified
     */
    public function notModified(): self
    {
        $this->setStatusCode(304);
        $this->setContent('');
        
        return $this;
    }
    
    // ═══════════════════════════════════════════════════════════
    // Security Headers (هدرهای امنیتی)
    // ═══════════════════════════════════════════════════════════
    
    /**
     * تنظیم Content Security Policy
     */
    public function csp(array $directives): self
    {
        $parts = [];
        
        foreach ($directives as $directive => $value) {
            if (is_array($value)) {
                $value = implode(' ', $value);
            }
            $parts[] = "$directive $value";
        }
        
        return $this->setHeader('Content-Security-Policy', implode('; ', $parts));
    }
    
    /**
     * تنظیم CORS headers
     */
    public function cors(
        string $origin = '*',
        array $methods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        array $headers = ['Content-Type', 'Authorization'],
        bool $credentials = false,
        int $maxAge = 86400
    ): self {
        $this->setHeader('Access-Control-Allow-Origin', $origin);
        $this->setHeader('Access-Control-Allow-Methods', implode(', ', $methods));
        $this->setHeader('Access-Control-Allow-Headers', implode(', ', $headers));
        $this->setHeader('Access-Control-Max-Age', (string) $maxAge);
        
        if ($credentials) {
            $this->setHeader('Access-Control-Allow-Credentials', 'true');
        }
        
        return $this;
    }
    
    /**
     * هدرهای امنیتی پیشرفته
     */
    public function securityHeaders(): self
    {
        $this->setHeader('X-Content-Type-Options', 'nosniff');
        $this->setHeader('X-Frame-Options', 'SAMEORIGIN');
        $this->setHeader('X-XSS-Protection', '1; mode=block');
        $this->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->setHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $this->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        
        return $this;
    }
    
    // ═══════════════════════════════════════════════════════════
    // Error Pages (صفحات خطا)
    // ═══════════════════════════════════════════════════════════
    
    /**
     * صفحه 404 Not Found
     */
    public function notFound(string $message = 'صفحه مورد نظر یافت نشد'): self
    {
        return $this->errorPage(404, $message);
    }
    
    /**
     * صفحه 403 Forbidden
     */
    public function forbidden(string $message = 'دسترسی غیرمجاز'): self
    {
        return $this->errorPage(403, $message);
    }
    
    /**
     * صفحه 401 Unauthorized
     */
    public function unauthorized(string $message = 'نیاز به احراز هویت'): self
    {
        return $this->errorPage(401, $message);
    }
    
    /**
     * صفحه 500 Server Error
     */
    public function serverError(string $message = 'خطای سرور'): self
    {
        return $this->errorPage(500, $message);
    }
    
    /**
     * صفحه 503 Service Unavailable
     */
    public function maintenance(string $message = 'سایت در حال بروزرسانی است'): self
    {
        $this->setHeader('Retry-After', '3600');
        return $this->errorPage(503, $message);
    }
    
    /**
     * تولید صفحه خطا
     */
    public function errorPage(int $code, string $message): self
    {
        $this->setStatusCode($code);
        $statusText = self::STATUS_TEXTS[$code] ?? 'Error';
        
        // اگر درخواست AJAX است، JSON برگردان
        if ($this->isAjaxRequest()) {
            return $this->error($message, $code);
        }
        
        // صفحه HTML خطا
        $html = $this->renderErrorPage($code, $statusText, $message);
        
        return $this->html($html, $code);
    }
    
    /**
     * رندر صفحه خطای HTML
     */
    private function renderErrorPage(int $code, string $statusText, string $message): string
    {
        $icons = [
            400 => '⚠️',
            401 => '🔒',
            403 => '🚫',
            404 => '🔍',
            500 => '💥',
            503 => '🔧',
        ];
        
        $icon = $icons[$code] ?? '❌';
        
        return <<<HTML
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>خطای {$code} - {$statusText}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Tahoma, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .error-container {
            background: white;
            border-radius: 16px;
            padding: 60px 40px;
            text-align: center;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }
        .error-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        .error-code {
            font-size: 72px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        .error-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
        }
        .error-message {
            font-size: 16px;
            color: #666;
            line-height: 1.8;
            margin-bottom: 30px;
        }
        .error-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5a6fd6;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }
        .btn-secondary:hover {
            background: #e0e0e0;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">{$icon}</div>
        <div class="error-code">{$code}</div>
        <h1 class="error-title">{$statusText}</h1>
        <p class="error-message">{$message}</p>
        <div class="error-actions">
            <a href="/" class="btn btn-primary">صفحه اصلی</a>
            <a href="javascript:history.back()" class="btn btn-secondary">بازگشت</a>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * بررسی درخواست AJAX
     */
    private function isAjaxRequest(): bool
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
               (isset($_SERVER['HTTP_ACCEPT']) && 
                strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    }
    
    // ═══════════════════════════════════════════════════════════
    // View Rendering (رندر ویو)
    // ═══════════════════════════════════════════════════════════
    
    /**
     * رندر یک ویو
     */
    public function view(string $view, array $data = [], int $status = 200): self
    {
        $viewPath = $this->resolveViewPath($view);
        
        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View not found: $view");
        }
        
        // Extract متغیرها برای دسترسی در ویو
        extract($data);
        
        // شروع بافر خروجی
        ob_start();
        
        try {
            include $viewPath;
            $content = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        
        return $this->html($content, $status);
    }
    
    /**
     * حل مسیر ویو
     */
    private function resolveViewPath(string $view): string
    {
        // تبدیل نقطه به اسلش
        $view = str_replace('.', '/', $view);
        
        // مسیر پایه ویوها
        $basePath = defined('VIEWS_PATH') ? VIEWS_PATH : dirname(__DIR__, 2) . '/views';
        
        return rtrim($basePath, '/') . '/' . $view . '.php';
    }
    
    // ═══════════════════════════════════════════════════════════
    // Send Response (ارسال پاسخ)
    // ═══════════════════════════════════════════════════════════
    
    /**
     * ارسال هدرها
     */
    public function sendHeaders(): self
    {
        if (headers_sent()) {
            return $this;
        }
        
        // ارسال Status Line
        $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/' . $this->protocolVersion;
        $statusText = $this->getStatusText();
        header("{$protocol} {$this->statusCode} {$statusText}", true, $this->statusCode);
        
        // ارسال هدرها
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}", true);
        }
        
        // ارسال کوکی‌ها
        foreach ($this->cookies as $name => $cookie) {
            setcookie(
                $name,
                $cookie['value'],
                [
                    'expires' => $cookie['expires'],
                    'path' => $cookie['path'],
                    'domain' => $cookie['domain'],
                    'secure' => $cookie['secure'],
                    'httponly' => $cookie['httpOnly'],
                    'samesite' => $cookie['sameSite'],
                ]
            );
        }
        
        return $this;
    }
    
    /**
     * ارسال محتوا
     */
    public function sendContent(): self
    {
        echo $this->content;
        return $this;
    }
    
    /**
     * ارسال کامل پاسخ
     */
    public function send(): void
    {
        if ($this->sent) {
            return;
        }
        
        $this->sendHeaders();
        $this->sendContent();
        
        $this->sent = true;
        
        // پایان دادن به خروجی
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (ob_get_level() > 0) {
            ob_end_flush();
        }
    }
    
    /**
     * آیا پاسخ ارسال شده؟
     */
    public function isSent(): bool
    {
        return $this->sent;
    }
    
    /**
     * تبدیل به رشته
     */
    public function __toString(): string
    {
        return $this->content ?? '';
    }
}

// ═══════════════════════════════════════════════════════════════════
// توابع کمکی سراسری (Global Helper Functions)
// ═══════════════════════════════════════════════════════════════════

if (!function_exists('response')) {
    /**
     * دسترسی به Response
     * 
     * @param string|null $content محتوا
     * @param int $status کد وضعیت
     * @return Response
     */
    function response($content = null, int $status = 200): Response
    {
        $response = Response::getInstance();
        
        if ($content !== null) {
            $response->setContent($content);
            $response->setStatusCode($status);
        }
        
        return $response;
    }
}

if (!function_exists('json_response')) {
    /**
     * پاسخ JSON سریع
     * 
     * @param mixed $data داده‌ها
     * @param int $status کد وضعیت
     * @return Response
     */
    function json_response($data, int $status = 200): Response
    {
        return Response::getInstance()->json($data, $status);
    }
}

if (!function_exists('redirect')) {
    /**
     * هدایت سریع
     * 
     * @param string $url آدرس مقصد
     * @param int $status کد وضعیت
     * @return Response
     */
    function redirect(string $url, int $status = 302): Response
    {
        return Response::getInstance()->redirect($url, $status);
    }
}

if (!function_exists('back')) {
    /**
     * بازگشت به صفحه قبل
     * 
     * @param string|null $fallback آدرس پیش‌فرض
     * @return Response
     */
    function back(?string $fallback = null): Response
    {
        return Response::getInstance()->back($fallback);
    }
}

if (!function_exists('view')) {
    /**
     * رندر ویو
     * 
     * @param string $name نام ویو
     * @param array $data داده‌ها
     * @param int $status کد وضعیت
     * @return Response
     */
    function view(string $name, array $data = [], int $status = 200): Response
    {
        return Response::getInstance()->view($name, $data, $status);
    }
}

if (!function_exists('abort')) {
    /**
     * توقف با کد خطا
     * 
     * @param int $code کد خطا
     * @param string $message پیام
     */
    function abort(int $code, string $message = ''): void
    {
        Response::getInstance()->errorPage($code, $message)->send();
        exit;
    }
}

if (!function_exists('api_success')) {
    /**
     * پاسخ موفق API
     * 
     * @param mixed $data داده‌ها
     * @param string $message پیام
     * @param int $status کد وضعیت
     * @return Response
     */
    function api_success($data = null, string $message = 'عملیات موفق', int $status = 200): Response
    {
        return Response::getInstance()->success($data, $message, $status);
    }
}

if (!function_exists('api_error')) {
    /**
     * پاسخ خطای API
     * 
     * @param string $message پیام خطا
     * @param int $status کد وضعیت
     * @param array|null $errors جزئیات خطاها
     * @return Response
     */
    function api_error(string $message = 'خطایی رخ داد', int $status = 400, ?array $errors = null): Response
    {
        return Response::getInstance()->error($message, $status, $errors);
    }
}

if (!function_exists('download')) {
    /**
     * دانلود فایل
     * 
     * @param string $filePath مسیر فایل
     * @param string|null $fileName نام فایل
     * @return Response
     */
    function download(string $filePath, ?string $fileName = null): Response
    {
        return Response::getInstance()->download($filePath, $fileName);
    }
}
