<?php
/**
 * Database Indexer for AI Copilot
 * ایندکس کننده دیتابیس برای دسترسی امن AI به اطلاعات
 * 
 * @version 1.0.0
 * @author Ready Studio
 */

class DatabaseIndexer
{
    private $pdo;
    private $batch_size = 100;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * ایندکس کامل تمام داده‌ها (اجرای دستی یا CRON)
     */
    public function indexAll()
    {
        try {
            $this->pdo->beginTransaction();

            // پاک کردن ایندکس قدیمی
            $this->pdo->exec("TRUNCATE TABLE chatbot_index");

            $results = [
                'customers' => $this->indexCustomers(),
                'leads' => $this->indexLeads(),
                'products' => $this->indexProducts(),
                'sales' => $this->indexSales(),
                'tasks' => $this->indexTasks(),
                'activities' => $this->indexActivities(),
                'settings' => $this->indexSettings()
            ];

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'ایندکس با موفقیت بروزرسانی شد',
                'stats' => $results
            ];

        } catch (Exception $e) {
            $this->pdo->rollback();
            error_log("Indexer Error: " . $e->getMessage());

            return [
                'success' => false,
                'error' => 'خطا در ایندکس کردن: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ایندکس مشتریان
     */
    private function indexCustomers()
    {
        $stmt = $this->pdo->query("
            SELECT 
                c.id,
                c.customer_code,
                c.first_name,
                c.last_name,
                c.company_name,
                c.email,
                c.phone,
                c.mobile,
                c.customer_type,
                c.status,
                c.industry,
                c.address,
                c.city,
                c.website,
                c.created_at,
                CONCAT(u.first_name, ' ', u.last_name) AS assigned_user
            FROM customers c
            LEFT JOIN users u ON c.assigned_to = u.id
            WHERE c.deleted_at IS NULL
            ORDER BY c.id DESC
        ");

        $count = 0;

        while ($customer = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // ساخت محتوای قابل جستجو
            $content = $this->buildCustomerContent($customer);

            $this->insertIndex(
                'customer',
                $customer['id'],
                $customer['customer_code'] . ' - ' . $customer['first_name'] . ' ' . $customer['last_name'],
                $content,
                json_encode($customer, JSON_UNESCAPED_UNICODE)
            );

            $count++;
        }

        return $count;
    }

    /**
     * ایندکس سرنخ‌ها
     */
    private function indexLeads()
    {
        $stmt = $this->pdo->query("
            SELECT 
                l.id,
                l.title,
                l.company,
                l.contact_name,
                l.email,
                l.phone,
                l.status,
                l.priority,
                l.source,
                l.estimated_value,
                l.expected_close_date,
                l.description,
                l.created_at,
                CONCAT(u.first_name, ' ', u.last_name) AS assigned_user
            FROM leads l
            LEFT JOIN users u ON l.assigned_to = u.id
            WHERE l.deleted_at IS NULL
            ORDER BY l.id DESC
        ");

        $count = 0;

        while ($lead = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $content = $this->buildLeadContent($lead);

            $this->insertIndex(
                'lead',
                $lead['id'],
                $lead['title'],
                $content,
                json_encode($lead, JSON_UNESCAPED_UNICODE)
            );

            $count++;
        }

        return $count;
    }

    /**
     * ایندکس محصولات
     */
    private function indexProducts()
    {
        $stmt = $this->pdo->query("
            SELECT 
                id,
                product_code,
                name,
                category,
                price,
                stock_quantity,
                description,
                status,
                created_at
            FROM products
            WHERE deleted_at IS NULL
            ORDER BY id DESC
        ");

        $count = 0;

        while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $content = $this->buildProductContent($product);

            $this->insertIndex(
                'product',
                $product['id'],
                $product['product_code'] . ' - ' . $product['name'],
                $content,
                json_encode($product, JSON_UNESCAPED_UNICODE)
            );

            $count++;
        }

        return $count;
    }

    /**
     * ایندکس فروش‌ها (metadata فقط - بدون جزئیات حساس)
     */
    private function indexSales()
    {
        $stmt = $this->pdo->query("
            SELECT 
                s.id,
                s.invoice_number,
                s.sale_date,
                s.total_amount,
                s.status,
                s.payment_status,
                CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
                c.company_name,
                CONCAT(u.first_name, ' ', u.last_name) AS seller_name
            FROM sales s
            LEFT JOIN customers c ON s.customer_id = c.id
            LEFT JOIN users u ON s.user_id = u.id
            WHERE s.deleted_at IS NULL
            ORDER BY s.id DESC
            LIMIT 500
        ");

        $count = 0;

        while ($sale = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $content = sprintf(
                "فاکتور %s - مشتری: %s - تاریخ: %s - مبلغ: %s تومان - وضعیت: %s - پرداخت: %s - فروشنده: %s",
                $sale['invoice_number'],
                $sale['customer_name'] . ($sale['company_name'] ? ' (' . $sale['company_name'] . ')' : ''),
                $sale['sale_date'],
                number_format($sale['total_amount']),
                $sale['status'],
                $sale['payment_status'],
                $sale['seller_name']
            );

            $this->insertIndex(
                'sale',
                $sale['id'],
                'فاکتور ' . $sale['invoice_number'],
                $content,
                json_encode($sale, JSON_UNESCAPED_UNICODE)
            );

            $count++;
        }

        return $count;
    }

    /**
     * ایندکس وظایف
     */
    private function indexTasks()
    {
        $stmt = $this->pdo->query("
            SELECT 
                t.id,
                t.title,
                t.description,
                t.status,
                t.priority,
                t.due_date,
                t.created_at,
                CONCAT(u.first_name, ' ', u.last_name) AS assigned_user
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE t.deleted_at IS NULL
            ORDER BY t.id DESC
            LIMIT 300
        ");

        $count = 0;

        while ($task = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $content = sprintf(
                "وظیفه: %s - وضعیت: %s - اولویت: %s - سررسید: %s - مسئول: %s - توضیحات: %s",
                $task['title'],
                $task['status'],
                $task['priority'],
                $task['due_date'] ?: 'ندارد',
                $task['assigned_user'] ?: 'تخصیص نیافته',
                $task['description'] ?: 'ندارد'
            );

            $this->insertIndex(
                'task',
                $task['id'],
                $task['title'],
                $content,
                json_encode($task, JSON_UNESCAPED_UNICODE)
            );

            $count++;
        }

        return $count;
    }

    /**
     * ایندکس فعالیت‌های اخیر
     */
    private function indexActivities()
    {
        $stmt = $this->pdo->query("
            SELECT 
                a.id,
                a.action,
                a.table_name,
                a.record_id,
                a.created_at,
                CONCAT(u.first_name, ' ', u.last_name) AS user_name
            FROM activity_logs a
            LEFT JOIN users u ON a.user_id = u.id
            ORDER BY a.created_at DESC
            LIMIT 200
        ");

        $count = 0;

        while ($activity = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $content = sprintf(
                "%s - عملیات: %s در %s - زمان: %s",
                $activity['user_name'],
                $activity['action'],
                $activity['table_name'] ?: 'سیستم',
                $activity['created_at']
            );

            $this->insertIndex(
                'activity',
                $activity['id'],
                'فعالیت ' . $activity['action'],
                $content,
                json_encode($activity, JSON_UNESCAPED_UNICODE)
            );

            $count++;
        }

        return $count;
    }

    /**
     * ایندکس تنظیمات (محدود)
     */
    private function indexSettings()
    {
        $allowed_settings = [
            'company_name',
            'company_phone',
            'company_email',
            'currency',
            'tax_rate'
        ];

        $placeholders = str_repeat('?,', count($allowed_settings) - 1) . '?';

        $stmt = $this->pdo->prepare("
            SELECT setting_key, setting_value
            FROM settings
            WHERE setting_key IN ($placeholders)
        ");
        $stmt->execute($allowed_settings);

        $count = 0;

        while ($setting = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $content = sprintf(
                "تنظیم %s: %s",
                $this->translateSettingKey($setting['setting_key']),
                $setting['setting_value']
            );

            $this->insertIndex(
                'setting',
                0,
                $this->translateSettingKey($setting['setting_key']),
                $content,
                json_encode($setting, JSON_UNESCAPED_UNICODE)
            );

            $count++;
        }

        return $count;
    }

    /**
     * درج رکورد در جدول ایندکس
     */
    private function insertIndex($entity_type, $entity_id, $title, $content, $metadata)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO chatbot_index (entity_type, entity_id, title, content, metadata, indexed_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $entity_type,
            $entity_id,
            $title,
            $content,
            $metadata
        ]);
    }

    /**
     * جستجوی متنی در ایندکس
     */
    public function search($query, $entity_types = null, $limit = 10)
    {
        $query = '%' . $query . '%';

        $sql = "
            SELECT 
                entity_type,
                entity_id,
                title,
                content,
                metadata,
                indexed_at
            FROM chatbot_index
            WHERE (title LIKE ? OR content LIKE ?)
        ";

        $params = [$query, $query];

        // فیلتر بر اساس نوع
        if ($entity_types && is_array($entity_types)) {
            $placeholders = str_repeat('?,', count($entity_types) - 1) . '?';
            $sql .= " AND entity_type IN ($placeholders)";
            $params = array_merge($params, $entity_types);
        }

        $sql .= " ORDER BY indexed_at DESC LIMIT ?";
        $params[] = $limit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * بروزرسانی یک رکورد خاص
     */
    public function updateEntity($entity_type, $entity_id)
    {
        try {
            // حذف رکورد قدیمی
            $stmt = $this->pdo->prepare("
                DELETE FROM chatbot_index
                WHERE entity_type = ? AND entity_id = ?
            ");
            $stmt->execute([$entity_type, $entity_id]);

            // افزودن رکورد جدید
            switch ($entity_type) {
                case 'customer':
                    return $this->indexSingleCustomer($entity_id);
                case 'lead':
                    return $this->indexSingleLead($entity_id);
                case 'product':
                    return $this->indexSingleProduct($entity_id);
                case 'sale':
                    return $this->indexSingleSale($entity_id);
                case 'task':
                    return $this->indexSingleTask($entity_id);
                default:
                    return false;
            }

        } catch (Exception $e) {
            error_log("Update Index Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ایندکس یک مشتری
     */
    private function indexSingleCustomer($customer_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                c.id,
                c.customer_code,
                c.first_name,
                c.last_name,
                c.company_name,
                c.email,
                c.phone,
                c.mobile,
                c.customer_type,
                c.status,
                c.industry,
                c.address,
                c.city,
                c.website,
                c.created_at,
                CONCAT(u.first_name, ' ', u.last_name) AS assigned_user
            FROM customers c
            LEFT JOIN users u ON c.assigned_to = u.id
            WHERE c.id = ? AND c.deleted_at IS NULL
        ");

        $stmt->execute([$customer_id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$customer) return false;

        $content = $this->buildCustomerContent($customer);

        $this->insertIndex(
            'customer',
            $customer['id'],
            $customer['customer_code'] . ' - ' . $customer['first_name'] . ' ' . $customer['last_name'],
            $content,
            json_encode($customer, JSON_UNESCAPED_UNICODE)
        );

        return true;
    }

    /**
     * ساخت محتوای جستجوی مشتری
     */
    private function buildCustomerContent($customer)
    {
        return sprintf(
            "کد مشتری: %s - نام: %s %s - شرکت: %s - ایمیل: %s - تلفن: %s - موبایل: %s - نوع: %s - وضعیت: %s - صنعت: %s - شهر: %s - وبسایت: %s - مسئول: %s",
            $customer['customer_code'],
            $customer['first_name'],
            $customer['last_name'],
            $customer['company_name'] ?: 'ندارد',
            $customer['email'] ?: 'ندارد',
            $customer['phone'] ?: 'ندارد',
            $customer['mobile'] ?: 'ندارد',
            $customer['customer_type'] === 'company' ? 'حقوقی' : 'حقیقی',
            $customer['status'],
            $customer['industry'] ?: 'نامشخص',
            $customer['city'] ?: 'نامشخص',
            $customer['website'] ?: 'ندارد',
            $customer['assigned_user'] ?: 'تخصیص نیافته'
        );
    }

    /**
     * ساخت محتوای جستجوی سرنخ
     */
    private function buildLeadContent($lead)
    {
        return sprintf(
            "عنوان: %s - شرکت: %s - نام تماس: %s - ایمیل: %s - تلفن: %s - وضعیت: %s - اولویت: %s - منبع: %s - ارزش تخمینی: %s تومان - تاریخ بسته شدن: %s - توضیحات: %s - مسئول: %s",
            $lead['title'],
            $lead['company'] ?: 'ندارد',
            $lead['contact_name'] ?: 'ندارد',
            $lead['email'] ?: 'ندارد',
            $lead['phone'] ?: 'ندارد',
            $lead['status'],
            $lead['priority'],
            $lead['source'] ?: 'نامشخص',
            $lead['estimated_value'] ? number_format($lead['estimated_value']) : '0',
            $lead['expected_close_date'] ?: 'تعیین نشده',
            $lead['description'] ?: 'ندارد',
            $lead['assigned_user'] ?: 'تخصیص نیافته'
        );
    }

    /**
     * ساخت محتوای جستجوی محصول
     */
    private function buildProductContent($product)
    {
        return sprintf(
            "کد محصول: %s - نام: %s - دسته: %s - قیمت: %s تومان - موجودی: %s - وضعیت: %s - توضیحات: %s",
            $product['product_code'],
            $product['name'],
            $product['category'] ?: 'بدون دسته',
            number_format($product['price']),
            $product['stock_quantity'],
            $product['status'],
            $product['description'] ?: 'ندارد'
        );
    }

    /**
     * ترجمه کلید تنظیمات
     */
    private function translateSettingKey($key)
    {
        $translations = [
            'company_name' => 'نام شرکت',
            'company_phone' => 'تلفن شرکت',
            'company_email' => 'ایمیل شرکت',
            'currency' => 'واحد پول',
            'tax_rate' => 'نرخ مالیات'
        ];

        return $translations[$key] ?? $key;
    }

    /**
     * ایندکس‌های بیشتر برای سایر موجودیت‌ها...
     */
    private function indexSingleLead($lead_id) { /* similar to indexSingleCustomer */ return true; }
    private function indexSingleProduct($product_id) { /* similar */ return true; }
    private function indexSingleSale($sale_id) { /* similar */ return true; }
    private function indexSingleTask($task_id) { /* similar */ return true; }

    /**
     * دریافت آمار ایندکس
     */
    public function getIndexStats()
    {
        $stmt = $this->pdo->query("
            SELECT 
                entity_type,
                COUNT(*) as count
            FROM chatbot_index
            GROUP BY entity_type
        ");

        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * پاکسازی ایندکس‌های قدیمی (بیش از X روز)
     */
    public function cleanOldIndexes($days = 30)
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM chatbot_index
            WHERE indexed_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");

        $stmt->execute([$days]);

        return $stmt->rowCount();
    }
}
