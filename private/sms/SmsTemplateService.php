<?php
/**
 * SMS Template Service
 * سرویس مدیریت و همگام‌سازی الگوهای پیامک
 *
 * @version 3.6.0
 * @author ReadyCRM Team
 */

class SmsTemplateService
{
    private $pdo;
    private $msgway;
    private $user_id;

    /**
     * سازنده کلاس
     */
    public function __construct($pdo, $user_id = null)
    {
        $this->pdo = $pdo;
        $this->user_id = $user_id;

        // اتصال به MsgWay Client
        require_once __DIR__ . '/MsgWayClient.php';
        $api_key = $this->getApiKey();
        $this->msgway = new MsgWayClient($api_key);
    }

    /**
     * دریافت API Key از تنظیمات
     */
    private function getApiKey()
    {
        try {
            $stmt = $this->pdo->query("
                SELECT setting_value
                FROM settings
                WHERE setting_key = 'msgway_api_key'
                LIMIT 1
            ");

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['setting_value'] : '';

        } catch (PDOException $e) {
            error_log("خطا در دریافت API Key: " . $e->getMessage());
            return '';
        }
    }

    /**
     * همگام‌سازی الگوها از MessageWay به دیتابیس
     * 
     * این متد لیست الگوهای تأیید شده را از API دریافت می‌کند
     * و آنها را در جدول sms_templates ذخیره یا بروزرسانی می‌کند
     *
     * @return array نتیجه همگام‌سازی
     */
    public function syncTemplates()
    {
        try {
            // دریافت لیست الگوها از API
            $api_result = $this->msgway->getTemplates();

            if (!$api_result['success']) {
                return [
                    'success' => false,
                    'error' => 'خطا در دریافت الگوها از MessageWay: ' . $api_result['error']
                ];
            }

            $templates = $api_result['templates'];
            $stats = [
                'total' => count($templates),
                'created' => 0,
                'updated' => 0,
                'skipped' => 0
            ];

            $this->pdo->beginTransaction();

            foreach ($templates as $template) {
                // بررسی وجود الگو در دیتابیس
                $existing = $this->getTemplateByApiId($template['id']);

                if ($existing) {
                    // بروزرسانی
                    $updated = $this->updateTemplateFromAPI($existing['id'], $template);
                    if ($updated) {
                        $stats['updated']++;
                    } else {
                        $stats['skipped']++;
                    }
                } else {
                    // ایجاد
                    $created = $this->createTemplateFromAPI($template);
                    if ($created) {
                        $stats['created']++;
                    } else {
                        $stats['skipped']++;
                    }
                }
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => sprintf(
                    'همگام‌سازی کامل شد: %d ایجاد، %d بروزرسانی، %d نادیده گرفته شد',
                    $stats['created'],
                    $stats['updated'],
                    $stats['skipped']
                ),
                'stats' => $stats
            ];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("خطا در همگام‌سازی الگوها: " . $e->getMessage());

            return [
                'success' => false,
                'error' => 'خطا در همگام‌سازی الگوها'
            ];
        }
    }

    /**
     * دریافت الگو از دیتابیس بر اساس API Template ID
     */
    private function getTemplateByApiId($api_template_id)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT *
                FROM sms_templates
                WHERE api_template_id = ?
                LIMIT 1
            ");

            $stmt->execute([$api_template_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("خطا در جستجوی الگو: " . $e->getMessage());
            return null;
        }
    }

    /**
     * ایجاد الگو از داده‌های API
     */
    private function createTemplateFromAPI($api_template)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO sms_templates (
                    name,
                    slug,
                    template_body,
                    parameters,
                    message_type,
                    api_template_id,
                    provider_id,
                    status,
                    created_by,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $parameters = $this->extractParameters($api_template['body']);

            return $stmt->execute([
                $api_template['name'],
                $this->generateSlug($api_template['name']),
                $api_template['body'],
                json_encode($parameters, JSON_UNESCAPED_UNICODE),
                $api_template['type'] ?? 'sms',
                $api_template['id'],
                $api_template['provider_id'] ?? null,
                'active',
                $this->user_id
            ]);

        } catch (PDOException $e) {
            error_log("خطا در ایجاد الگو: " . $e->getMessage());
            return false;
        }
    }

