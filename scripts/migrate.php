<?php
/**
 * Migration Runner - نسخه بهبود یافته
 * اجرای Migrationها به ترتیب صحیح
 */

// مسیرهای اصلی
define('BASE_PATH', dirname(__DIR__));
define('MIGRATION_PATH', BASE_PATH . '/database/migrations');

// رنگ‌ها برای CLI
class Colors {
    const RED = "\033[0;31m";
    const GREEN = "\033[0;32m";
    const YELLOW = "\033[1;33m";
    const BLUE = "\033[0;34m";
    const NC = "\033[0m"; // No Color
}

/**
 * نمایش پیام رنگی
 */
function printMessage($message, $color = Colors::NC) {
    echo $color . $message . Colors::NC . PHP_EOL;
}

/**
 * خواندن اطلاعات اتصال از config
 */
function getDbConnection() {
    $configFile = BASE_PATH . '/private/config.php';
    
    if (!file_exists($configFile)) {
        throw new Exception("فایل config.php یافت نشد!");
    }
    
    require_once $configFile;
    
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]);
        
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception("خطا در اتصال به دیتابیس: " . $e->getMessage());
    }
}

/**
 * ایجاد جدول migrations در صورت عدم وجود
 */
function createMigrationsTable($pdo) {
    $sql = "CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        batch INT NOT NULL,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    try {
        $pdo->exec($sql);
        return true;
    } catch (PDOException $e) {
        printMessage("خطا در ایجاد جدول migrations: " . $e->getMessage(), Colors::RED);
        return false;
    }
}

/**
 * دریافت لیست Migrationهای اجرا شده
 */
function getExecutedMigrations($pdo) {
    try {
        $stmt = $pdo->query("SELECT migration FROM migrations ORDER BY id");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        // اگر جدول وجود نداشت، آرایه خالی برمی‌گردانیم
        return [];
    }
}

/**
 * دریافت لیست فایل‌های Migration
 */
function getMigrationFiles() {
    if (!is_dir(MIGRATION_PATH)) {
        throw new Exception("پوشه migrations یافت نشد: " . MIGRATION_PATH);
    }
    
    $files = glob(MIGRATION_PATH . '/*.sql');
    
    if (empty($files)) {
        throw new Exception("هیچ فایل migration یافت نشد!");
    }
    
    // مرتب‌سازی بر اساس نام فایل
    sort($files);
    
    return $files;
}

/**
 * اجرای یک فایل Migration
 */
function executeMigration($pdo, $filePath) {
    $fileName = basename($filePath);
    
    printMessage("▶ در حال اجرای: $fileName", Colors::BLUE);
    
    // خواندن محتوای فایل
    $sql = file_get_contents($filePath);
    
    if (empty(trim($sql))) {
        printMessage("  ⚠ فایل خالی است!", Colors::YELLOW);
        return false;
    }
    
    // تقسیم دستورات SQL
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    if (empty($statements)) {
        printMessage("  ⚠ هیچ دستور SQL معتبری یافت نشد!", Colors::YELLOW);
        return false;
    }
    
    try {
        $pdo->beginTransaction();
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        $pdo->commit();
        printMessage("  ✓ اجرا موفق", Colors::GREEN);
        return true;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        printMessage("  ✗ خطا: " . $e->getMessage(), Colors::RED);
        return false;
    }
}

/**
 * ثبت Migration در جدول
 */
function recordMigration($pdo, $fileName, $batch) {
    try {
        $stmt = $pdo->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
        $stmt->execute([$fileName, $batch]);
        return true;
    } catch (PDOException $e) {
        printMessage("خطا در ثبت migration: " . $e->getMessage(), Colors::RED);
        return false;
    }
}

/**
 * دستور migrate
 */
function runMigrate() {
    printMessage("\n╔════════════════════════════════════╗", Colors::BLUE);
    printMessage("║   اجرای Migrationها               ║", Colors::BLUE);
    printMessage("╚════════════════════════════════════╝\n", Colors::BLUE);
    
    try {
        $pdo = getDbConnection();
        
        // ایجاد جدول migrations
        createMigrationsTable($pdo);
        
        // دریافت Migrationهای اجرا شده
        $executed = getExecutedMigrations($pdo);
        
        // دریافت فایل‌های Migration
        $files = getMigrationFiles();
        
        // محاسبه Batch جدید
        $stmt = $pdo->query("SELECT IFNULL(MAX(batch), 0) + 1 as next_batch FROM migrations");
        $batch = $stmt->fetch()['next_batch'];
        
        $runCount = 0;
        $errorCount = 0;
        
        foreach ($files as $filePath) {
            $fileName = basename($filePath);
            
            // بررسی اجرا شدن قبلی
            if (in_array($fileName, $executed)) {
                printMessage("⊘ قبلاً اجرا شده: $fileName", Colors::YELLOW);
                continue;
            }
            
            // اجرای Migration
            if (executeMigration($pdo, $filePath)) {
                recordMigration($pdo, $fileName, $batch);
                $runCount++;
            } else {
                $errorCount++;
            }
        }
        
        printMessage("\n" . str_repeat("─", 40), Colors::BLUE);
        printMessage("✓ تعداد اجرا شده: $runCount", Colors::GREEN);
        if ($errorCount > 0) {
            printMessage("✗ تعداد خطا: $errorCount", Colors::RED);
        }
        printMessage(str_repeat("─", 40) . "\n", Colors::BLUE);
        
    } catch (Exception $e) {
        printMessage("خطای کلی: " . $e->getMessage(), Colors::RED);
        exit(1);
    }
}

/**
 * دستور rollback
 */
