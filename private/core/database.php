<?php
/**
 * ReadyCRM v2 - Database Manager
 * 
 * فایل: private/core/Database.php
 * 
 * مدیریت اتصال و عملیات دیتابیس:
 * - Singleton Pattern برای یک اتصال سراسری
 * - Query Builder ساده و امن
 * - پشتیبانی از تراکنش‌ها
 * - Prepared Statements برای جلوگیری از SQL Injection
 * - لاگ کوئری‌ها در حالت Debug
 * - مدیریت خودکار Reconnect
 * 
 * @package ReadyCRM
 * @subpackage Core
 * @version 2.0.0
 * @author ReadyStudio
 */

declare(strict_types=1);

namespace Core;

use PDO;
use PDOException;
use PDOStatement;

/**
 * کلاس مدیریت دیتابیس
 * 
 * الگوی Singleton برای یک اتصال سراسری
 * Query Builder با روش‌های زنجیره‌ای
 * پشتیبانی کامل از UTF-8 فارسی
 */
class Database
{
    /**
     * نمونه Singleton
     * @var self|null
     */
    private static ?self $instance = null;
    
    /**
     * اتصال PDO
     * @var PDO|null
     */
    private ?PDO $pdo = null;
    
    /**
     * تنظیمات اتصال
     * @var array<string, mixed>
     */
    private array $config = [];
    
    /**
     * پیشوند جداول
     * @var string
     */
    private string $prefix = 'crm_';
    
    /**
     * آیا در حال تراکنش هستیم؟
     * @var bool
     */
    private bool $inTransaction = false;
    
    /**
     * سطح تراکنش‌های تودرتو
     * @var int
     */
    private int $transactionLevel = 0;
    
    /**
     * لاگ کوئری‌ها
     * @var array<int, array{query: string, bindings: array, time: float}>
     */
    private array $queryLog = [];
    
    /**
     * آیا لاگ کوئری فعال است؟
     * @var bool
     */
    private bool $enableQueryLog = false;
    
    /**
     * تعداد کل کوئری‌های اجرا شده
     * @var int
     */
    private int $queryCount = 0;
    
    /**
     * زمان کل اجرای کوئری‌ها (میلی‌ثانیه)
     * @var float
     */
    private float $totalQueryTime = 0.0;
    
    // ============================================
    // Query Builder State
    // ============================================
    
    /**
     * جدول فعلی
     * @var string
     */
    private string $table = '';
    
    /**
     * ستون‌های SELECT
     * @var array<int, string>
     */
    private array $selectColumns = ['*'];
    
    /**
     * شرایط WHERE
     * @var array<int, array{type: string, column: string, operator: string, value: mixed, boolean: string}>
     */
    private array $wheres = [];
    
    /**
     * پارامترهای binding
     * @var array<string, mixed>
     */
    private array $bindings = [];
    
    /**
     * شمارنده binding
     * @var int
     */
    private int $bindingCounter = 0;
    
    /**
     * JOIN ها
     * @var array<int, array{type: string, table: string, first: string, operator: string, second: string}>
     */
    private array $joins = [];
    
    /**
     * ORDER BY
     * @var array<int, array{column: string, direction: string}>
     */
    private array $orders = [];
    
    /**
     * GROUP BY
     * @var array<int, string>
     */
    private array $groups = [];
    
    /**
     * HAVING
     * @var array<int, string>
     */
    private array $havings = [];
    
    /**
     * LIMIT
     * @var int|null
     */
    private ?int $limitValue = null;
    
    /**
     * OFFSET
     * @var int|null
     */
    private ?int $offsetValue = null;
    
    /**
     * DISTINCT
     * @var bool
     */
    private bool $distinct = false;
    
    // ============================================
    // Constructor & Singleton
    // ============================================
    
