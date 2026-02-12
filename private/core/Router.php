<?php
/**
 * ReadyCRM v2 - Router (مسیریاب)
 * 
 * فایل: private/core/Router.php
 * 
 * سیستم مسیریابی ساده و سبک برای CRM:
 * - پشتیبانی از روت‌های GET, POST, PUT, DELETE
 * - گروه‌بندی روت‌ها با prefix و middleware
 * - پارامترهای داینامیک در URL
 * - Middleware قبل و بعد از اجرا
 * - Named Routes برای تولید URL
 * - Redirect و Response helpers
 * 
 * @package    ReadyCRM
 * @version    2.0.0
 * @author     ReadyStudio.ir
 */

namespace Core;

class Router
{
    /**
     * نمونه Singleton
     */
    private static ?Router $instance = null;
    
    /**
     * لیست تمام روت‌های ثبت شده
     * @var array
     */
    private array $routes = [];
    
    /**
     * روت‌های نامگذاری شده
     * @var array
     */
    private array $namedRoutes = [];
    
    /**
     * گروه فعلی (برای گروه‌بندی روت‌ها)
     * @var array
     */
    private array $groupStack = [];
    
    /**
     * Middleware های سراسری
     * @var array
     */
    private array $globalMiddleware = [];
    
    /**
     * روت فعلی در حال اجرا
     * @var array|null
     */
    private ?array $currentRoute = null;
    
    /**
     * پارامترهای استخراج شده از URL
     * @var array
     */
    private array $params = [];
    
    /**
     * Base Path برای URL ها
     * @var string
     */
    private string $basePath = '';
    
    /**
     * الگوهای رایج برای پارامترها
     * @var array
     */
    private array $patterns = [
        'id'      => '[0-9]+',
        'slug'    => '[a-z0-9\-]+',
        'uuid'    => '[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}',
        'any'     => '.*',
        'alpha'   => '[a-zA-Z]+',
        'alnum'   => '[a-zA-Z0-9]+',
        'numeric' => '[0-9]+',
    ];
    
    /**
     * Constructor خصوصی (Singleton)
     */
    private function __construct()
    {
        // تنظیم Base Path از config
        if (defined('BASE_PATH')) {
            $this->basePath = rtrim(BASE_PATH, '/');
        }
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
    public static function getInstance(): Router
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // ═══════════════════════════════════════════════════════════
    // ثبت روت‌ها (Route Registration)
    // ═══════════════════════════════════════════════════════════
    
    /**
     * ثبت روت GET
     * 
     * @param string $uri مسیر URL
     * @param mixed $action اکشن (callable یا string)
     * @return Route
     */
    public function get(string $uri, $action): Route
    {
        return $this->addRoute(['GET'], $uri, $action);
    }
    
    /**
     * ثبت روت POST
     */
    public function post(string $uri, $action): Route
    {
        return $this->addRoute(['POST'], $uri, $action);
    }
    
    /**
     * ثبت روت PUT
     */
    public function put(string $uri, $action): Route
    {
        return $this->addRoute(['PUT'], $uri, $action);
    }
    
    /**
     * ثبت روت PATCH
     */
    public function patch(string $uri, $action): Route
    {
        return $this->addRoute(['PATCH'], $uri, $action);
    }
    
    /**
     * ثبت روت DELETE
     */
    public function delete(string $uri, $action): Route
    {
        return $this->addRoute(['DELETE'], $uri, $action);
    }
    
    /**
     * ثبت روت برای چند متد
     * 
     * @param array $methods ['GET', 'POST']
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public function match(array $methods, string $uri, $action): Route
    {
        return $this->addRoute(array_map('strtoupper', $methods), $uri, $action);
    }
    
    /**
     * ثبت روت برای همه متدها
     */
    public function any(string $uri, $action): Route
    {
        return $this->addRoute(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], $uri, $action);
    }
    
    /**
     * ثبت روت‌های CRUD کامل برای یک Resource
     * 
     * @param string $name نام resource (مثلاً customers)
     * @param string $controller کلاس کنترلر
     * @param array $options گزینه‌ها
     */
    public function resource(string $name, string $controller, array $options = []): void
    {
        $only = $options['only'] ?? ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];
        $except = $options['except'] ?? [];
        
        $actions = array_diff($only, $except);
        
        $singular = rtrim($name, 's'); // customers → customer
        
