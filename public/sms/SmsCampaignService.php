<?php
/**
 * SMS Campaign Service
 * سرویس مدیریت و اجرای کمپین‌های پیامکی
 *
 * @version 3.6.0
 * @author ReadyCRM Team
 */

class SmsCampaignService
{
    private $pdo;
    private $user_id;
    private $msgway;
    private $template_service;
    private $settings;

    /**
     * سازنده کلاس
     */
    public function __construct($pdo, $user_id)
    {
        $this->pdo = $pdo;
        $this->user_id = $user_id;

        // بارگذاری تنظیمات SMS
        $this->loadSettings();

        // اتصال به MsgWay Client
        require_once __DIR__ . '/MsgWayClient.php';
        $this->msgway = new MsgWayClient($this->settings['api_key']);

        // Template Service
        require_once __DIR__ . '/SmsTemplateService.php';
        $this->template_service = new SmsTemplateService($pdo, $user_id);
    }

    /**
     * بارگذاری تنظیمات SMS از دیتابیس
     */
    private function loadSettings()
    {
        try {
            $stmt = $this->pdo->query("
                SELECT setting_key, setting_value
                FROM settings
                WHERE setting_key LIKE 'msgway_%'
            ");

            $this->settings = [
                'enabled' => true,
                'api_key' => '',
                'sender_number' => '',
                'batch_size' => 100,
                'retry_attempts' => 3,
                'retry_delay' => 300
            ];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $key = str_replace('msgway_', '', $row['setting_key']);
                $this->settings[$key] = $row['setting_value'];
            }

        } catch (PDOException $e) {
            error_log("خطا در بارگذاری تنظیمات SMS: " . $e->getMessage());
        }
    }

    /**
     * بررسی فعال بودن سیستم SMS
     */
    public function isEnabled()
    {
        return !empty($this->settings['enabled']) &&
               !empty($this->settings['api_key']);
    }

