<?php
/**
 * ══════════════════════════════════════════════════════════════════════════════
 * SmsLogger - SMS Activity Logger for ReadyCRM MessageWay Module
 * ══════════════════════════════════════════════════════════════════════════════
 *
 * این کلاس مسئول ثبت، پیگیری و گزارش‌گیری تمام فعالیت‌های SMS است.
 *
 * الگوی معماری:
 *   ← Stateful Service (مشابه ChatbotService)
 *   ← وابسته به $pdo (مانند تمام Service Layerها در ReadyCRM)
 *   ← بدون Business Logic اضافه
 *   ← کاملاً AI-Ready (سازگار با DatabaseIndexer)
 *
 * @version 3.6.0
 * @author ReadyCRM Team
 * @package ReadyCRM\SMS
 * ══════════════════════════════════════════════════════════════════════════════
 */

// جلوگیری از دسترسی مستقیم
if (!defined('APP_NAME')) {
    die('Direct access not allowed.');
}

class SmsLogger
{
    // ─────────────────────────────────────────────────────────────────────────
    // PROPERTIES
    // ─────────────────────────────────────────────────────────────────────────

    private $pdo;
    private $user_id;

    /**
     * وضعیت‌های معتبر برای یک لاگ SMS
     */
    const STATUS_PENDING   = 'pending';
    const STATUS_SENT      = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED    = 'failed';
    const STATUS_REJECTED  = 'rejected';

    /**
     * انواع رویداد قابل لاگ
     */
    const EVENT_SEND     = 'send';
    const EVENT_DELIVER  = 'deliver';
    const EVENT_FAIL     = 'fail';
    const EVENT_RETRY    = 'retry';
    const EVENT_CANCEL   = 'cancel';
    const EVENT_STATUS   = 'status_check';

    // ─────────────────────────────────────────────────────────────────────────
    // CONSTRUCTOR
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * سازنده کلاس
     *
     * @param PDO $pdo اتصال دیتابیس
     * @param int $user_id شناسه کاربر جاری (برای attribution)
     */
    public function __construct($pdo, $user_id)
    {
        $this->pdo     = $pdo;
        $this->user_id = (int) $user_id;
    }

    // =========================================================================
    // CORE LOGGING METHODS
    // =========================================================================

    /**
     * ثبت یک رویداد ارسال SMS (اصلی‌ترین متد)
     *
     * این متد بلافاصله پس از فراخوانی MsgWayClient::sendSMS() صدا زده می‌شود.
     *
     * @param array $data داده‌های لاگ
     * @return int|false شناسه لاگ ثبت‌شده یا false در صورت خطا
     */
    public function log(array $data)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO sms_logs (
                    campaign_id,
                    recipient_phone,
                    message_text,
                    template_id,
                    status,
                    provider_message_id,
                    cost,
                    sent_by,
                    metadata,
                    error_message,
                    sent_at
                ) VALUES (
                    :campaign_id,
                    :recipient_phone,
                    :message_text,
                    :template_id,
                    :status,
                    :provider_message_id,
                    :cost,
                    :sent_by,
                    :metadata,
                    :error_message,
                    NOW()
                )
            ");

            $stmt->execute([
                ':campaign_id'         => $data['campaign_id']         ?? null,
                ':recipient_phone'     => $data['recipient_phone']     ?? '',
                ':message_text'        => $data['message_text']        ?? '',
                ':template_id'         => $data['template_id']         ?? null,
                ':status'              => $data['status']              ?? self::STATUS_PENDING,
                ':provider_message_id' => $data['provider_message_id'] ?? null,
                ':cost'                => $data['cost']                ?? 0.00,
                ':sent_by'             => $data['sent_by']             ?? $this->user_id,
                ':metadata'            => isset($data['metadata'])
                                          ? json_encode($data['metadata'], JSON_UNESCAPED_UNICODE)
                                          : null,
                ':error_message'       => $data['error_message']       ?? null,
            ]);