    /**
     * بروزرسانی الگو از داده‌های API
     */
    private function updateTemplateFromAPI($template_id, $api_template)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE sms_templates
                SET
                    name = ?,
                    template_body = ?,
                    parameters = ?,
                    message_type = ?,
                    provider_id = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");

            $parameters = $this->extractParameters($api_template['body']);

            return $stmt->execute([
                $api_template['name'],
                $api_template['body'],
                json_encode($parameters, JSON_UNESCAPED_UNICODE),
                $api_template['type'] ?? 'sms',
                $api_template['provider_id'] ?? null,
                $template_id
            ]);

        } catch (PDOException $e) {
            error_log("خطا در بروزرسانی الگو: " . $e->getMessage());
            return false;
        }
    }

    /**
     * استخراج پارامترهای الگو از متن
     * 
     * شناسایی پارامترها با فرمت {parameter_name}
     *
     * @param string $body متن الگو
     * @return array لیست پارامترها
     */
    private function extractParameters($body)
    {
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $body, $matches);

        if (empty($matches[1])) {
            return [];
        }

        $parameters = [];
        foreach (array_unique($matches[1]) as $param) {
            $parameters[] = [
                'name' => $param,
                'label' => $this->generateParameterLabel($param)
            ];
        }

        return $parameters;
    }

    /**
     * تولید برچسب فارسی برای پارامتر
     */
    private function generateParameterLabel($param)
    {
        // ترجمه پارامترهای رایج
        $translations = [
            'name' => 'نام',
            'first_name' => 'نام',
            'last_name' => 'نام خانوادگی',
            'full_name' => 'نام کامل',
            'code' => 'کد',
            'verification_code' => 'کد تأیید',
            'otp' => 'کد یکبار مصرف',
            'amount' => 'مبلغ',
            'date' => 'تاریخ',
            'time' => 'زمان',
            'phone' => 'تلفن',
            'mobile' => 'موبایل',
            'email' => 'ایمیل',
            'company' => 'شرکت',
            'product' => 'محصول',
            'order_id' => 'شماره سفارش',
            'invoice_number' => 'شماره فاکتور',
            'customer_name' => 'نام مشتری',
            'price' => 'قیمت',
            'discount' => 'تخفیف',
            'total' => 'جمع کل',
            'status' => 'وضعیت',
            'link' => 'لینک',
            'url' => 'آدرس'
        ];

        return $translations[strtolower($param)] ?? ucfirst(str_replace('_', ' ', $param));
    }

    /**
     * تولید Slug منحصر به فرد از نام الگو
     */
    private function generateSlug($name)
    {
        // حذف کاراکترهای غیرمجاز
        $slug = preg_replace('/[^a-z0-9\s\-_]/i', '', $name);
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[\s\-_]+/', '_', $slug);

        // بررسی یکتایی
        $original_slug = $slug;
        $counter = 1;

        while ($this->slugExists($slug)) {
            $slug = $original_slug . '_' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * بررسی وجود Slug در دیتابیس
     */
    private function slugExists($slug)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM sms_templates WHERE slug = ?
            ");
            $stmt->execute([$slug]);
            return $stmt->fetchColumn() > 0;

        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * دریافت لیست الگوها از دیتابیس
     *
     * @param array $filters فیلترهای جستجو
     * @return array لیست الگوها
     */
    public function getTemplates($filters = [])
    {
        try {
            $where = ["1=1"];
            $params = [];

            // فیلتر وضعیت
            if (!empty($filters['status'])) {
                $where[] = "status = ?";
                $params[] = $filters['status'];
            }

            // فیلتر نوع پیام
            if (!empty($filters['message_type'])) {
                $where[] = "message_type = ?";
                $params[] = $filters['message_type'];
            }

            // جستجو در نام
            if (!empty($filters['search'])) {
                $where[] = "(name LIKE ? OR slug LIKE ?)";
                $search_term = '%' . $filters['search'] . '%';
                $params[] = $search_term;
                $params[] = $search_term;
            }

            $where_clause = implode(' AND ', $where);

            $stmt = $this->pdo->prepare("
                SELECT
                    t.*,
                    CONCAT(u.first_name, ' ', u.last_name) as created_by_name
                FROM sms_templates t
                LEFT JOIN users u ON t.created_by = u.id
                WHERE {$where_clause}
                ORDER BY t.created_at DESC
            ");

            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("خطا در دریافت الگوها: " . $e->getMessage());
            return [];
        }
    }

    /**
     * دریافت الگو با شناسه
     */
    public function getTemplateById($id)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    t.*,
                    CONCAT(u.first_name, ' ', u.last_name) as created_by_name
                FROM sms_templates t
                LEFT JOIN users u ON t.created_by = u.id
                WHERE t.id = ?
            ");

            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("خطا در دریافت الگو: " . $e->getMessage());
            return null;
        }
    }

    /**
     * دریافت الگو با Slug
     */
    public function getTemplateBySlug($slug)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM sms_templates WHERE slug = ? AND status = 'active'
            ");

            $stmt->execute([$slug]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("خطا در دریافت الگو: " . $e->getMessage());
            return null;
        }
    }

    /**
     * ایجاد الگوی دستی (بدون API)
     * 
     * این متد فقط برای الگوهای محلی است که در MessageWay ثبت نشده‌اند
     */
    public function createManualTemplate($data)
    {
        try {
            // اعتبارسنجی
            if (empty($data['name']) || empty($data['template_body'])) {
                return [
                    'success' => false,
                    'error' => 'نام و متن الگو الزامی است'
                ];
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO sms_templates (
                    name,
                    slug,
                    template_body,
                    parameters,
                    message_type,
                    api_template_id,
                    status,
                    created_by,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, NULL, ?, ?, NOW())
            ");

            $slug = $this->generateSlug($data['name']);
            $parameters = $this->extractParameters($data['template_body']);

            $result = $stmt->execute([
                $data['name'],
                $slug,
                $data['template_body'],
                json_encode($parameters, JSON_UNESCAPED_UNICODE),
                $data['message_type'] ?? 'sms',
                $data['status'] ?? 'draft',
                $this->user_id
            ]);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'الگو با موفقیت ایجاد شد',
                    'template_id' => $this->pdo->lastInsertId()
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'خطا در ایجاد الگو'
                ];
            }

        } catch (PDOException $e) {
            error_log("خطا در ایجاد الگوی دستی: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'خطا در ایجاد الگو'
            ];
        }
    }

    /**
     * بروزرسانی الگو
     */
    public function updateTemplate($id, $data)
    {
        try {
            $template = $this->getTemplateById($id);

            if (!$template) {
                return [
                    'success' => false,
                    'error' => 'الگو یافت نشد'
                ];
            }

            // اگر الگو از API است، فقط وضعیت را می‌توان تغییر داد
            if ($template['api_template_id']) {
                $stmt = $this->pdo->prepare("
                    UPDATE sms_templates
                    SET status = ?, updated_at = NOW()
                    WHERE id = ?
                ");

                $result = $stmt->execute([
                    $data['status'] ?? $template['status'],
                    $id
                ]);

            } else {
                // الگوی دستی - امکان ویرایش کامل
                $stmt = $this->pdo->prepare("
                    UPDATE sms_templates
                    SET
                        name = ?,
                        template_body = ?,
                        parameters = ?,
                        message_type = ?,
                        status = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");

                $parameters = $this->extractParameters($data['template_body']);

                $result = $stmt->execute([
                    $data['name'] ?? $template['name'],
                    $data['template_body'] ?? $template['template_body'],
                    json_encode($parameters, JSON_UNESCAPED_UNICODE),
                    $data['message_type'] ?? $template['message_type'],
                    $data['status'] ?? $template['status'],
                    $id
                ]);
            }

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'الگو با موفقیت بروزرسانی شد'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'خطا در بروزرسانی الگو'
                ];
            }

        } catch (PDOException $e) {
            error_log("خطا در بروزرسانی الگو: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'خطا در بروزرسانی الگو'
            ];
        }
    }

    /**
     * حذف نرم الگو
     */
    public function deleteTemplate($id)
    {
        try {
            $template = $this->getTemplateById($id);

            if (!$template) {
                return [
                    'success' => false,
                    'error' => 'الگو یافت نشد'
                ];
            }

            // بررسی استفاده در کمپین‌های فعال
            $usage_count = $this->getTemplateUsageCount($id);

            if ($usage_count > 0) {
                return [
                    'success' => false,
                    'error' => sprintf('این الگو در %d کمپین فعال استفاده شده است', $usage_count)
                ];
            }

            // حذف نرم (Soft Delete)
            $stmt = $this->pdo->prepare("
                UPDATE sms_templates
                SET status = 'deleted', updated_at = NOW()
                WHERE id = ?
            ");

            $result = $stmt->execute([$id]);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'الگو با موفقیت حذف شد'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'خطا در حذف الگو'
                ];
            }

        } catch (PDOException $e) {
            error_log("خطا در حذف الگو: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'خطا در حذف الگو'
            ];
        }
    }

    /**
     * شمارش استفاده از الگو در کمپین‌های فعال
     */
    private function getTemplateUsageCount($template_id)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*)
                FROM sms_campaigns
                WHERE template_id = ? AND status IN ('draft', 'scheduled', 'running')
            ");

            $stmt->execute([$template_id]);
            return $stmt->fetchColumn();

        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * پیش‌نمایش الگو با پارامترهای نمونه
     */
    public function previewTemplate($id, $sample_params = [])
    {
        try {
            $template = $this->getTemplateById($id);

            if (!$template) {
                return [
                    'success' => false,
                    'error' => 'الگو یافت نشد'
                ];
            }

            $body = $template['template_body'];
            $parameters = json_decode($template['parameters'], true) ?? [];

            // جایگزینی پارامترها
            foreach ($parameters as $param) {
                $param_name = $param['name'];
                $value = $sample_params[$param_name] ?? '[' . $param['label'] . ']';
                $body = str_replace('{' . $param_name . '}', $value, $body);
            }

            return [
                'success' => true,
                'preview' => $body,
                'length' => mb_strlen($body),
                'sms_count' => ceil(mb_strlen($body) / 70)
            ];

        } catch (Exception $e) {
            error_log("خطا در پیش‌نمایش الگو: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'خطا در پیش‌نمایش الگو'
            ];
        }
    }

    /**
     * آمار الگوها
     */
    public function getStats()
    {
        try {
            return [
                'total' => $this->pdo->query("SELECT COUNT(*) FROM sms_templates WHERE status != 'deleted'")->fetchColumn(),
                'active' => $this->pdo->query("SELECT COUNT(*) FROM sms_templates WHERE status = 'active'")->fetchColumn(),
                'draft' => $this->pdo->query("SELECT COUNT(*) FROM sms_templates WHERE status = 'draft'")->fetchColumn(),
                'api_synced' => $this->pdo->query("SELECT COUNT(*) FROM sms_templates WHERE api_template_id IS NOT NULL")->fetchColumn(),
                'manual' => $this->pdo->query("SELECT COUNT(*) FROM sms_templates WHERE api_template_id IS NULL AND status != 'deleted'")->fetchColumn()
            ];

        } catch (PDOException $e) {
            error_log("خطا در دریافت آمار الگوها: " . $e->getMessage());
            return [
                'total' => 0,
                'active' => 0,
                'draft' => 0,
                'api_synced' => 0,
                'manual' => 0
            ];
        }
    }

    /**
     * تست اتصال به MessageWay
     */
    public function testConnection()
    {
        return $this->msgway->testConnection();
    }
}
