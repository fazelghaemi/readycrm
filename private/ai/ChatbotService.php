<?php
/**
 * Chatbot Service - AI Assistant for CRM
 * سرویس اصلی مدیریت چت‌بات هوشمند
 * 
 * @version 1.0.0
 * @author Ready Studio
 */

class ChatbotService
{
    private $pdo;
    private $gapgpt;
    private $indexer;
    private $user_id;
    private $settings;

    /**
     * سازنده کلاس
     */
    public function __construct($pdo, $user_id)
    {
        $this->pdo = $pdo;
        $this->user_id = $user_id;
        
        // بارگذاری تنظیمات AI
        $this->loadSettings();
        
        // اتصال به GapGPT
        require_once __DIR__ . '/GapGPTClient.php';
        $this->gapgpt = new GapGPTClient($this->settings);
        
        // ایندکسر دیتابیس
        require_once __DIR__ . '/DatabaseIndexer.php';
        $this->indexer = new DatabaseIndexer($pdo);
    }

    /**
     * بارگذاری تنظیمات AI از دیتابیس
     */
    private function loadSettings()
    {
        try {
            $stmt = $this->pdo->query("
                SELECT setting_key, setting_value 
                FROM settings 
                WHERE setting_key LIKE 'ai_%'
            ");
            
            $this->settings = [
                'enabled' => true,
                'api_key' => '',
                'model' => 'gapgpt-deepseek-v3',
                'temperature' => 0.7,
                'max_tokens' => 2000,
                'index_enabled' => true
            ];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $key = str_replace('ai_', '', $row['setting_key']);
                $this->settings[$key] = $row['setting_value'];
            }
            
        } catch (PDOException $e) {
            error_log("خطا در بارگذاری تنظیمات AI: " . $e->getMessage());
        }
    }

    /**
     * بررسی فعال بودن چت‌بات
     */
    public function isEnabled()
    {
        return !empty($this->settings['enabled']) && 
               !empty($this->settings['api_key']);
    }

