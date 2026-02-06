<?php
/**
 * مرحله 3: اجرای Migration و ساخت جداول
 */

if (!isset($_SESSION['db_config'])) {
    header('Location: ?step=2');
    exit;
}

$db_config = $_SESSION['db_config'];
$error = '';
$success = '';
$migration_results = [];

// اجرای migration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migrations'])) {
    try {
        $pdo = new PDO(
            "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8mb4",
            $db_config['user'],
            $db_config['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // ایجاد جدول migrations اگر وجود ندارد
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration_name VARCHAR(255) UNIQUE NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // فهرست فایل‌های migration
        $migration_files = [
            '001_init.sql',
            '002_catalog.sql',
            '003_inventory.sql',
            '004_sales.sql',
            '005_woocommerce.sql',
            '006_ai.sql'
        ];
        
        $migrations_path = __DIR__ . '/../../database/migrations/';
        
        foreach ($migration_files as $file) {
            // بررسی اجرای قبلی
            $stmt = $pdo->prepare("SELECT id FROM migrations WHERE migration_name = ?");
            $stmt->execute([$file]);
            
            if ($stmt->fetch()) {
                $migration_results[] = [
                    'file' => $file,
                    'status' => 'skipped',
                    'message' => 'قبلاً اجرا شده'
                ];
                continue;
            }
            
            $file_path = $migrations_path . $file;
            
            if (!file_exists($file_path)) {
                $migration_results[] = [
                    'file' => $file,
                    'status' => 'error',
                    'message' => 'فایل یافت نشد'
                ];
                continue;
            }
            
            // خواندن و اجرای SQL
            $sql = file_get_contents($file_path);
            
            try {
                $pdo->exec($sql);
                
                // ثبت migration موفق
                $stmt = $pdo->prepare("INSERT INTO migrations (migration_name) VALUES (?)");
                $stmt->execute([$file]);
                
                $migration_results[] = [
                    'file' => $file,
                    'status' => 'success',
                    'message' => 'اجرا موفق'
                ];
            } catch (PDOException $e) {
                $migration_results[] = [
                    'file' => $file,
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }
        
        // بررسی موفقیت کلی
        $all_success = true;
        foreach ($migration_results as $result) {
            if ($result['status'] === 'error') {
                $all_success = false;
                break;
            }
        }
        
        if ($all_success) {
            $_SESSION['migrations_done'] = true;
            $success = 'تمام جداول با موفقیت ایجاد شدند!';
        } else {
            $error = 'برخی از migrationها با خطا مواجه شدند.';
        }
        
    } catch (PDOException $e) {
        $error = 'خطا در اتصال به دیتابیس: ' . $e->getMessage();
    }
}

$migrations_done = isset($_SESSION['migrations_done']) && $_SESSION['migrations_done'];
?>

<h2 class="section-title">🏗️ ساخت جداول دیتابیس</h2>

<p class="section-description">
    در این مرحله، تمام جداول مورد نیاز سیستم CRM از طریق Migration Runner ایجاد می‌شوند.
</p>

<?php if ($error): ?>
<div class="alert alert-error">
    <strong>❌ خطا!</strong><br>
    <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success">
    <strong>✅ موفق!</strong><br>
    <?php echo htmlspecialchars($success); ?>
</div>
<?php endif; ?>

<?php if (!empty($migration_results)): ?>
<h3 style="margin-top: 30px; color: var(--gray-700);">📋 نتیجه اجرای Migrationها</h3>
<div class="checklist">
    <?php foreach ($migration_results as $result): ?>
    <div class="checklist-item <?php echo $result['status'] === 'success' ? 'success' : ($result['status'] === 'error' ? 'error' : 'warning'); ?>">
        <div class="checklist-icon">
            <?php 
            if ($result['status'] === 'success') echo '✅';
            elseif ($result['status'] === 'error') echo '❌';
            else echo '⏭️';
            ?>
        </div>
        <div class="checklist-content">
            <div class="checklist-title"><?php echo htmlspecialchars($result['file']); ?></div>
            <div class="checklist-description"><?php echo htmlspecialchars($result['message']); ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!$migrations_done): ?>
<div class="alert alert-info" style="margin-top: 30px;">
    <strong>📌 توجه:</strong><br>
    با کلیک روی دکمه زیر، تمام جداول زیر ایجاد می‌شوند:<br>
    <ul style="margin: 10px 0 0 20px; line-height: 1.8;">
        <li><strong>001_init.sql:</strong> users, migrations, audit_logs, roles, permissions</li>
        <li><strong>002_catalog.sql:</strong> products, product_variants, categories, tags, attributes</li>
        <li><strong>003_inventory.sql:</strong> warehouses, inventory_movements, stock_snapshots</li>
        <li><strong>004_sales.sql:</strong> customers, customer_addresses, sales, sale_items, payments, refunds</li>
        <li><strong>005_woocommerce.sql:</strong> stores, external_entity_map, webhook_events, sync_jobs</li>
        <li><strong>006_ai.sql:</strong> ai_providers, ai_requests, ai_outputs, ai_summaries</li>
    </ul>
</div>

<form method="POST" action="">
    <div class="btn-group">
        <a href="?step=2" class="btn btn-secondary">
            ➡️ قبلی
        </a>
        
        <button type="submit" name="run_migrations" class="btn btn-primary">
            🚀 اجرای Migrationها
        </button>
    </div>
</form>
<?php else: ?>
<div class="btn-group">
    <a href="?step=2" class="btn btn-secondary">
        ➡️ قبلی
    </a>
    
    <a href="?step=4" class="btn btn-primary">
        بعدی: ایجاد کاربر مدیر
        ⬅️
    </a>
</div>
<?php endif; ?>