            return (int) $this->pdo->lastInsertId();

        } catch (PDOException $e) {
            error_log('[SmsLogger] خطا در ثبت لاگ SMS: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ثبت موفقیت‌آمیز یک ارسال
     *
     * @param string $phone شماره مقصد
     * @param string $message متن پیام
     * @param string $provider_message_id شناسه پیام از طرف MessageWay
     * @param array  $options گزینه‌های اضافه (campaign_id, template_id, cost, ...)
     * @return int|false
     */
    public function logSuccess($phone, $message, $provider_message_id, array $options = [])
    {
        return $this->log(array_merge($options, [
            'recipient_phone'     => $phone,
            'message_text'        => $message,
            'status'              => self::STATUS_SENT,
            'provider_message_id' => $provider_message_id,
        ]));
    }

    /**
     * ثبت شکست یک ارسال
     *
     * @param string $phone شماره مقصد
     * @param string $message متن پیام
     * @param string $error_message پیغام خطا
     * @param array  $options گزینه‌های اضافه
     * @return int|false
     */
    public function logFailure($phone, $message, $error_message, array $options = [])
    {
        return $this->log(array_merge($options, [
            'recipient_phone' => $phone,
            'message_text'    => $message,
            'status'          => self::STATUS_FAILED,
            'error_message'   => $error_message,
        ]));
    }

    /**
     * ثبت نتیجه یک Batch (کمپین) - استفاده داخلی توسط SmsCampaignService
     *
     * @param int   $campaign_id شناسه کمپین
     * @param array $results نتایج آرایه‌ای از ارسال‌ها
     *              هر آیتم: ['phone' => ..., 'success' => bool, 'message_id' => ..., 'error' => ...]
     * @param string $message_text متن ارسال‌شده
     * @return array آمار ثبت ['logged' => int, 'failed' => int]
     */
    public function logBatch($campaign_id, array $results, $message_text = '')
    {
        $stats = ['logged' => 0, 'failed_log' => 0];

        foreach ($results as $result) {
            $log_data = [
                'campaign_id'  => $campaign_id,
                'message_text' => $message_text,
                'sent_by'      => $this->user_id,
            ];

            if (!empty($result['success'])) {
                $log_data['recipient_phone']     = $result['phone'] ?? '';
                $log_data['status']              = self::STATUS_SENT;
                $log_data['provider_message_id'] = $result['message_id'] ?? null;
                $log_data['cost']                = $result['cost'] ?? 0;
            } else {
                $log_data['recipient_phone'] = $result['phone'] ?? '';
                $log_data['status']          = self::STATUS_FAILED;
                $log_data['error_message']   = $result['error'] ?? 'خطای نامشخص';
            }

            $id = $this->log($log_data);
            $id ? $stats['logged']++ : $stats['failed_log']++;
        }

        return $stats;
    }

    // =========================================================================
    // STATUS UPDATE METHODS
    // =========================================================================

    /**
     * به‌روزرسانی وضعیت یک پیام بر اساس پاسخ Delivery Report
     *
     * @param string $provider_message_id شناسه MessageWay
     * @param string $new_status وضعیت جدید (delivered, failed, ...)
     * @param array  $extra داده‌های اضافه (delivered_at، metadata، ...)
     * @return bool
     */
    public function updateStatus($provider_message_id, $new_status, array $extra = [])
    {
        try {
            $allowed_statuses = [
                self::STATUS_PENDING,
                self::STATUS_SENT,
                self::STATUS_DELIVERED,
                self::STATUS_FAILED,
                self::STATUS_REJECTED,
            ];

            if (!in_array($new_status, $allowed_statuses, true)) {
                error_log("[SmsLogger] وضعیت نامعتبر: {$new_status}");
                return false;
            }

            $set_parts = ['status = :status', 'updated_at = NOW()'];
            $params    = [
                ':status'              => $new_status,
                ':provider_message_id' => $provider_message_id,
            ];

            // اگر تحویل داده شده، زمان تحویل را ثبت کن
            if ($new_status === self::STATUS_DELIVERED) {
                $set_parts[]             = 'delivered_at = NOW()';
            }

            // اگر metadata ارسال شده، ادغام کن
            if (!empty($extra['metadata'])) {
                $set_parts[]        = 'metadata = :metadata';
                $params[':metadata'] = json_encode($extra['metadata'], JSON_UNESCAPED_UNICODE);
            }

            $sql = "UPDATE sms_logs SET " . implode(', ', $set_parts)
                 . " WHERE provider_message_id = :provider_message_id";

            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);

        } catch (PDOException $e) {
            error_log('[SmsLogger] خطا در به‌روزرسانی وضعیت: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * به‌روزرسانی وضعیت بر اساس شناسه داخلی لاگ
     *
     * @param int    $log_id
     * @param string $new_status
     * @return bool
     */
    public function updateStatusById($log_id, $new_status)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE sms_logs
                SET status = :status, updated_at = NOW()
                WHERE id = :id
            ");
            return $stmt->execute([
                ':status' => $new_status,
                ':id'     => (int) $log_id,
            ]);
        } catch (PDOException $e) {
            error_log('[SmsLogger] خطا در updateStatusById: ' . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // QUERY METHODS
    // =========================================================================

    /**
     * دریافت یک لاگ بر اساس شناسه داخلی
     *
     * @param int $log_id
     * @return array|null
     */
    public function getById($log_id)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT sl.*,
                       u.name AS sent_by_name,
                       c.name AS campaign_name
                FROM sms_logs sl
                LEFT JOIN users u ON u.id = sl.sent_by
                LEFT JOIN sms_campaigns c ON c.id = sl.campaign_id
                WHERE sl.id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => (int) $log_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        } catch (PDOException $e) {
            error_log('[SmsLogger] خطا در getById: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * دریافت لیست لاگ‌ها با فیلتر، صفحه‌بندی و مرتب‌سازی
     *
     * @param array $filters
     *   - campaign_id    int
     *   - status         string (sent|delivered|failed|...)
     *   - phone          string (جستجوی جزئی)
     *   - date_from      string (Y-m-d)
     *   - date_to        string (Y-m-d)
     *   - sent_by        int
     * @param int $page
     * @param int $per_page
     * @return array ['data' => [...], 'total' => int, 'pages' => int]
     */
    public function getLogs(array $filters = [], $page = 1, $per_page = 50)
    {
        try {
            $where  = ['1=1'];
            $params = [];

            if (!empty($filters['campaign_id'])) {
                $where[]                    = 'sl.campaign_id = :campaign_id';
                $params[':campaign_id']     = (int) $filters['campaign_id'];
            }

            if (!empty($filters['status'])) {
                $where[]           = 'sl.status = :status';
                $params[':status'] = $filters['status'];
            }

            if (!empty($filters['phone'])) {
                $where[]          = 'sl.recipient_phone LIKE :phone';
                $params[':phone'] = '%' . $filters['phone'] . '%';
            }

            if (!empty($filters['date_from'])) {
                $where[]               = 'DATE(sl.sent_at) >= :date_from';
                $params[':date_from']  = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $where[]             = 'DATE(sl.sent_at) <= :date_to';
                $params[':date_to']  = $filters['date_to'];
            }

            if (!empty($filters['sent_by'])) {
                $where[]              = 'sl.sent_by = :sent_by';
                $params[':sent_by']   = (int) $filters['sent_by'];
            }

            $where_sql = implode(' AND ', $where);

            // شمارش کل رکوردها
            $count_stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM sms_logs sl WHERE {$where_sql}
            ");
            $count_stmt->execute($params);
            $total = (int) $count_stmt->fetchColumn();

            // محاسبه offset
            $page     = max(1, (int) $page);
            $per_page = max(1, min(200, (int) $per_page));
            $offset   = ($page - 1) * $per_page;

            // دریافت داده‌ها
            $data_stmt = $this->pdo->prepare("
                SELECT
                    sl.id,
                    sl.campaign_id,
                    sl.recipient_phone,
                    sl.message_text,
                    sl.status,
                    sl.provider_message_id,
                    sl.cost,
                    sl.error_message,
                    sl.sent_at,
                    sl.delivered_at,
                    u.name AS sent_by_name,
                    c.name AS campaign_name
                FROM sms_logs sl
                LEFT JOIN users u ON u.id = sl.sent_by
                LEFT JOIN sms_campaigns c ON c.id = sl.campaign_id
                WHERE {$where_sql}
                ORDER BY sl.sent_at DESC
                LIMIT :limit OFFSET :offset
            ");

            $params[':limit']  = $per_page;
            $params[':offset'] = $offset;
            $data_stmt->execute($params);

            return [
                'data'     => $data_stmt->fetchAll(PDO::FETCH_ASSOC),
                'total'    => $total,
                'page'     => $page,
                'per_page' => $per_page,
                'pages'    => $total > 0 ? (int) ceil($total / $per_page) : 0,
            ];

        } catch (PDOException $e) {
            error_log('[SmsLogger] خطا در getLogs: ' . $e->getMessage());
            return ['data' => [], 'total' => 0, 'page' => 1, 'per_page' => $per_page, 'pages' => 0];
        }
    }

    /**
     * دریافت تمام لاگ‌های یک کمپین (بدون صفحه‌بندی)
     *
     * @param int $campaign_id
     * @return array
     */
    public function getCampaignLogs($campaign_id)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, recipient_phone, status, provider_message_id,
                       cost, error_message, sent_at, delivered_at
                FROM sms_logs
                WHERE campaign_id = :campaign_id
                ORDER BY sent_at ASC
            ");
            $stmt->execute([':campaign_id' => (int) $campaign_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log('[SmsLogger] خطا در getCampaignLogs: ' . $e->getMessage());
            return [];
        }
    }

    // =========================================================================
    // STATISTICS METHODS
    // =========================================================================

    /**
     * آمار کلی SMS (قابل استفاده در Dashboard و DatabaseIndexer)
     *
     * @param int $days تعداد روز اخیر (پیش‌فرض: 30)
     * @return array
     */
    public function getStats($days = 30)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    COUNT(*)                                          AS total_sent,
                    SUM(status = 'delivered')                        AS total_delivered,
                    SUM(status = 'failed')                           AS total_failed,
                    SUM(status = 'sent')                             AS total_pending_delivery,
                    COALESCE(SUM(cost), 0)                           AS total_cost,
                    ROUND(
                        SUM(status = 'delivered') * 100.0 / NULLIF(COUNT(*), 0),
                        2
                    )                                                AS delivery_rate,
                    COUNT(DISTINCT campaign_id)                      AS active_campaigns,
                    COUNT(DISTINCT recipient_phone)                  AS unique_recipients,
                    COUNT(DISTINCT DATE(sent_at))                    AS active_days
                FROM sms_logs
                WHERE sent_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            ");
            $stmt->execute([':days' => (int) $days]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            // تبدیل null به 0 برای نمایش امن
            return array_map(function ($v) {
                return $v ?? 0;
            }, $stats);

        } catch (PDOException $e) {
            error_log('[SmsLogger] خطا در getStats: ' . $e->getMessage());
            return [
                'total_sent'              => 0,
                'total_delivered'         => 0,
                'total_failed'            => 0,
                'total_pending_delivery'  => 0,
                'total_cost'              => 0,
                'delivery_rate'           => 0,
                'active_campaigns'        => 0,
                'unique_recipients'       => 0,
                'active_days'             => 0,
            ];
        }
    }

    /**
     * آمار تفکیکی برای یک کمپین خاص
     *
     * @param int $campaign_id
     * @return array
     */
    public function getCampaignStats($campaign_id)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    COUNT(*)                                                    AS total,
                    SUM(status = 'sent')                                        AS sent,
                    SUM(status = 'delivered')                                   AS delivered,
                    SUM(status = 'failed')                                      AS failed,
                    SUM(status = 'rejected')                                    AS rejected,
                    COALESCE(SUM(cost), 0)                                      AS total_cost,
                    ROUND(SUM(status = 'delivered') * 100.0 / NULLIF(COUNT(*), 0), 2) AS delivery_rate,
                    MIN(sent_at)                                                AS first_sent,
                    MAX(sent_at)                                                AS last_sent
                FROM sms_logs
                WHERE campaign_id = :campaign_id
            ");
            $stmt->execute([':campaign_id' => (int) $campaign_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        } catch (PDOException $e) {
            error_log('[SmsLogger] خطا در getCampaignStats: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * آمار روزانه برای نمودار (Chart-Ready)
     *
     * @param int $days
     * @return array آرایه‌ای با تاریخ و آمار هر روز
     */
    public function getDailyStats($days = 30)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    DATE(sent_at)                                        AS date,
                    COUNT(*)                                             AS total,
                    SUM(status IN ('sent', 'delivered'))                 AS successful,
                    SUM(status = 'delivered')                            AS delivered,
                    SUM(status = 'failed')                               AS failed,
                    COALESCE(SUM(cost), 0)                               AS cost
                FROM sms_logs
                WHERE sent_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY DATE(sent_at)
                ORDER BY date ASC
            ");
            $stmt->execute([':days' => (int) $days]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log('[SmsLogger] خطا در getDailyStats: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * تفکیک ارسال بر اساس وضعیت (Pie Chart-Ready)
     *
     * @param int|null $campaign_id null = همه کمپین‌ها
     * @return array
     */
    public function getStatusBreakdown($campaign_id = null)
    {
        try {
            $where  = '1=1';
            $params = [];

            if ($campaign_id !== null) {
                $where              = 'campaign_id = :campaign_id';
                $params[':campaign_id'] = (int) $campaign_id;
            }

            $stmt = $this->pdo->prepare("
                SELECT
                    status,
                    COUNT(*) AS count,
                    ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 2) AS percentage
                FROM sms_logs
                WHERE {$where}
                GROUP BY status
                ORDER BY count DESC
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log('[SmsLogger] خطا در getStatusBreakdown: ' . $e->getMessage());
            return [];
        }
    }

    // =========================================================================
    // AI-READY EXPORT METHODS (for DatabaseIndexer)
    // =========================================================================

    /**
     * خلاصه قابل استفاده توسط DatabaseIndexer برای AI Context
     *
     * این متد توسط DatabaseIndexer فراخوانی می‌شود تا داده‌های SMS
     * را در Context هوش مصنوعی قرار دهد.
     *
     * @return string متن خلاصه‌شده
     */
    public function getSummaryForAI()
    {
        $stats = $this->getStats(30);

        $summary = "وضعیت SMS در ۳۰ روز اخیر:\n";
        $summary .= "- مجموع ارسال‌شده: {$stats['total_sent']} پیام\n";
        $summary .= "- تحویل داده‌شده: {$stats['total_delivered']} پیام\n";
        $summary .= "- ناموفق: {$stats['total_failed']} پیام\n";
        $summary .= "- نرخ تحویل: {$stats['delivery_rate']}%\n";
        $summary .= "- هزینه کل: " . number_format($stats['total_cost']) . " تومان\n";
        $summary .= "- کمپین‌های فعال: {$stats['active_campaigns']}\n";
        $summary .= "- مخاطبان یکتا: {$stats['unique_recipients']}";

        return $summary;
    }

    /**
     * دریافت آخرین خطاها برای عیب‌یابی و AI Context
     *
     * @param int $limit
     * @return array
     */
    public function getRecentErrors($limit = 10)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    recipient_phone,
                    error_message,
                    sent_at,
                    campaign_id
                FROM sms_logs
                WHERE status = 'failed'
                  AND error_message IS NOT NULL
                ORDER BY sent_at DESC
                LIMIT :limit
            ");
            $stmt->execute([':limit' => (int) $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log('[SmsLogger] خطا در getRecentErrors: ' . $e->getMessage());
            return [];
        }
    }

    // =========================================================================
    // CLEANUP METHODS
    // =========================================================================

    /**
     * پاکسازی لاگ‌های قدیمی (بیش از X روز)
     * برای استفاده در Cron Job نگهداری دیتابیس
     *
     * @param int $days_to_keep تعداد روز نگهداری (پیش‌فرض: 365)
     * @return int تعداد رکوردهای حذف‌شده
     */
    public function cleanup($days_to_keep = 365)
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM sms_logs
                WHERE sent_at < DATE_SUB(NOW(), INTERVAL :days DAY)
                  AND status IN ('delivered', 'failed', 'rejected')
            ");
            $stmt->execute([':days' => (int) $days_to_keep]);
            $deleted = $stmt->rowCount();

            error_log("[SmsLogger] پاکسازی: {$deleted} لاگ قدیمی حذف شد.");
            return $deleted;

        } catch (PDOException $e) {
            error_log('[SmsLogger] خطا در cleanup: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * حذف تمام لاگ‌های یک کمپین (معمولاً قبل از اجرای مجدد)
     *
     * @param int $campaign_id
     * @return bool
     */
    public function clearCampaignLogs($campaign_id)
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM sms_logs WHERE campaign_id = :campaign_id
            ");
            return $stmt->execute([':campaign_id' => (int) $campaign_id]);

        } catch (PDOException $e) {
            error_log('[SmsLogger] خطا در clearCampaignLogs: ' . $e->getMessage());
            return false;
        }
    }
}