    /**
     * ایجاد کمپین جدید
     */
    public function createCampaign($data)
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'error' => 'سیستم پیامک غیرفعال است'
            ];
        }

        try {
            // اعتبارسنجی
            $validation = $this->validateCampaignData($data);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'error' => $validation['error']
                ];
            }

            // بررسی الگو
            $template = $this->template_service->getTemplateById($data['template_id']);
            if (!$template) {
                return [
                    'success' => false,
                    'error' => 'الگو یافت نشد'
                ];
            }

            // استخراج مخاطبان
            $recipients = $this->resolveRecipients($data['recipient_type'], $data['recipient_filters'] ?? []);
            if (empty($recipients)) {
                return [
                    'success' => false,
                    'error' => 'هیچ مخاطبی یافت نشد'
                ];
            }

            // محاسبه هزینه تقریبی
            $cost_estimate = $this->estimateCost(count($recipients), $template['message_type']);

            // ایجاد کمپین در دیتابیس
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("
                INSERT INTO sms_campaigns (
                    name,
                    description,
                    template_id,
                    recipient_type,
                    recipient_filters,
                    schedule_type,
                    scheduled_at,
                    status,
                    total_recipients,
                    estimated_cost,
                    created_by,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $data['name'],
                $data['description'] ?? null,
                $data['template_id'],
                $data['recipient_type'],
                json_encode($data['recipient_filters'] ?? [], JSON_UNESCAPED_UNICODE),
                $data['schedule_type'] ?? 'immediate',
                $data['scheduled_at'] ?? null,
                $data['schedule_type'] === 'immediate' ? 'running' : 'scheduled',
                count($recipients),
                $cost_estimate,
                $this->user_id
            ]);

            $campaign_id = $this->pdo->lastInsertId();

            // ذخیره مخاطبان
            $this->saveCampaignRecipients($campaign_id, $recipients, $template);

            $this->pdo->commit();

            // اگر اجرای فوری است، به صف اضافه کن
            if ($data['schedule_type'] === 'immediate') {
                $this->queueCampaign($campaign_id);
            }

            return [
                'success' => true,
                'message' => 'کمپین با موفقیت ایجاد شد',
                'campaign_id' => $campaign_id,
                'total_recipients' => count($recipients),
                'estimated_cost' => $cost_estimate
            ];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("خطا در ایجاد کمپین: " . $e->getMessage());

            return [
                'success' => false,
                'error' => 'خطا در ایجاد کمپین'
            ];
        }
    }

    /**
     * اعتبارسنجی داده‌های کمپین
     */
    private function validateCampaignData($data)
    {
        if (empty($data['name'])) {
            return ['valid' => false, 'error' => 'نام کمپین الزامی است'];
        }

        if (empty($data['template_id'])) {
            return ['valid' => false, 'error' => 'الگو الزامی است'];
        }

        if (empty($data['recipient_type'])) {
            return ['valid' => false, 'error' => 'نوع مخاطب الزامی است'];
        }

        $allowed_recipient_types = ['all_customers', 'filtered_customers', 'all_leads', 'filtered_leads', 'manual'];
        if (!in_array($data['recipient_type'], $allowed_recipient_types)) {
            return ['valid' => false, 'error' => 'نوع مخاطب نامعتبر است'];
        }

        if ($data['schedule_type'] === 'scheduled' && empty($data['scheduled_at'])) {
            return ['valid' => false, 'error' => 'زمان زمان‌بندی الزامی است'];
        }

        return ['valid' => true];
    }

    /**
     * استخراج مخاطبان بر اساس نوع و فیلترها
     */
    private function resolveRecipients($type, $filters = [])
    {
        $recipients = [];

        switch ($type) {
            case 'all_customers':
                $recipients = $this->getAllCustomerPhones();
                break;

            case 'filtered_customers':
                $recipients = $this->getFilteredCustomerPhones($filters);
                break;

            case 'all_leads':
                $recipients = $this->getAllLeadPhones();
                break;

            case 'filtered_leads':
                $recipients = $this->getFilteredLeadPhones($filters);
                break;

            case 'manual':
                $recipients = $this->parseManualPhones($filters['phones'] ?? '');
                break;
        }

        // نرمال‌سازی و حذف تکراری
        $recipients = array_unique(array_filter(array_map(function($phone) {
            return $this->normalizePhone($phone);
        }, $recipients)));

        return array_values($recipients);
    }

    /**
     * دریافت شماره تمام مشتریان
     */
    private function getAllCustomerPhones()
    {
        try {
            $stmt = $this->pdo->query("
                SELECT mobile
                FROM customers
                WHERE mobile IS NOT NULL
                    AND mobile != ''
                    AND deleted_at IS NULL
            ");

            return $stmt->fetchAll(PDO::FETCH_COLUMN);

        } catch (PDOException $e) {
            error_log("خطا در دریافت شماره مشتریان: " . $e->getMessage());
            return [];
        }
    }

    /**
     * دریافت شماره مشتریان با فیلتر
     */
    private function getFilteredCustomerPhones($filters)
    {
        try {
            $where = ["mobile IS NOT NULL", "mobile != ''", "deleted_at IS NULL"];
            $params = [];

            if (!empty($filters['customer_type'])) {
                $where[] = "customer_type = ?";
                $params[] = $filters['customer_type'];
            }

            if (!empty($filters['status'])) {
                $where[] = "status = ?";
                $params[] = $filters['status'];
            }

            if (!empty($filters['city'])) {
                $where[] = "city = ?";
                $params[] = $filters['city'];
            }

            if (!empty($filters['assigned_to'])) {
                $where[] = "assigned_to = ?";
                $params[] = $filters['assigned_to'];
            }

            $where_clause = implode(' AND ', $where);

            $stmt = $this->pdo->prepare("
                SELECT mobile
                FROM customers
                WHERE {$where_clause}
            ");

            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);

        } catch (PDOException $e) {
            error_log("خطا در دریافت شماره مشتریان فیلترشده: " . $e->getMessage());
            return [];
        }
    }

    /**
     * دریافت شماره تمام لیدها
     */
    private function getAllLeadPhones()
    {
        try {
            $stmt = $this->pdo->query("
                SELECT phone
                FROM leads
                WHERE phone IS NOT NULL
                    AND phone != ''
                    AND deleted_at IS NULL
            ");

            return $stmt->fetchAll(PDO::FETCH_COLUMN);

        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * دریافت شماره لیدهای فیلترشده
     */
    private function getFilteredLeadPhones($filters)
    {
        try {
            $where = ["phone IS NOT NULL", "phone != ''", "deleted_at IS NULL"];
            $params = [];

            if (!empty($filters['status'])) {
                $where[] = "status = ?";
                $params[] = $filters['status'];
            }

            if (!empty($filters['priority'])) {
                $where[] = "priority = ?";
                $params[] = $filters['priority'];
            }

            if (!empty($filters['source'])) {
                $where[] = "source = ?";
                $params[] = $filters['source'];
            }

            $where_clause = implode(' AND ', $where);

            $stmt = $this->pdo->prepare("
                SELECT phone
                FROM leads
                WHERE {$where_clause}
            ");

            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);

        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * پردازش شماره‌های دستی
     */
    private function parseManualPhones($phones_text)
    {
        // جدا کردن بر اساس کاما، سطر جدید یا فاصله
        $phones = preg_split('/[,\n\r\s]+/', $phones_text);
        return array_filter($phones);
    }

    /**
     * نرمال‌سازی شماره موبایل
     */
    private function normalizePhone($phone)
    {
        // حذف کاراکترهای غیرعددی
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // حذف پیش‌شماره ایران
        $phone = ltrim($phone, '0');
        $phone = preg_replace('/^98/', '', $phone);

        // اضافه کردن 0 اول
        if (strlen($phone) === 10) {
            $phone = '0' . $phone;
        }

        // اعتبارسنجی فرمت ایرانی
        if (!preg_match('/^09[0-9]{9}$/', $phone)) {
            return null;
        }

        return $phone;
    }

    /**
     * ذخیره مخاطبان کمپین
     */
    private function saveCampaignRecipients($campaign_id, $recipients, $template)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO sms_recipients (
                    campaign_id,
                    phone_number,
                    status,
                    created_at
                ) VALUES (?, ?, 'pending', NOW())
            ");

            foreach ($recipients as $phone) {
                $stmt->execute([$campaign_id, $phone]);
            }

            return true;

        } catch (PDOException $e) {
            error_log("خطا در ذخیره مخاطبان: " . $e->getMessage());
            return false;
        }
    }

    /**
     * محاسبه هزینه تقریبی
     */
    private function estimateCost($recipient_count, $message_type = 'sms')
    {
        // هزینه فرضی (باید از API دریافت شود)
        $cost_per_sms = 500; // تومان
        $cost_per_ivr = 1500; // تومان

        $unit_cost = $message_type === 'ivr' ? $cost_per_ivr : $cost_per_sms;

        return $recipient_count * $unit_cost;
    }

    /**
     * اضافه کردن کمپین به صف اجرا
     */
    private function queueCampaign($campaign_id)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE sms_campaigns
                SET queued_at = NOW()
                WHERE id = ?
            ");

            return $stmt->execute([$campaign_id]);

        } catch (PDOException $e) {
            error_log("خطا در افزودن کمپین به صف: " . $e->getMessage());
            return false;
        }
    }

    /**
     * اجرای کمپین (پردازش Batch)
     */
    public function executeCampaign($campaign_id)
    {
        try {
            // دریافت کمپین
            $campaign = $this->getCampaignById($campaign_id);

            if (!$campaign || $campaign['status'] === 'completed') {
                return [
                    'success' => false,
                    'error' => 'کمپین یافت نشد یا قبلاً اجرا شده است'
                ];
            }

            // دریافت الگو
            $template = $this->template_service->getTemplateById($campaign['template_id']);

            if (!$template) {
                return [
                    'success' => false,
                    'error' => 'الگو یافت نشد'
                ];
            }

            // دریافت مخاطبان Pending
            $recipients = $this->getPendingRecipients($campaign_id, $this->settings['batch_size']);

            if (empty($recipients)) {
                // تمام شد
                $this->completeCampaign($campaign_id);
                return [
                    'success' => true,
                    'message' => 'کمپین کامل شد',
                    'status' => 'completed'
                ];
            }

            $stats = [
                'sent' => 0,
                'failed' => 0
            ];

            foreach ($recipients as $recipient) {
                $result = $this->sendToRecipient($recipient, $template, $campaign);

                if ($result['success']) {
                    $stats['sent']++;
                } else {
                    $stats['failed']++;
                }

                // Rate limiting
                usleep(100000); // 100ms delay
            }

            // بروزرسانی آمار کمپین
            $this->updateCampaignStats($campaign_id);

            return [
                'success' => true,
                'message' => sprintf('ارسال شد: %d، خطا: %d', $stats['sent'], $stats['failed']),
                'stats' => $stats,
                'status' => 'running'
            ];

        } catch (Exception $e) {
            error_log("خطا در اجرای کمپین: " . $e->getMessage());

            return [
                'success' => false,
                'error' => 'خطا در اجرای کمپین'
            ];
        }
    }

    /**
     * دریافت مخاطبان در انتظار
     */
    private function getPendingRecipients($campaign_id, $limit)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT *
                FROM sms_recipients
                WHERE campaign_id = ? AND status = 'pending'
                ORDER BY id ASC
                LIMIT ?
            ");

            $stmt->execute([$campaign_id, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * ارسال به یک مخاطب
     */
    private function sendToRecipient($recipient, $template, $campaign)
    {
        try {
            // رندر الگو
            $message = $this->renderTemplate($template['template_body'], $recipient);

            // ارسال
            if ($template['message_type'] === 'ivr') {
                $api_result = $this->msgway->sendIVR(
                    [$recipient['phone_number']],
                    $template['api_template_id']
                );
            } else {
                $api_result = $this->msgway->sendSMS(
                    [$recipient['phone_number']],
                    $message,
                    $this->settings['sender_number']
                );
            }

            if ($api_result['success']) {
                $this->updateRecipientStatus($recipient['id'], 'sent', $api_result['message_id'] ?? null);

                // لاگ موفق
                $this->logSMS($campaign['id'], $recipient['id'], $recipient['phone_number'], $message, 'sent', null);

                return ['success' => true];
            } else {
                $this->updateRecipientStatus($recipient['id'], 'failed', null, $api_result['error']);

                // لاگ خطا
                $this->logSMS($campaign['id'], $recipient['id'], $recipient['phone_number'], $message, 'failed', $api_result['error']);

                return [
                    'success' => false,
                    'error' => $api_result['error']
                ];
            }

        } catch (Exception $e) {
            $this->updateRecipientStatus($recipient['id'], 'failed', null, $e->getMessage());
            $this->logSMS($campaign['id'], $recipient['id'], $recipient['phone_number'], '', 'failed', $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * رندر الگو با پارامترهای مخاطب
     */
    private function renderTemplate($template_body, $recipient)
    {
        // دریافت اطلاعات مخاطب از DB
        $customer = $this->getCustomerByPhone($recipient['phone_number']);

        $replacements = [
            '{name}' => $customer['first_name'] ?? '',
            '{first_name}' => $customer['first_name'] ?? '',
            '{last_name}' => $customer['last_name'] ?? '',
            '{full_name}' => ($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''),
            '{company}' => $customer['company_name'] ?? '',
            '{phone}' => $recipient['phone_number']
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template_body);
    }

    /**
     * دریافت مشتری بر اساس شماره
     */
    private function getCustomerByPhone($phone)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM customers WHERE mobile = ? LIMIT 1
            ");
            $stmt->execute([$phone]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * بروزرسانی وضعیت مخاطب
     */
    private function updateRecipientStatus($recipient_id, $status, $message_id = null, $error = null)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE sms_recipients
                SET
                    status = ?,
                    message_id = ?,
                    error_message = ?,
                    sent_at = CASE WHEN ? = 'sent' THEN NOW() ELSE sent_at END
                WHERE id = ?
            ");

            return $stmt->execute([$status, $message_id, $error, $status, $recipient_id]);

        } catch (PDOException $e) {
            error_log("خطا در بروزرسانی وضعیت مخاطب: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ثبت لاگ ارسال
     */
    private function logSMS($campaign_id, $recipient_id, $phone, $message, $status, $error = null)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO sms_logs (
                    campaign_id,
                    recipient_id,
                    phone_number,
                    message_body,
                    status,
                    error_message,
                    sent_by,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            return $stmt->execute([
                $campaign_id,
                $recipient_id,
                $phone,
                $message,
                $status,
                $error,
                $this->user_id
            ]);

        } catch (PDOException $e) {
            error_log("خطا در ثبت لاگ SMS: " . $e->getMessage());
            return false;
        }
    }

    /**
     * بروزرسانی آمار کمپین
     */
    private function updateCampaignStats($campaign_id)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE sms_campaigns
                SET
                    sent_count = (SELECT COUNT(*) FROM sms_recipients WHERE campaign_id = ? AND status = 'sent'),
                    failed_count = (SELECT COUNT(*) FROM sms_recipients WHERE campaign_id = ? AND status = 'failed'),
                    updated_at = NOW()
                WHERE id = ?
            ");

            return $stmt->execute([$campaign_id, $campaign_id, $campaign_id]);

        } catch (PDOException $e) {
            error_log("خطا در بروزرسانی آمار: " . $e->getMessage());
            return false;
        }
    }

    /**
     * تکمیل کمپین
     */
    private function completeCampaign($campaign_id)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE sms_campaigns
                SET
                    status = 'completed',
                    completed_at = NOW(),
                    updated_at = NOW()
                WHERE id = ?
            ");

            return $stmt->execute([$campaign_id]);

        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * دریافت کمپین با شناسه
     */
    public function getCampaignById($id)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    c.*,
                    t.name as template_name,
                    CONCAT(u.first_name, ' ', u.last_name) as created_by_name
                FROM sms_campaigns c
                LEFT JOIN sms_templates t ON c.template_id = t.id
                LEFT JOIN users u ON c.created_by = u.id
                WHERE c.id = ?
            ");

            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * لیست کمپین‌ها
     */
    public function getCampaigns($filters = [])
    {
        try {
            $where = ["1=1"];
            $params = [];

            if (!empty($filters['status'])) {
                $where[] = "c.status = ?";
                $params[] = $filters['status'];
            }

            if (!empty($filters['search'])) {
                $where[] = "(c.name LIKE ? OR c.description LIKE ?)";
                $search = '%' . $filters['search'] . '%';
                $params[] = $search;
                $params[] = $search;
            }

            $where_clause = implode(' AND ', $where);

            $stmt = $this->pdo->prepare("
                SELECT
                    c.*,
                    t.name as template_name,
                    CONCAT(u.first_name, ' ', u.last_name) as created_by_name
                FROM sms_campaigns c
                LEFT JOIN sms_templates t ON c.template_id = t.id
                LEFT JOIN users u ON c.created_by = u.id
                WHERE {$where_clause}
                ORDER BY c.created_at DESC
            ");

            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * حذف کمپین
     */
    public function deleteCampaign($id)
    {
        try {
            $campaign = $this->getCampaignById($id);

            if (!$campaign) {
                return [
                    'success' => false,
                    'error' => 'کمپین یافت نشد'
                ];
            }

            if (in_array($campaign['status'], ['running', 'scheduled'])) {
                return [
                    'success' => false,
                    'error' => 'کمپین در حال اجرا یا زمان‌بندی شده قابل حذف نیست'
                ];
            }

            $stmt = $this->pdo->prepare("
                UPDATE sms_campaigns
                SET status = 'deleted', updated_at = NOW()
                WHERE id = ?
            ");

            if ($stmt->execute([$id])) {
                return [
                    'success' => true,
                    'message' => 'کمپین حذف شد'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'خطا در حذف کمپین'
                ];
            }

        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => 'خطا در حذف کمپین'
            ];
        }
    }

    /**
     * آمار عمومی
     */
    public function getStats()
    {
        try {
            return [
                'total_campaigns' => $this->pdo->query("SELECT COUNT(*) FROM sms_campaigns WHERE status != 'deleted'")->fetchColumn(),
                'active_campaigns' => $this->pdo->query("SELECT COUNT(*) FROM sms_campaigns WHERE status IN ('running', 'scheduled')")->fetchColumn(),
                'completed_campaigns' => $this->pdo->query("SELECT COUNT(*) FROM sms_campaigns WHERE status = 'completed'")->fetchColumn(),
                'total_sent' => $this->pdo->query("SELECT COUNT(*) FROM sms_recipients WHERE status = 'sent'")->fetchColumn(),
                'total_failed' => $this->pdo->query("SELECT COUNT(*) FROM sms_recipients WHERE status = 'failed'")->fetchColumn(),
                'today_sent' => $this->pdo->query("SELECT COUNT(*) FROM sms_recipients WHERE status = 'sent' AND DATE(sent_at) = CURDATE()")->fetchColumn()
            ];

        } catch (PDOException $e) {
            return [
                'total_campaigns' => 0,
                'active_campaigns' => 0,
                'completed_campaigns' => 0,
                'total_sent' => 0,
                'total_failed' => 0,
                'today_sent' => 0
            ];
        }
    }

    /**
     * تست اتصال
     */
    public function testConnection()
    {
        return $this->msgway->testConnection();
    }
}