        if (in_array('index', $actions)) {
            $this->get($name, [$controller, 'index'])->name("{$name}.index");
        }
        
        if (in_array('create', $actions)) {
            $this->get("{$name}/create", [$controller, 'create'])->name("{$name}.create");
        }
        
        if (in_array('store', $actions)) {
            $this->post($name, [$controller, 'store'])->name("{$name}.store");
        }
        
        if (in_array('show', $actions)) {
            $this->get("{$name}/{id}", [$controller, 'show'])->name("{$name}.show");
        }
        
        if (in_array('edit', $actions)) {
            $this->get("{$name}/{id}/edit", [$controller, 'edit'])->name("{$name}.edit");
        }
        
        if (in_array('update', $actions)) {
            $this->match(['PUT', 'PATCH'], "{$name}/{id}", [$controller, 'update'])->name("{$name}.update");
        }
        
        if (in_array('destroy', $actions)) {
            $this->delete("{$name}/{id}", [$controller, 'destroy'])->name("{$name}.destroy");
        }
    }
    
    /**
     * اضافه کردن روت به لیست
     */
    private function addRoute(array $methods, string $uri, $action): Route
    {
        // اعمال prefix گروه
        $uri = $this->applyGroupPrefix($uri);
        
        // ساخت شیء Route
        $route = new Route($methods, $uri, $action);
        
        // اعمال middleware گروه
        if (!empty($this->groupStack)) {
            $groupMiddleware = $this->getGroupMiddleware();
            $route->middleware($groupMiddleware);
        }
        
        // ذخیره در لیست
        $this->routes[] = $route;
        
        return $route;
    }
    
    // ═══════════════════════════════════════════════════════════
    // گروه‌بندی روت‌ها (Route Grouping)
    // ═══════════════════════════════════════════════════════════
    
    /**
     * گروه‌بندی روت‌ها با تنظیمات مشترک
     * 
     * @param array $attributes ['prefix' => 'admin', 'middleware' => ['auth']]
     * @param callable $callback
     */
    public function group(array $attributes, callable $callback): void
    {
        // اضافه به stack
        $this->groupStack[] = $attributes;
        
        // اجرای callback
        $callback($this);
        
        // حذف از stack
        array_pop($this->groupStack);
    }
    
    /**
     * اعمال prefix گروه به URI
     */
    private function applyGroupPrefix(string $uri): string
    {
        $prefix = '';
        
        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
        }
        
        return ltrim($prefix . '/' . ltrim($uri, '/'), '/');
    }
    
    /**
     * دریافت middleware های گروه
     */
    private function getGroupMiddleware(): array
    {
        $middleware = [];
        
        foreach ($this->groupStack as $group) {
            if (isset($group['middleware'])) {
                $groupMiddleware = (array) $group['middleware'];
                $middleware = array_merge($middleware, $groupMiddleware);
            }
        }
        
        return array_unique($middleware);
    }
    
    // ═══════════════════════════════════════════════════════════
    // Middleware
    // ═══════════════════════════════════════════════════════════
    
    /**
     * افزودن Middleware سراسری
     * 
     * @param string|array $middleware
     * @return self
     */
    public function addGlobalMiddleware($middleware): self
    {
        $middleware = (array) $middleware;
        $this->globalMiddleware = array_merge($this->globalMiddleware, $middleware);
        return $this;
    }
    
    /**
     * اجرای Middleware ها
     * 
     * @param array $middleware لیست middleware
     * @return bool آیا ادامه دهد؟
     */
    private function runMiddleware(array $middleware): bool
    {
        foreach ($middleware as $mw) {
            $result = $this->executeMiddleware($mw);
            
            if ($result === false) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * اجرای یک Middleware
     */
    private function executeMiddleware(string $middleware)
    {
        // بررسی پارامتر (مثلاً role:admin)
        $params = [];
        if (strpos($middleware, ':') !== false) {
            [$middleware, $paramString] = explode(':', $middleware, 2);
            $params = explode(',', $paramString);
        }
        
        // کلاس Middleware
        $class = 'Middleware\\' . ucfirst($middleware) . 'Middleware';
        
        if (!class_exists($class)) {
            // بررسی در مسیر قدیمی
            $file = ROOT_PATH . '/private/middleware/' . $middleware . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
            
            // تابع middleware (برای سادگی)
            $function = 'middleware_' . $middleware;
            if (function_exists($function)) {
                return call_user_func_array($function, $params);
            }
            
            throw new \Exception("Middleware not found: {$middleware}");
        }
        
        $instance = new $class();
        return $instance->handle($params);
    }
    
    // ═══════════════════════════════════════════════════════════
    // Dispatch (اجرای روت)
    // ═══════════════════════════════════════════════════════════
    
    /**
     * پردازش درخواست و اجرای روت مناسب
     * 
     * @param string|null $uri URI درخواست (اختیاری)
     * @param string|null $method متد HTTP (اختیاری)
     * @return mixed
     */
    public function dispatch(?string $uri = null, ?string $method = null)
    {
        // دریافت URI و Method
        $uri = $uri ?? $this->getRequestUri();
        $method = $method ?? $this->getRequestMethod();
        
        // پیدا کردن روت
        $route = $this->findRoute($uri, $method);
        
        if ($route === null) {
            return $this->handleNotFound();
        }
        
        // ذخیره روت فعلی
        $this->currentRoute = $route;
        
        // اجرای Global Middleware
        if (!$this->runMiddleware($this->globalMiddleware)) {
            return null;
        }
        
        // اجرای Route Middleware
        if (!$this->runMiddleware($route['middleware'])) {
            return null;
        }
        
        // اجرای Action
        return $this->executeAction($route['action'], $this->params);
    }
    
    /**
     * پیدا کردن روت مناسب
     */
    private function findRoute(string $uri, string $method): ?array
    {
        foreach ($this->routes as $route) {
            if (!in_array($method, $route->getMethods())) {
                continue;
            }
            
            $pattern = $this->compilePattern($route->getUri());
            
            if (preg_match($pattern, $uri, $matches)) {
                // استخراج پارامترها
                $this->params = $this->extractParams($route->getUri(), $matches);
                
                return [
                    'route'      => $route,
                    'action'     => $route->getAction(),
                    'middleware' => $route->getMiddleware(),
                    'name'       => $route->getName(),
                ];
            }
        }
        
        return null;
    }
    
    /**
     * کامپایل الگوی روت به Regex
     */
    private function compilePattern(string $uri): string
    {
        // تبدیل {param} به regex
        $pattern = preg_replace_callback(
            '/\{([a-zA-Z_]+)(\?)?\}/',
            function ($matches) {
                $param = $matches[1];
                $optional = isset($matches[2]);
                
                // بررسی الگوی از پیش تعریف شده
                $regex = $this->patterns[$param] ?? '[^/]+';
                
                if ($optional) {
                    return "(?P<{$param}>{$regex})?";
                }
                
                return "(?P<{$param}>{$regex})";
            },
            $uri
        );
        
        return '#^' . $pattern . '$#u';
    }
    
    /**
     * استخراج پارامترها از matches
     */
    private function extractParams(string $uri, array $matches): array
    {
        $params = [];
        
        // فقط پارامترهای نامگذاری شده
        foreach ($matches as $key => $value) {
            if (is_string($key) && $value !== '') {
                $params[$key] = $value;
            }
        }
        
        return $params;
    }
    
    /**
     * اجرای Action
     */
    private function executeAction($action, array $params)
    {
        // Callable (تابع یا Closure)
        if (is_callable($action)) {
            return call_user_func_array($action, $params);
        }
        
        // String (Controller@method)
        if (is_string($action) && strpos($action, '@') !== false) {
            [$controller, $method] = explode('@', $action);
            return $this->callController($controller, $method, $params);
        }
        
        // Array ([Controller::class, 'method'])
        if (is_array($action) && count($action) === 2) {
            return $this->callController($action[0], $action[1], $params);
        }
        
        throw new \Exception("Invalid route action");
    }
    
    /**
     * فراخوانی Controller
     */
    private function callController(string $controller, string $method, array $params)
    {
        // اضافه کردن namespace اگر نداشت
        if (strpos($controller, '\\') === false) {
            $controller = 'Controllers\\' . $controller;
        }
        
        if (!class_exists($controller)) {
            throw new \Exception("Controller not found: {$controller}");
        }
        
        $instance = new $controller();
        
        if (!method_exists($instance, $method)) {
            throw new \Exception("Method {$method} not found in {$controller}");
        }
        
        return call_user_func_array([$instance, $method], $params);
    }
    
    // ═══════════════════════════════════════════════════════════
    // Request Helpers
    // ═══════════════════════════════════════════════════════════
    
    /**
     * دریافت URI درخواست
     */
    private function getRequestUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // حذف query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        // حذف base path
        if ($this->basePath && strpos($uri, $this->basePath) === 0) {
            $uri = substr($uri, strlen($this->basePath));
        }
        
        return '/' . trim($uri, '/');
    }
    
    /**
     * دریافت متد HTTP
     */
    private function getRequestMethod(): string
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // پشتیبانی از Method Override (برای فرم‌ها)
        if ($method === 'POST') {
            // از فیلد _method
            if (isset($_POST['_method'])) {
                $method = strtoupper($_POST['_method']);
            }
            // از هدر X-HTTP-Method-Override
            elseif (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
                $method = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
            }
        }
        
        return $method;
    }
    
    // ═══════════════════════════════════════════════════════════
    // Named Routes & URL Generation
    // ═══════════════════════════════════════════════════════════
    
    /**
     * ثبت نام برای روت
     */
    public function registerNamedRoute(string $name, Route $route): void
    {
        $this->namedRoutes[$name] = $route;
    }
    
    /**
     * تولید URL از نام روت
     * 
     * @param string $name نام روت
     * @param array $params پارامترها
     * @param bool $absolute URL کامل؟
     * @return string
     */
    public function route(string $name, array $params = [], bool $absolute = true): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \Exception("Route not found: {$name}");
        }
        
        $route = $this->namedRoutes[$name];
        $uri = $route->getUri();
        
        // جایگزینی پارامترها
        foreach ($params as $key => $value) {
            $uri = preg_replace('/\{' . $key . '\??\}/', $value, $uri);
        }
        
        // حذف پارامترهای اختیاری باقیمانده
        $uri = preg_replace('/\{[a-zA-Z_]+\?\}/', '', $uri);
        
        $path = $this->basePath . '/' . ltrim($uri, '/');
        
        if ($absolute) {
            $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            return $scheme . '://' . $host . $path;
        }
        
        return $path;
    }
    
    /**
     * بررسی روت فعلی
     * 
     * @param string $name نام روت
     * @return bool
     */
    public function isCurrentRoute(string $name): bool
    {
        if ($this->currentRoute === null) {
            return false;
        }
        
        return ($this->currentRoute['name'] ?? null) === $name;
    }
    
    /**
     * دریافت نام روت فعلی
     */
    public function currentRouteName(): ?string
    {
        return $this->currentRoute['name'] ?? null;
    }
    
    // ═══════════════════════════════════════════════════════════
    // Response Helpers
    // ═══════════════════════════════════════════════════════════
    
    /**
     * Redirect به URL
     */
    public function redirect(string $url, int $status = 302): void
    {
        header("Location: {$url}", true, $status);
        exit;
    }
    
    /**
     * Redirect به روت نامگذاری شده
     */
    public function redirectToRoute(string $name, array $params = [], int $status = 302): void
    {
        $url = $this->route($name, $params);
        $this->redirect($url, $status);
    }
    
    /**
     * Redirect به صفحه قبل
     */
    public function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? $this->basePath . '/';
        $this->redirect($referer);
    }
    
    /**
     * ارسال پاسخ JSON
     */
    public function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * ارسال خطای JSON
     */
    public function jsonError(string $message, int $status = 400, array $errors = []): void
    {
        $this->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }
    
    /**
     * ارسال موفقیت JSON
     */
    public function jsonSuccess($data = null, string $message = 'عملیات موفق'): void
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ]);
    }
    
    // ═══════════════════════════════════════════════════════════
    // Error Handlers
    // ═══════════════════════════════════════════════════════════
    
    /**
     * Handler برای 404
     */
    private function handleNotFound()
    {
        http_response_code(404);
        
        // اگر AJAX بود JSON برگردان
        if ($this->isAjax()) {
            $this->jsonError('صفحه مورد نظر یافت نشد', 404);
        }
        
        // نمایش صفحه 404
        $errorPage = ROOT_PATH . '/private/views/errors/404.php';
        if (file_exists($errorPage)) {
            include $errorPage;
        } else {
            echo '<h1>404 - صفحه یافت نشد</h1>';
        }
        
        exit;
    }
    
    /**
     * بررسی AJAX بودن درخواست
     */
    private function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    // ═══════════════════════════════════════════════════════════
    // Getters
    // ═══════════════════════════════════════════════════════════
    
    /**
     * دریافت پارامترهای روت
     */
    public function getParams(): array
    {
        return $this->params;
    }
    
    /**
     * دریافت یک پارامتر
     */
    public function getParam(string $key, $default = null)
    {
        return $this->params[$key] ?? $default;
    }
    
    /**
     * دریافت تمام روت‌ها (برای debug)
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
    
    /**
     * تنظیم Base Path
     */
    public function setBasePath(string $path): self
    {
        $this->basePath = rtrim($path, '/');
        return $this;
    }
    
    /**
     * افزودن الگوی سفارشی
     */
    public function pattern(string $name, string $regex): self
    {
        $this->patterns[$name] = $regex;
        return $this;
    }
}