function runRollback() {
    printMessage("\n╔════════════════════════════════════╗", Colors::YELLOW);
    printMessage("║   بازگشت آخرین Batch              ║", Colors::YELLOW);
    printMessage("╚════════════════════════════════════╝\n", Colors::YELLOW);
    
    try {
        $pdo = getDbConnection();
        
        // دریافت آخرین batch
        $stmt = $pdo->query("SELECT MAX(batch) as last_batch FROM migrations");
        $lastBatch = $stmt->fetch()['last_batch'];
        
        if (!$lastBatch) {
            printMessage("هیچ migration برای بازگشت وجود ندارد!", Colors::YELLOW);
            return;
        }
        
        // دریافت Migrationهای آخرین batch
        $stmt = $pdo->prepare("SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC");
        $stmt->execute([$lastBatch]);
        $migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        printMessage("در حال بازگشت Batch #$lastBatch...\n", Colors::YELLOW);
        
        foreach ($migrations as $migration) {
            printMessage("⟲ بازگشت: $migration", Colors::YELLOW);
            
            // حذف از جدول migrations
            $stmt = $pdo->prepare("DELETE FROM migrations WHERE migration = ?");
            $stmt->execute([$migration]);
        }
        
        printMessage("\n✓ بازگشت با موفقیت انجام شد!", Colors::GREEN);
        
    } catch (Exception $e) {
        printMessage("خطا: " . $e->getMessage(), Colors::RED);
        exit(1);
    }
}

/**
 * دستور status
 */
function runStatus() {
    printMessage("\n╔════════════════════════════════════╗", Colors::BLUE);
    printMessage("║   وضعیت Migrationها              ║", Colors::BLUE);
    printMessage("╚════════════════════════════════════╝\n", Colors::BLUE);
    
    try {
        $pdo = getDbConnection();
        createMigrationsTable($pdo);
        
        $executed = getExecutedMigrations($pdo);
        $files = getMigrationFiles();
        
        printMessage("Migration                        | وضعیت    | Batch");
        printMessage(str_repeat("─", 60));
        
        foreach ($files as $filePath) {
            $fileName = basename($filePath);
            
            if (in_array($fileName, $executed)) {
                // دریافت batch
                $stmt = $pdo->prepare("SELECT batch FROM migrations WHERE migration = ?");
                $stmt->execute([$fileName]);
                $batch = $stmt->fetch()['batch'];
                
                printMessage(
                    str_pad($fileName, 32) . " | " . 
                    Colors::GREEN . "✓ اجرا شده" . Colors::NC . " | #$batch"
                );
            } else {
                printMessage(
                    str_pad($fileName, 32) . " | " . 
                    Colors::YELLOW . "⊘ منتظر    " . Colors::NC . " | -"
                );
            }
        }
        
        printMessage("\n" . str_repeat("─", 60));
        printMessage("کل: " . count($files) . " | اجرا شده: " . count($executed) . " | منتظر: " . (count($files) - count($executed)));
        printMessage(str_repeat("─", 60) . "\n");
        
    } catch (Exception $e) {
        printMessage("خطا: " . $e->getMessage(), Colors::RED);
        exit(1);
    }
}

/**
 * دستور reset
 */
function runReset() {
    printMessage("\n╔════════════════════════════════════╗", Colors::RED);
    printMessage("║   ⚠ بازنشانی کامل دیتابیس ⚠      ║", Colors::RED);
    printMessage("╚════════════════════════════════════╝\n", Colors::RED);
    
    echo "آیا مطمئن هستید؟ تمام داده‌ها حذف خواهد شد! (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $confirm = trim(fgets($handle));
    fclose($handle);
    
    if ($confirm !== 'yes') {
        printMessage("عملیات لغو شد.", Colors::YELLOW);
        return;
    }
    
    try {
        $pdo = getDbConnection();
        
        // دریافت لیست تمام جداول
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tables)) {
            printMessage("دیتابیس خالی است!", Colors::YELLOW);
            return;
        }
        
        // غیرفعال کردن foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        printMessage("در حال حذف جداول...\n", Colors::RED);
        
        foreach ($tables as $table) {
            printMessage("  ✗ حذف جدول: $table", Colors::RED);
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
        }
        
        // فعال کردن foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        printMessage("\n✓ تمام جداول با موفقیت حذف شدند!", Colors::GREEN);
        printMessage("برای بازسازی دیتابیس، دستور migrate را اجرا کنید.\n", Colors::BLUE);
        
    } catch (Exception $e) {
        printMessage("خطا: " . $e->getMessage(), Colors::RED);
        exit(1);
    }
}

/**
 * نمایش راهنما
 */
function showHelp() {
    printMessage("\n╔════════════════════════════════════╗", Colors::BLUE);
    printMessage("║   راهنمای Migration Runner        ║", Colors::BLUE);
    printMessage("╚════════════════════════════════════╝\n", Colors::BLUE);
    
    echo <<<HELP
دستورات موجود:

  migrate      اجرای تمام Migrationهای جدید
  rollback     بازگشت آخرین Batch از Migrationها
  status       نمایش وضعیت Migrationها
  reset        حذف کامل تمام جداول (خطرناک!)
  help         نمایش این راهنما

استفاده:
  php migrate.php [command]

مثال:
  php migrate.php migrate
  php migrate.php status
  php migrate.php rollback


HELP;
}

// ═══════════════════════════════════════
// اجرای برنامه
// ═══════════════════════════════════════

// بررسی اجرا از CLI
if (php_sapi_name() !== 'cli') {
    die("این اسکریپت فقط از command line قابل اجرا است!\n");
}

$command = $argv[1] ?? 'help';

switch ($command) {
    case 'migrate':
        runMigrate();
        break;
    case 'rollback':
        runRollback();
        break;
    case 'status':
        runStatus();
        break;
    case 'reset':
        runReset();
        break;
    case 'help':
    default:
        showHelp();
        break;
}