    /**
     * Constructor خصوصی (Singleton)
     * 
     * @param array<string, mixed> $config تنظیمات اتصال
     */
    private function __construct(array $config = [])
    {
        $this->config = $config;
        $this->prefix = $config['prefix'] ?? 'crm_';
        
        // فعال‌سازی لاگ در حالت Debug
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $this->enableQueryLog = true;
        }
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
        throw new \Exception('Cannot unserialize Database singleton');
    }
    
    /**
     * دریافت نمونه Singleton
     * 
     * @param array<string, mixed>|null $config تنظیمات اتصال (فقط بار اول)
     * @return self
     */
    public static function getInstance(?array $config = null): self
    {
        if (self::$instance === null) {
            // دریافت تنظیمات از Config
            if ($config === null && class_exists('Core\Config')) {
                $config = Config::getInstance()->getDatabaseConfig();
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
        if (self::$instance !== null) {
            self::$instance->disconnect();
            self::$instance = null;
        }
    }
    
    // ============================================
    // Connection Management
    // ============================================
    
    /**
     * برقراری اتصال به دیتابیس
     * 
     * @return PDO
     * @throws PDOException
     */
    public function connect(): PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }
        
        $host = $this->config['host'] ?? 'localhost';
        $port = $this->config['port'] ?? 3306;
        $dbname = $this->config['name'] ?? '';
        $username = $this->config['username'] ?? '';
        $password = $this->config['password'] ?? '';
        $charset = $this->config['charset'] ?? 'utf8mb4';
        $collation = $this->config['collation'] ?? 'utf8mb4_persian_ci';
        
        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$collation}",
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ];
        
        try {
            $this->pdo = new PDO($dsn, $username, $password, $options);
            
            // تنظیمات اضافی
            $this->pdo->exec("SET time_zone = '+03:30'"); // ایران
            $this->pdo->exec("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
            
            return $this->pdo;
            
        } catch (PDOException $e) {
            error_log('[Database] Connection failed: ' . $e->getMessage());
            throw new PDOException('Database connection failed: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }
    
    /**
     * قطع اتصال
     */
    public function disconnect(): void
    {
        $this->pdo = null;
    }
    
    /**
     * بررسی اتصال
     * 
     * @return bool
     */
    public function isConnected(): bool
    {
        if ($this->pdo === null) {
            return false;
        }
        
        try {
            $this->pdo->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * بررسی و اتصال مجدد در صورت نیاز
     * 
     * @return PDO
     */
    public function reconnectIfNeeded(): PDO
    {
        if (!$this->isConnected()) {
            $this->disconnect();
            return $this->connect();
        }
        
        return $this->pdo;
    }
    
    /**
     * دریافت اتصال PDO خام
     * 
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->connect();
    }
    
    /**
     * دریافت پیشوند جداول
     * 
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }
    
    /**
     * دریافت نام کامل جدول با پیشوند
     * 
     * @param string $table
     * @return string
     */
    public function tableName(string $table): string
    {
        // اگر قبلاً پیشوند دارد، اضافه نکن
        if (str_starts_with($table, $this->prefix)) {
            return $table;
        }
        
        return $this->prefix . $table;
    }
    
    // ============================================
    // Query Builder - Table Selection
    // ============================================
    
    /**
     * انتخاب جدول
     * 
     * @param string $table نام جدول (بدون پیشوند)
     * @return self
     */
    public function table(string $table): self
    {
        $this->resetBuilder();
        $this->table = $this->tableName($table);
        return $this;
    }
    
    /**
     * بازنشانی Query Builder
     */
    private function resetBuilder(): void
    {
        $this->table = '';
        $this->selectColumns = ['*'];
        $this->wheres = [];
        $this->bindings = [];
        $this->bindingCounter = 0;
        $this->joins = [];
        $this->orders = [];
        $this->groups = [];
        $this->havings = [];
        $this->limitValue = null;
        $this->offsetValue = null;
        $this->distinct = false;
    }
    
    // ============================================
    // Query Builder - SELECT
    // ============================================
    
    /**
     * تعیین ستون‌های SELECT
     * 
     * @param string|array<int, string> $columns
     * @return self
     */
    public function select(string|array $columns = ['*']): self
    {
        if (is_string($columns)) {
            $columns = [$columns];
        }
        
        $this->selectColumns = $columns;
        return $this;
    }
    
    /**
     * افزودن ستون به SELECT
     * 
     * @param string|array<int, string> $columns
     * @return self
     */
    public function addSelect(string|array $columns): self
    {
        if (is_string($columns)) {
            $columns = [$columns];
        }
        
        if ($this->selectColumns === ['*']) {
            $this->selectColumns = [];
        }
        
        $this->selectColumns = array_merge($this->selectColumns, $columns);
        return $this;
    }
    
    /**
     * SELECT DISTINCT
     * 
     * @return self
     */
    public function distinct(): self
    {
        $this->distinct = true;
        return $this;
    }
    
    /**
     * SELECT با Raw Expression
     * 
     * @param string $expression
     * @return self
     */
    public function selectRaw(string $expression): self
    {
        if ($this->selectColumns === ['*']) {
            $this->selectColumns = [];
        }
        
        $this->selectColumns[] = $expression;
        return $this;
    }
    
    // ============================================
    // Query Builder - WHERE
    // ============================================
    
    /**
     * افزودن شرط WHERE
     * 
     * @param string $column
     * @param mixed $operatorOrValue
     * @param mixed $value
     * @param string $boolean
     * @return self
     */
    public function where(string $column, mixed $operatorOrValue = null, mixed $value = null, string $boolean = 'AND'): self
    {
        // اگر فقط دو آرگومان داده شده، عملگر = است
        if ($value === null && $operatorOrValue !== null) {
            $value = $operatorOrValue;
            $operator = '=';
        } else {
            $operator = $operatorOrValue ?? '=';
        }
        
        $bindKey = $this->createBindingKey($column);
        
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $bindKey,
            'boolean' => $boolean,
        ];
        
        $this->bindings[$bindKey] = $value;
        
        return $this;
    }
    
    /**
     * افزودن شرط OR WHERE
     * 
     * @param string $column
     * @param mixed $operatorOrValue
     * @param mixed $value
     * @return self
     */
    public function orWhere(string $column, mixed $operatorOrValue = null, mixed $value = null): self
    {
        return $this->where($column, $operatorOrValue, $value, 'OR');
    }
    
    /**
     * WHERE با چند شرط (آرایه)
     * 
     * @param array<string, mixed> $conditions
     * @param string $boolean
     * @return self
     */
    public function whereArray(array $conditions, string $boolean = 'AND'): self
    {
        foreach ($conditions as $column => $value) {
            $this->where($column, '=', $value, $boolean);
        }
        
        return $this;
    }
    
    /**
     * WHERE IN
     * 
     * @param string $column
     * @param array<int, mixed> $values
     * @param string $boolean
     * @param bool $not
     * @return self
     */
    public function whereIn(string $column, array $values, string $boolean = 'AND', bool $not = false): self
    {
        if (empty($values)) {
            // شرط غیرممکن برای آرایه خالی
            $this->wheres[] = [
                'type' => 'raw',
                'sql' => $not ? '1=1' : '1=0',
                'boolean' => $boolean,
            ];
            return $this;
        }
        
        $bindKeys = [];
        foreach ($values as $val) {
            $bindKey = $this->createBindingKey($column);
            $bindKeys[] = $bindKey;
            $this->bindings[$bindKey] = $val;
        }
        
        $operator = $not ? 'NOT IN' : 'IN';
        
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'operator' => $operator,
            'values' => $bindKeys,
            'boolean' => $boolean,
        ];
        
        return $this;
    }
    
    /**
     * WHERE NOT IN
     * 
     * @param string $column
     * @param array<int, mixed> $values
     * @param string $boolean
     * @return self
     */
    public function whereNotIn(string $column, array $values, string $boolean = 'AND'): self
    {
        return $this->whereIn($column, $values, $boolean, true);
    }
    
    /**
     * WHERE NULL
     * 
     * @param string $column
     * @param string $boolean
     * @param bool $not
     * @return self
     */
    public function whereNull(string $column, string $boolean = 'AND', bool $not = false): self
    {
        $operator = $not ? 'IS NOT NULL' : 'IS NULL';
        
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'operator' => $operator,
            'boolean' => $boolean,
        ];
        
        return $this;
    }
    
    /**
     * WHERE NOT NULL
     * 
     * @param string $column
     * @param string $boolean
     * @return self
     */
    public function whereNotNull(string $column, string $boolean = 'AND'): self
    {
        return $this->whereNull($column, $boolean, true);
    }
    
    /**
     * WHERE BETWEEN
     * 
     * @param string $column
     * @param mixed $min
     * @param mixed $max
     * @param string $boolean
     * @param bool $not
     * @return self
     */
    public function whereBetween(string $column, mixed $min, mixed $max, string $boolean = 'AND', bool $not = false): self
    {
        $minKey = $this->createBindingKey($column . '_min');
        $maxKey = $this->createBindingKey($column . '_max');
        
        $this->bindings[$minKey] = $min;
        $this->bindings[$maxKey] = $max;
        
        $operator = $not ? 'NOT BETWEEN' : 'BETWEEN';
        
        $this->wheres[] = [
            'type' => 'between',
            'column' => $column,
            'operator' => $operator,
            'min' => $minKey,
            'max' => $maxKey,
            'boolean' => $boolean,
        ];
        
        return $this;
    }
    
    /**
     * WHERE LIKE
     * 
     * @param string $column
     * @param string $pattern
     * @param string $boolean
     * @return self
     */
    public function whereLike(string $column, string $pattern, string $boolean = 'AND'): self
    {
        return $this->where($column, 'LIKE', $pattern, $boolean);
    }
    
    /**
     * WHERE Raw SQL
     * 
     * @param string $sql
     * @param array<string, mixed> $bindings
     * @param string $boolean
     * @return self
     */
    public function whereRaw(string $sql, array $bindings = [], string $boolean = 'AND'): self
    {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => $sql,
            'boolean' => $boolean,
        ];
        
        foreach ($bindings as $key => $value) {
            $this->bindings[$key] = $value;
        }
        
        return $this;
    }
    
    /**
     * ایجاد کلید binding یکتا
     * 
     * @param string $column
     * @return string
     */
    private function createBindingKey(string $column): string
    {
        $cleanColumn = preg_replace('/[^a-zA-Z0-9_]/', '_', $column);
        return ':' . $cleanColumn . '_' . (++$this->bindingCounter);
    }
    
    // ============================================
    // Query Builder - JOIN
    // ============================================
    
    /**
     * INNER JOIN
     * 
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @return self
     */
    public function join(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'INNER',
            'table' => $this->tableName($table),
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];
        
        return $this;
    }
    
    /**
     * LEFT JOIN
     * 
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @return self
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'LEFT',
            'table' => $this->tableName($table),
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];
        
        return $this;
    }
    
    /**
     * RIGHT JOIN
     * 
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @return self
     */
    public function rightJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'RIGHT',
            'table' => $this->tableName($table),
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];
        
        return $this;
    }
    
    // ============================================
    // Query Builder - ORDER, GROUP, LIMIT
    // ============================================
    
    /**
     * ORDER BY
     * 
     * @param string $column
     * @param string $direction
     * @return self
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        
        $this->orders[] = [
            'column' => $column,
            'direction' => $direction,
        ];
        
        return $this;
    }
    
    /**
     * ORDER BY DESC
     * 
     * @param string $column
     * @return self
     */
    public function orderByDesc(string $column): self
    {
        return $this->orderBy($column, 'DESC');
    }
    
    /**
     * ORDER BY جدیدترین (بر اساس created_at)
     * 
     * @param string $column
     * @return self
     */
    public function latest(string $column = 'created_at'): self
    {
        return $this->orderBy($column, 'DESC');
    }
    
    /**
     * ORDER BY قدیمی‌ترین
     * 
     * @param string $column
     * @return self
     */
    public function oldest(string $column = 'created_at'): self
    {
        return $this->orderBy($column, 'ASC');
    }
    
    /**
     * GROUP BY
     * 
     * @param string|array<int, string> $columns
     * @return self
     */
    public function groupBy(string|array $columns): self
    {
        if (is_string($columns)) {
            $columns = [$columns];
        }
        
        $this->groups = array_merge($this->groups, $columns);
        return $this;
    }
    
    /**
     * HAVING
     * 
     * @param string $raw
     * @return self
     */
    public function havingRaw(string $raw): self
    {
        $this->havings[] = $raw;
        return $this;
    }
    
    /**
     * LIMIT
     * 
     * @param int $limit
     * @return self
     */
    public function limit(int $limit): self
    {
        $this->limitValue = max(0, $limit);
        return $this;
    }
    
    /**
     * OFFSET
     * 
     * @param int $offset
     * @return self
     */
    public function offset(int $offset): self
    {
        $this->offsetValue = max(0, $offset);
        return $this;
    }
    
    /**
     * صفحه‌بندی
     * 
     * @param int $page شماره صفحه (از 1)
     * @param int $perPage تعداد در هر صفحه
     * @return self
     */
    public function paginate(int $page, int $perPage = 20): self
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        
        $this->limitValue = $perPage;
        $this->offsetValue = ($page - 1) * $perPage;
        
        return $this;
    }
    
    // ============================================
    // Query Builder - Build SQL
    // ============================================
    
    /**
     * ساخت کوئری SELECT
     * 
     * @return string
     */
    private function buildSelectQuery(): string
    {
        $sql = 'SELECT ';
        
        if ($this->distinct) {
            $sql .= 'DISTINCT ';
        }
        
        $sql .= implode(', ', $this->selectColumns);
        $sql .= ' FROM ' . $this->table;
        
        // JOINs
        foreach ($this->joins as $join) {
            $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
        }
        
        // WHERE
        $sql .= $this->buildWhereClause();
        
        // GROUP BY
        if (!empty($this->groups)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groups);
        }
        
        // HAVING
        if (!empty($this->havings)) {
            $sql .= ' HAVING ' . implode(' AND ', $this->havings);
        }
        
        // ORDER BY
        if (!empty($this->orders)) {
            $orderParts = array_map(
                fn($o) => "{$o['column']} {$o['direction']}",
                $this->orders
            );
            $sql .= ' ORDER BY ' . implode(', ', $orderParts);
        }
        
        // LIMIT & OFFSET
        if ($this->limitValue !== null) {
            $sql .= ' LIMIT ' . $this->limitValue;
            
            if ($this->offsetValue !== null) {
                $sql .= ' OFFSET ' . $this->offsetValue;
            }
        }
        
        return $sql;
    }
    
    /**
     * ساخت بخش WHERE
     * 
     * @return string
     */
    private function buildWhereClause(): string
    {
        if (empty($this->wheres)) {
            return '';
        }
        
        $clauses = [];
        
        foreach ($this->wheres as $i => $where) {
            $clause = '';
            
            if ($i > 0) {
                $clause = ' ' . $where['boolean'] . ' ';
            }
            
            switch ($where['type']) {
                case 'basic':
                    $clause .= "{$where['column']} {$where['operator']} {$where['value']}";
                    break;
                    
                case 'in':
                    $placeholders = implode(', ', $where['values']);
                    $clause .= "{$where['column']} {$where['operator']} ({$placeholders})";
                    break;
                    
                case 'null':
                    $clause .= "{$where['column']} {$where['operator']}";
                    break;
                    
                case 'between':
                    $clause .= "{$where['column']} {$where['operator']} {$where['min']} AND {$where['max']}";
                    break;
                    
                case 'raw':
                    $clause .= $where['sql'];
                    break;
            }
            
            $clauses[] = $clause;
        }
        
        return ' WHERE ' . implode('', $clauses);
    }
    
    // ============================================
    // Query Builder - Execute SELECT
    // ============================================
    
    /**
     * اجرای کوئری و دریافت همه نتایج
     * 
     * @return array<int, array<string, mixed>>
     */
    public function get(): array
    {
        $sql = $this->buildSelectQuery();
        $stmt = $this->executeQuery($sql, $this->bindings);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->resetBuilder();
        
        return $result;
    }
    
    /**
     * دریافت اولین نتیجه
     * 
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        
        return $results[0] ?? null;
    }
    
    /**
     * دریافت یک رکورد با ID
     * 
     * @param int|string $id
     * @param string $column
     * @return array<string, mixed>|null
     */
    public function find(int|string $id, string $column = 'id'): ?array
    {
        return $this->where($column, $id)->first();
    }
    
    /**
     * دریافت مقدار یک ستون
     * 
     * @param string $column
     * @return mixed
     */
    public function value(string $column): mixed
    {
        $this->select($column)->limit(1);
        $row = $this->first();
        
        return $row[$column] ?? null;
    }
    
    /**
     * دریافت آرایه از یک ستون
     * 
     * @param string $column
     * @param string|null $key
     * @return array<int|string, mixed>
     */
    public function pluck(string $column, ?string $key = null): array
    {
        if ($key !== null) {
            $this->select([$key, $column]);
        } else {
            $this->select($column);
        }
        
        $results = $this->get();
        
        if ($key !== null) {
            $plucked = [];
            foreach ($results as $row) {
                $plucked[$row[$key]] = $row[$column];
            }
            return $plucked;
        }
        
        return array_column($results, $column);
    }
    
    /**
     * شمارش نتایج
     * 
     * @param string $column
     * @return int
     */
    public function count(string $column = '*'): int
    {
        $this->selectColumns = ["COUNT({$column}) as aggregate"];
        $row = $this->first();
        
        return (int) ($row['aggregate'] ?? 0);
    }
    
    /**
     * مجموع
     * 
     * @param string $column
     * @return float
     */
    public function sum(string $column): float
    {
        $this->selectColumns = ["SUM({$column}) as aggregate"];
        $row = $this->first();
        
        return (float) ($row['aggregate'] ?? 0);
    }
    
    /**
     * میانگین
     * 
     * @param string $column
     * @return float
     */
    public function avg(string $column): float
    {
        $this->selectColumns = ["AVG({$column}) as aggregate"];
        $row = $this->first();
        
        return (float) ($row['aggregate'] ?? 0);
    }
    
    /**
     * حداقل
     * 
     * @param string $column
     * @return mixed
     */
    public function min(string $column): mixed
    {
        $this->selectColumns = ["MIN({$column}) as aggregate"];
        $row = $this->first();
        
        return $row['aggregate'] ?? null;
    }
    
    /**
     * حداکثر
     * 
     * @param string $column
     * @return mixed
     */
    public function max(string $column): mixed
    {
        $this->selectColumns = ["MAX({$column}) as aggregate"];
        $row = $this->first();
        
        return $row['aggregate'] ?? null;
    }
    
    /**
     * بررسی وجود نتیجه
     * 
     * @return bool
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }
    
    /**
     * بررسی عدم وجود نتیجه
     * 
     * @return bool
     */
    public function doesntExist(): bool
    {
        return !$this->exists();
    }
    
    // ============================================
    // Query Builder - INSERT
    // ============================================
    
    /**
     * درج رکورد جدید
     * 
     * @param array<string, mixed> $data
     * @return int شناسه رکورد جدید
     */
    public function insert(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = [];
        $bindings = [];
        
        foreach ($data as $column => $value) {
            $bindKey = ':' . $column;
            $placeholders[] = $bindKey;
            $bindings[$bindKey] = $value;
        }
        
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
        
        $this->executeQuery($sql, $bindings);
        $lastId = (int) $this->connect()->lastInsertId();
        
        $this->resetBuilder();
        
        return $lastId;
    }
    
    /**
     * درج چند رکورد
     * 
     * @param array<int, array<string, mixed>> $rows
     * @return int تعداد رکوردهای درج شده
     */
    public function insertMany(array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }
        
        $columns = array_keys($rows[0]);
        $allPlaceholders = [];
        $bindings = [];
        $rowNum = 0;
        
        foreach ($rows as $row) {
            $placeholders = [];
            foreach ($columns as $column) {
                $bindKey = ":{$column}_{$rowNum}";
                $placeholders[] = $bindKey;
                $bindings[$bindKey] = $row[$column] ?? null;
            }
            $allPlaceholders[] = '(' . implode(', ', $placeholders) . ')';
            $rowNum++;
        }
        
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES %s',
            $this->table,
            implode(', ', $columns),
            implode(', ', $allPlaceholders)
        );
        
        $stmt = $this->executeQuery($sql, $bindings);
        $count = $stmt->rowCount();
        
        $this->resetBuilder();
        
        return $count;
    }
    
    /**
     * درج یا بروزرسانی (UPSERT)
     * 
     * @param array<string, mixed> $data
     * @param array<int, string> $updateColumns ستون‌هایی که در صورت وجود بروز شوند
     * @return int
     */
    public function insertOrUpdate(array $data, array $updateColumns = []): int
    {
        $columns = array_keys($data);
        $placeholders = [];
        $bindings = [];
        
        foreach ($data as $column => $value) {
            $bindKey = ':' . $column;
            $placeholders[] = $bindKey;
            $bindings[$bindKey] = $value;
        }
        
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
        
        // ON DUPLICATE KEY UPDATE
        if (empty($updateColumns)) {
            $updateColumns = $columns;
        }
        
        $updates = array_map(
            fn($col) => "{$col} = VALUES({$col})",
            $updateColumns
        );
        
        $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
        
        $stmt = $this->executeQuery($sql, $bindings);
        $affected = $stmt->rowCount();
        
        $this->resetBuilder();
        
        return $affected;
    }
    
    // ============================================
    // Query Builder - UPDATE
    // ============================================
    
    /**
     * بروزرسانی رکوردها
     * 
     * @param array<string, mixed> $data
     * @return int تعداد رکوردهای تغییر یافته
     */
    public function update(array $data): int
    {
        $setParts = [];
        $setBindings = [];
        
        foreach ($data as $column => $value) {
            $bindKey = ':set_' . $column;
            $setParts[] = "{$column} = {$bindKey}";
            $setBindings[$bindKey] = $value;
        }
        
        $sql = sprintf(
            'UPDATE %s SET %s',
            $this->table,
            implode(', ', $setParts)
        );
        
        $sql .= $this->buildWhereClause();
        
        // ترکیب bindings
        $allBindings = array_merge($setBindings, $this->bindings);
        
        $stmt = $this->executeQuery($sql, $allBindings);
        $affected = $stmt->rowCount();
        
        $this->resetBuilder();
        
        return $affected;
    }
    
    /**
     * افزایش مقدار ستون
     * 
     * @param string $column
     * @param int|float $amount
     * @return int
     */
    public function increment(string $column, int|float $amount = 1): int
    {
        $sql = sprintf(
            'UPDATE %s SET %s = %s + :amount',
            $this->table,
            $column,
            $column
        );
        
        $sql .= $this->buildWhereClause();
        
        $this->bindings[':amount'] = $amount;
        
        $stmt = $this->executeQuery($sql, $this->bindings);
        $affected = $stmt->rowCount();
        
        $this->resetBuilder();
        
        return $affected;
    }
    
    /**
     * کاهش مقدار ستون
     * 
     * @param string $column
     * @param int|float $amount
     * @return int
     */
    public function decrement(string $column, int|float $amount = 1): int
    {
        return $this->increment($column, -$amount);
    }
    
    // ============================================
    // Query Builder - DELETE
    // ============================================
    
    /**
     * حذف رکوردها
     * 
     * @return int تعداد رکوردهای حذف شده
     */
    public function delete(): int
    {
        $sql = 'DELETE FROM ' . $this->table;
        $sql .= $this->buildWhereClause();
        
        $stmt = $this->executeQuery($sql, $this->bindings);
        $affected = $stmt->rowCount();
        
        $this->resetBuilder();
        
        return $affected;
    }
    
    /**
     * پاکسازی کامل جدول
     * 
     * @return bool
     */
    public function truncate(): bool
    {
        $sql = 'TRUNCATE TABLE ' . $this->table;
        $this->executeQuery($sql, []);
        
        $this->resetBuilder();
        
        return true;
    }
    
    // ============================================
    // Raw Queries
    // ============================================
    
    /**
     * اجرای کوئری خام
     * 
     * @param string $sql
     * @param array<string, mixed> $bindings
     * @return PDOStatement
     */
    public function raw(string $sql, array $bindings = []): PDOStatement
    {
        return $this->executeQuery($sql, $bindings);
    }
    
    /**
     * اجرای SELECT خام
     * 
     * @param string $sql
     * @param array<string, mixed> $bindings
     * @return array<int, array<string, mixed>>
     */
    public function selectRawQuery(string $sql, array $bindings = []): array
    {
        $stmt = $this->executeQuery($sql, $bindings);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * اجرای کوئری و دریافت تعداد تأثیر یافته
     * 
     * @param string $sql
     * @param array<string, mixed> $bindings
     * @return int
     */
    public function statement(string $sql, array $bindings = []): int
    {
        $stmt = $this->executeQuery($sql, $bindings);
        return $stmt->rowCount();
    }
    
    /**
     * اجرای کوئری واقعی
     * 
     * @param string $sql
     * @param array<string, mixed> $bindings
     * @return PDOStatement
     * @throws PDOException
     */
    private function executeQuery(string $sql, array $bindings): PDOStatement
    {
        $startTime = microtime(true);
        
        try {
            $pdo = $this->reconnectIfNeeded();
            $stmt = $pdo->prepare($sql);
            
            foreach ($bindings as $key => $value) {
                $type = match (true) {
                    is_int($value) => PDO::PARAM_INT,
                    is_bool($value) => PDO::PARAM_BOOL,
                    is_null($value) => PDO::PARAM_NULL,
                    default => PDO::PARAM_STR,
                };
                
                $stmt->bindValue($key, $value, $type);
            }
            
            $stmt->execute();
            
            $this->queryCount++;
            $queryTime = (microtime(true) - $startTime) * 1000;
            $this->totalQueryTime += $queryTime;
            
            // لاگ کوئری
            if ($this->enableQueryLog) {
                $this->queryLog[] = [
                    'query' => $sql,
                    'bindings' => $bindings,
                    'time' => $queryTime,
                ];
            }
            
            return $stmt;
            
        } catch (PDOException $e) {
            error_log("[Database] Query failed: {$sql} | Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    // ============================================
    // Transactions
    // ============================================
    
    /**
     * شروع تراکنش
     * 
     * @return bool
     */
    public function beginTransaction(): bool
    {
        $this->transactionLevel++;
        
        if ($this->transactionLevel === 1) {
            $this->inTransaction = true;
            return $this->connect()->beginTransaction();
        }
        
        // Savepoint برای تراکنش‌های تودرتو
        $this->connect()->exec("SAVEPOINT trans_{$this->transactionLevel}");
        return true;
    }
    
    /**
     * تأیید تراکنش
     * 
     * @return bool
     */
    public function commit(): bool
    {
        if ($this->transactionLevel === 0) {
            return false;
        }
        
        if ($this->transactionLevel === 1) {
            $this->transactionLevel = 0;
            $this->inTransaction = false;
            return $this->connect()->commit();
        }
        
        $this->transactionLevel--;
        return true;
    }
    
    /**
     * برگشت تراکنش
     * 
     * @return bool
     */
    public function rollBack(): bool
    {
        if ($this->transactionLevel === 0) {
            return false;
        }
        
        if ($this->transactionLevel === 1) {
            $this->transactionLevel = 0;
            $this->inTransaction = false;
            return $this->connect()->rollBack();
        }
        
        // Rollback به Savepoint
        $this->connect()->exec("ROLLBACK TO SAVEPOINT trans_{$this->transactionLevel}");
        $this->transactionLevel--;
        return true;
    }
    
    /**
     * اجرای callback در تراکنش
     * 
     * @param callable $callback
     * @return mixed
     * @throws \Throwable
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
            
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }
    
    /**
     * آیا در تراکنش هستیم؟
     * 
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->inTransaction;
    }
    
    // ============================================
    // Utilities
    // ============================================
    
    /**
     * دریافت لاگ کوئری‌ها
     * 
     * @return array<int, array{query: string, bindings: array, time: float}>
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }
    
    /**
     * فعال/غیرفعال کردن لاگ
     * 
     * @param bool $enable
     */
    public function enableQueryLogging(bool $enable = true): void
    {
        $this->enableQueryLog = $enable;
        
        if (!$enable) {
            $this->queryLog = [];
        }
    }
    
    /**
     * دریافت آمار کوئری‌ها
     * 
     * @return array{count: int, total_time: float, avg_time: float}
     */
    public function getQueryStats(): array
    {
        return [
            'count' => $this->queryCount,
            'total_time' => round($this->totalQueryTime, 2),
            'avg_time' => $this->queryCount > 0 
                ? round($this->totalQueryTime / $this->queryCount, 2) 
                : 0,
        ];
    }
    
    /**
     * دریافت ID آخرین رکورد درج شده
     * 
     * @return string
     */
    public function lastInsertId(): string
    {
        return $this->connect()->lastInsertId();
    }
    
    /**
     * Escape کردن رشته
     * 
     * @param string $value
     * @return string
     */
    public function quote(string $value): string
    {
        return $this->connect()->quote($value);
    }
    
    /**
     * بررسی وجود جدول
     * 
     * @param string $table
     * @return bool
     */
    public function tableExists(string $table): bool
    {
        $fullTable = $this->tableName($table);
        
        try {
            $result = $this->selectRawQuery(
                "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table LIMIT 1",
                [':table' => $fullTable]
            );
            
            return !empty($result);
            
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * دریافت لیست جداول
     * 
     * @return array<int, string>
     */
    public function getTables(): array
    {
        $result = $this->selectRawQuery("SHOW TABLES");
        return array_column($result, array_key_first($result[0] ?? ['Tables_in_' => null]));
    }
    
    /**
     * دریافت ستون‌های جدول
     * 
     * @param string $table
     * @return array<int, array<string, mixed>>
     */
    public function getColumns(string $table): array
    {
        return $this->selectRawQuery("DESCRIBE " . $this->tableName($table));
    }
}

// ============================================
// توابع کمکی سراسری
// ============================================

if (!function_exists('db')) {
    /**
     * دسترسی سریع به Database
     * 
     * @return Database
     */
    function db(): Database
    {
        return Database::getInstance();
    }
}

if (!function_exists('table')) {
    /**
     * شروع Query Builder روی جدول
     * 
     * @param string $table
     * @return Database
     */
    function table(string $table): Database
    {
        return Database::getInstance()->table($table);
    }
}