// ═══════════════════════════════════════════════════════════════════
// کلاس Route (شیء تکی هر روت)
// ═══════════════════════════════════════════════════════════════════

class Route
{
    private array $methods;
    private string $uri;
    private $action;
    private ?string $name = null;
    private array $middleware = [];
    private array $where = [];
    
    public function __construct(array $methods, string $uri, $action)
    {
        $this->methods = $methods;
        $this->uri = '/' . trim($uri, '/');
        $this->action = $action;
    }
    
    /**
     * نامگذاری روت
     */
    public function name(string $name): self
    {
        $this->name = $name;
        Router::getInstance()->registerNamedRoute($name, $this);
        return $this;
    }
    
    /**
     * افزودن Middleware
     */
    public function middleware($middleware): self
    {
        $middleware = (array) $middleware;
        $this->middleware = array_merge($this->middleware, $middleware);
        return $this;
    }
    
    /**
     * محدودیت پارامتر با regex
     */
    public function where(string $param, string $regex): self
    {
        $this->where[$param] = $regex;
        return $this;
    }
    
    /**
     * محدودیت پارامتر به عدد
     */
    public function whereNumber(string $param): self
    {
        return $this->where($param, '[0-9]+');
    }
    
    /**
     * محدودیت پارامتر به حروف
     */
    public function whereAlpha(string $param): self
    {
        return $this->where($param, '[a-zA-Z]+');
    }
    