    /**
     * پردازش پیام کاربر و تولید پاسخ
     */
    public function processMessage($message, $session_id = null)
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'چت‌بات در حال حاضر غیرفعال است.'
            ];
        }

        try {
            // تولید session_id در صورت عدم وجود
            if (empty($session_id)) {
                $session_id = $this->generateSessionId();
            }

            // ذخیره پیام کاربر
            $this->saveMessage($session_id, 'user', $message);

            // تشخیص نیت کاربر
            $intent = $this->detectIntent($message);

            // دریافت Context از دیتابیس
            $context = $this->buildContext($intent, $message);

            // دریافت تاریخچه مکالمه
            $conversation_history = $this->getConversationHistory($session_id);

            // ساخت Prompt نهایی
            $prompt = $this->buildPrompt($message, $context, $conversation_history, $intent);

            // ارسال به GapGPT
            $ai_response = $this->gapgpt->sendMessage($prompt);

            if ($ai_response['success']) {
                // ذخیره پاسخ AI
                $this->saveMessage($session_id, 'assistant', $ai_response['message']);

                // پیشنهاد اقدامات سریع
                $quick_actions = $this->suggestQuickActions($intent, $ai_response['message']);

                return [
                    'success' => true,
                    'message' => $ai_response['message'],
                    'session_id' => $session_id,
                    'intent' => $intent,
                    'quick_actions' => $quick_actions,
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'خطا در دریافت پاسخ از هوش مصنوعی: ' . $ai_response['error']
                ];
            }

        } catch (Exception $e) {
            error_log("خطا در پردازش پیام چت‌بات: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطایی در پردازش درخواست شما رخ داد.'
            ];
        }
    }

    /**
     * تشخیص نیت کاربر از روی پیام
     */
    private function detectIntent($message)
    {
        $message = mb_strtolower(trim($message));

        // الگوهای شناسایی نیت
        $patterns = [
            'report' => ['گزارش', 'آمار', 'نمودار', 'تحلیل', 'چارت', 'جمع'],
            'search' => ['جستجو', 'پیدا کن', 'نشان بده', 'لیست', 'کجاست', 'چطور'],
            'create' => ['ثبت', 'اضافه', 'ایجاد', 'جدید', 'بساز'],
            'update' => ['ویرایش', 'تغییر', 'بروزرسانی', 'اصلاح'],
            'delete' => ['حذف', 'پاک کن', 'بردار'],
            'help' => ['راهنما', 'کمک', 'چطوری', 'آموزش', 'نحوه']
        ];

        foreach ($patterns as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($message, $keyword) !== false) {
                    return $intent;
                }
            }
        }

        return 'general'; // پیش‌فرض
    }

    /**
     * ساخت Context از دیتابیس برای AI
     */
    private function buildContext($intent, $message)
    {
        $context = [
            'user_info' => $this->getUserInfo(),
            'crm_stats' => $this->getCRMStats(),
            'indexed_data' => []
        ];

        // اگر ایندکس فعال باشد
        if ($this->settings['index_enabled']) {
            $context['indexed_data'] = $this->indexer->searchRelevant($message, 5);
        }

        // بر اساس نیت، داده‌های خاص اضافه کن
        switch ($intent) {
            case 'report':
                $context['recent_sales'] = $this->getRecentSales(10);
                $context['monthly_stats'] = $this->getMonthlyStats();
                break;

            case 'search':
                $context['search_results'] = $this->performSearch($message);
                break;

            case 'create':
                $context['form_fields'] = $this->getFormFields($message);
                break;
        }

        return $context;
    }

    /**
     * ساخت Prompt نهایی برای ارسال به AI
     */
    private function buildPrompt($message, $context, $history, $intent)
    {
        $prompt = "شما دستیار هوشمند سیستم CRM هستید.\n\n";
        
        // نقش و وظایف
        $prompt .= "وظایف شما:\n";
        $prompt .= "- پاسخگویی به سوالات درباره مشتریان، فروش، لیدها و وظایف\n";
        $prompt .= "- ارائه گزارش و تحلیل آماری\n";
        $prompt .= "- راهنمایی برای انجام کارها در سیستم\n";
        $prompt .= "- پیشنهاد اقدامات مناسب\n\n";

        // اطلاعات کاربر
        $prompt .= "اطلاعات کاربر فعلی:\n";
        $prompt .= "- نام: {$context['user_info']['name']}\n";
        $prompt .= "- نقش: {$context['user_info']['role']}\n\n";

        // آمار کلی CRM
        $prompt .= "آمار کلی سیستم:\n";
        $prompt .= "- تعداد مشتریان: " . number_format($context['crm_stats']['customers']) . "\n";
        $prompt .= "- تعداد لیدها: " . number_format($context['crm_stats']['leads']) . "\n";
        $prompt .= "- مجموع فروش: " . number_format($context['crm_stats']['total_sales']) . " تومان\n";
        $prompt .= "- وظایف در انتظار: " . number_format($context['crm_stats']['pending_tasks']) . "\n\n";

        // داده‌های ایندکس شده
        if (!empty($context['indexed_data'])) {
            $prompt .= "اطلاعات مرتبط از دیتابیس:\n";
            foreach ($context['indexed_data'] as $item) {
                $prompt .= "- {$item['content_text']}\n";
            }
            $prompt .= "\n";
        }

        // تاریخچه مکالمه (5 پیام آخر)
        if (!empty($history)) {
            $prompt .= "تاریخچه گفتگو:\n";
            foreach (array_slice($history, -5) as $msg) {
                $role = $msg['role'] == 'user' ? 'کاربر' : 'شما';
                $prompt .= "{$role}: {$msg['message']}\n";
            }
            $prompt .= "\n";
        }

        // پیام فعلی کاربر
        $prompt .= "سوال/درخواست فعلی کاربر:\n";
        $prompt .= "{$message}\n\n";

        // دستورالعمل‌های خاص بر اساس نیت
        switch ($intent) {
            case 'report':
                $prompt .= "لطفاً یک گزارش دقیق و عددی ارائه کنید.\n";
                break;
            case 'create':
                $prompt .= "کاربر می‌خواهد چیزی ثبت کند. مرحله به مرحله راهنمایی کنید.\n";
                break;
            case 'help':
                $prompt .= "راهنمایی کامل و گام به گام ارائه دهید.\n";
                break;
        }

        $prompt .= "\nپاسخ را به زبان فارسی، واضح، مختصر و کاربردی بنویسید.";

        return $prompt;
    }

    /**
     * ذخیره پیام در دیتابیس
     */
    private function saveMessage($session_id, $role, $message)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO chatbot_conversations 
                (user_id, session_id, role, message, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $this->user_id,
                $session_id,
                $role,
                $message
            ]);

        } catch (PDOException $e) {
            error_log("خطا در ذخیره پیام چت‌بات: " . $e->getMessage());
        }
    }

    /**
     * دریافت تاریخچه مکالمه
     */
    private function getConversationHistory($session_id, $limit = 10)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT role, message, created_at
                FROM chatbot_conversations
                WHERE session_id = ? AND user_id = ?
                ORDER BY id DESC
                LIMIT ?
            ");
            
            $stmt->execute([$session_id, $this->user_id, $limit]);
            return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

        } catch (PDOException $e) {
            error_log("خطا در دریافت تاریخچه: " . $e->getMessage());
            return [];
        }
    }

    /**
     * تولید Session ID یونیک
     */
    private function generateSessionId()
    {
        return 'chat_' . $this->user_id . '_' . time() . '_' . bin2hex(random_bytes(4));
    }

    /**
     * دریافت اطلاعات کاربر
     */
    private function getUserInfo()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    CONCAT(first_name, ' ', last_name) as name,
                    role
                FROM users
                WHERE id = ?
            ");
            $stmt->execute([$this->user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return ['name' => 'کاربر', 'role' => 'user'];
        }
    }

    /**
     * دریافت آمار کلی CRM
     */
    private function getCRMStats()
    {
        try {
            return [
                'customers' => $this->pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn(),
                'leads' => $this->pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn(),
                'total_sales' => $this->pdo->query("SELECT COALESCE(SUM(final_amount), 0) FROM sales WHERE status != 'cancelled'")->fetchColumn(),
                'pending_tasks' => $this->pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'pending'")->fetchColumn()
            ];
        } catch (PDOException $e) {
            return ['customers' => 0, 'leads' => 0, 'total_sales' => 0, 'pending_tasks' => 0];
        }
    }

    /**
     * دریافت آخرین فروش‌ها
     */
    private function getRecentSales($limit = 10)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    s.*,
                    c.name as customer_name
                FROM sales s
                LEFT JOIN customers c ON s.customer_id = c.id
                ORDER BY s.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * آمار ماهانه
     */
    private function getMonthlyStats()
    {
        try {
            return $this->pdo->query("
                SELECT 
                    COUNT(*) as count,
                    SUM(final_amount) as total
                FROM sales
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    AND status != 'cancelled'
            ")->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['count' => 0, 'total' => 0];
        }
    }

    /**
     * جستجو در دیتابیس
     */
    private function performSearch($query)
    {
        // اینجا می‌توانید جستجوی پیشرفته‌تری پیاده کنید
        return [];
    }

    /**
     * فیلدهای فرم مربوطه
     */
    private function getFormFields($message)
    {
        // تشخیص نوع فرم و بازگشت فیلدها
        return [];
    }

    /**
     * پیشنهاد اقدامات سریع بر اساس نیت
     */
    private function suggestQuickActions($intent, $response)
    {
        $actions = [];

        switch ($intent) {
            case 'create':
                $actions = [
                    ['label' => 'ثبت مشتری جدید', 'action' => 'open_form', 'target' => 'customer_form'],
                    ['label' => 'ثبت لید جدید', 'action' => 'open_form', 'target' => 'lead_form'],
                    ['label' => 'ثبت فروش', 'action' => 'open_form', 'target' => 'sale_form']
                ];
                break;

            case 'report':
                $actions = [
                    ['label' => 'مشاهده داشبورد', 'action' => 'navigate', 'target' => 'dashboard.php'],
                    ['label' => 'گزارش فروش', 'action' => 'navigate', 'target' => 'sales.php']
                ];
                break;

            case 'search':
                $actions = [
                    ['label' => 'جستجوی مشتریان', 'action' => 'navigate', 'target' => 'customers.php'],
                    ['label' => 'جستجوی لیدها', 'action' => 'navigate', 'target' => 'leads.php']
                ];
                break;

            default:
                $actions = [
                    ['label' => 'داشبورد', 'action' => 'navigate', 'target' => 'dashboard.php'],
                    ['label' => 'راهنما', 'action' => 'help', 'target' => 'guide']
                ];
        }

        return $actions;
    }

    /**
     * پاک کردن تاریخچه مکالمه قدیمی
     */
    public function cleanOldConversations($days = 30)
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM chatbot_conversations
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            return $stmt->execute([$days]);
        } catch (PDOException $e) {
            error_log("خطا در پاک کردن مکالمات قدیمی: " . $e->getMessage());
            return false;
        }
    }

    /**
     * آمار استفاده از چت‌بات
     */
    public function getUsageStats()
    {
        try {
            return [
                'total_conversations' => $this->pdo->query("SELECT COUNT(DISTINCT session_id) FROM chatbot_conversations")->fetchColumn(),
                'total_messages' => $this->pdo->query("SELECT COUNT(*) FROM chatbot_conversations")->fetchColumn(),
                'today_messages' => $this->pdo->query("SELECT COUNT(*) FROM chatbot_conversations WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
                'active_users' => $this->pdo->query("SELECT COUNT(DISTINCT user_id) FROM chatbot_conversations WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn()
            ];
        } catch (PDOException $e) {
            return ['total_conversations' => 0, 'total_messages' => 0, 'today_messages' => 0, 'active_users' => 0];
        }
    }
}