    // Getters
    public function getMethods(): array { return $this->methods; }
    public function getUri(): string { return $this->uri; }
    public function getAction() { return $this->action; }
    public function getName(): ?string { return $this->name; }
    public function getMiddleware(): array { return $this->middleware; }
    public function getWhere(): array { return $this->where; }
}

// ═══════════════════════════════════════════════════════════════════
// توابع کمکی سراسری (Global Helper Functions)
// ═══════════════════════════════════════════════════════════════════

if (!function_exists('router')) {
    /**
     * دسترسی به نمونه Router
     */
    function router(): Router
    {
        return Router::getInstance();
    }
}

if (!function_exists('route')) {
    /**
     * تولید URL از نام روت
     * 
     * @param string $name نام روت
     * @param array $params پارامترها
     * @return string
     */
    function route(string $name, array $params = []): string
    {
        return Router::getInstance()->route($name, $params);
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect به URL
     */
    function redirect(string $url, int $status = 302): void
    {
        Router::getInstance()->redirect($url, $status);
    }
}

if (!function_exists('back')) {
    /**
     * برگشت به صفحه قبل
     */
    function back(): void
    {
        Router::getInstance()->back();
    }
}

if (!function_exists('json_response')) {
    /**
     * پاسخ JSON
     */
    function json_response($data, int $status = 200): void
    {
        Router::getInstance()->json($data, $status);
    }
}

if (!function_exists('current_route')) {
    /**
     * نام روت فعلی
     */
    function current_route(): ?string
    {
        return Router::getInstance()->currentRouteName();
    }
}

if (!function_exists('is_route')) {
    /**
     * بررسی روت فعلی
     */
    function is_route(string $name): bool
    {
        return Router::getInstance()->isCurrentRoute($name);
    }
}

if (!function_exists('url')) {
    /**
     * تولید URL کامل
     */
    function url(string $path = ''): string
    {
        $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('method_field')) {
    /**
     * فیلد مخفی برای PUT/DELETE در فرم
     */
    function method_field(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . strtoupper($method) . '">';
    }
}
