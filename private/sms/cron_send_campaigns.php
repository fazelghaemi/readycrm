<?php
/**
 * ══════════════════════════════════════════════════════════════════════════════
 * ReadyCRM V3.6 - SMS CAMPAIGN CRON EXECUTOR
 * ══════════════════════════════════════════════════════════════════════════════
 * اجرای خودکار کمپین‌های SMS زمان‌بندی‌شده با Rate Limiting
 * 
 * این اسکریپت باید به‌صورت دوره‌ای (هر 1-5 دقیقه) توسط Cron اجرا شود:
 * 
 * مثال Crontab:
 * */2 * * * * /usr/bin/php /path/to/readycrm/public/sms/cron_send_campaigns.php >> /var/log/sms_cron.log 2>&1
 * 
 * @version 3.6.0
 * @author ReadyCRM Team
 * @package ReadyCRM\SMS\Cron
 * ══════════════════════════════════════════════════════════════════════════════
 */

// ─── PREVENT WEB ACCESS ──────────────────────────────────────────────────────
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('Access denied. This script must be run from command line.');
}

// ─── BOOTSTRAP ───────────────────────────────────────────────────────────────
require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/database.php';
require_once __DIR__ . '/../../private/functions.php';
require_once __DIR__ . '/../../private/sms/MsgWayClient.php';
require_once __DIR__ . '/../../private/sms/SmsCampaignService.php';

// ─── LOCK MECHANISM (PREVENT CONCURRENT RUNS) ────────────────────────────────
$lock_file = sys_get_temp_dir() . '/readycrm_sms_cron.lock';
$lock_handle = fopen($lock_file, 'c');

if (!flock($lock_handle, LOCK_EX | LOCK_NB)) {
    echo "[" . date('Y-m-d H:i:s') . "] Another instance is already running. Exiting.\n";
    exit(0);
}

// تنظیم cleanup خودکار
register_shutdown_function(function() use ($lock_handle, $lock_file) {
    if ($lock_handle) {
        flock($lock_handle, LOCK_UN);
        fclose($lock_handle);
    }
    if (file_exists($lock_file)) {
        @unlink($lock_file);
    }
});

// ─── MAIN EXECUTION ──────────────────────────────────────────────────────────
try {
    echo "═══════════════════════════════════════════════════════════════════════\n";
    echo "ReadyCRM SMS Campaign Executor\n";
    echo "Started at: " . date('Y-m-d H:i:s') . "\n";
    echo "═══════════════════════════════════════════════════════════════════════\n\n";

    // بارگذاری تنظیمات
    $settings = loadSmsSettings($pdo);

    // بررسی فعال بودن ماژول
    if (empty($settings['sms_enabled'])) {
        echo "[INFO] SMS module is disabled. Exiting.\n\n";
        exit(0);
    }

    // بررسی اعتبار API
    if (empty($settings['sms_api_key']) || empty($settings['sms_sender_number'])) {
        echo "[ERROR] SMS API credentials not configured.\n\n";
        exit(1);
    }

    // یافتن کمپین‌های آماده اجرا
    $campaigns = findPendingCampaigns($pdo);

    if (empty($campaigns)) {
        echo "[INFO] No pending campaigns found.\n\n";
        exit(0);
    }

    echo "[INFO] Found " . count($campaigns) . " campaign(s) to process.\n\n";

    // پردازش هر کمپین
    $total_sent = 0;
    $total_failed = 0;

    foreach ($campaigns as $campaign) {
        echo "───────────────────────────────────────────────────────────────────────\n";
        echo "Processing Campaign ID: {$campaign['id']}\n";
        echo "Title: {$campaign['title']}\n";
        echo "Recipients: {$campaign['recipient_count']}\n";
        echo "───────────────────────────────────────────────────────────────────────\n";

        $result = processCampaign($pdo, $campaign, $settings);

        $total_sent += $result['sent'];
        $total_failed += $result['failed'];

        echo "Result: {$result['sent']} sent, {$result['failed']} failed\n\n";
    }

    // خلاصه کلی
    echo "═══════════════════════════════════════════════════════════════════════\n";
    echo "Execution Summary\n";
    echo "───────────────────────────────────────────────────────────────────────\n";
    echo "Total Sent: {$total_sent}\n";
    echo "Total Failed: {$total_failed}\n";
    echo "Finished at: " . date('Y-m-d H:i:s') . "\n";
    echo "═══════════════════════════════════════════════════════════════════════\n\n";

    exit(0);

} catch (Exception $e) {
    echo "[CRITICAL ERROR] " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
    error_log("SMS Cron Critical Error: " . $e->getMessage());
    exit(1);
}

// ═══════════════════════════════════════════════════════════════════════════════
// HELPER FUNCTIONS
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * بارگذاری تنظیمات SMS از دیتابیس
 */
function loadSmsSettings(PDO $pdo): array {
    $stmt = $pdo->query("
        SELECT setting_key, setting_value
        FROM settings
        WHERE setting_key LIKE 'sms_%'
    ");

    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    return $settings;
}

/**
 * یافتن کمپین‌های آماده اجرا
 */
function findPendingCampaigns(PDO $pdo): array {
    $stmt = $pdo->query("
        SELECT 
            id,
            title,
            template_id,
            recipient_type,
            recipient_filter,
            recipient_count,
            scheduled_at,
            created_at
        FROM sms_campaigns
        WHERE status = 'scheduled'
          AND scheduled_at <= NOW()
          AND deleted_at IS NULL
        ORDER BY scheduled_at ASC
        LIMIT 10
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * پردازش یک کمپین
 */
function processCampaign(PDO $pdo, array $campaign, array $settings): array {
    $sent_count = 0;
    $failed_count = 0;

    try {
        // تغییر وضعیت به running
        updateCampaignStatus($pdo, $campaign['id'], 'running');

        // بارگذاری الگو
        $template = loadTemplate($pdo, $campaign['template_id']);
        if (!$template) {
            throw new Exception("Template not found: {$campaign['template_id']}");
        }

        // بارگذاری گیرندگان
        $recipients = loadRecipients($pdo, $campaign);
        if (empty($recipients)) {
            throw new Exception("No recipients found for campaign");
        }

        echo "[INFO] Loaded " . count($recipients) . " recipient(s).\n";

        // ایجاد MsgWay Client
        $client = new MsgWayClient(
            $settings['sms_api_key'],
            $settings['sms_sender_number'],
            $settings['sms_api_base_url'] ?? 'https://msgway.com/api/v1'
        );

        // Rate Limiting Parameters
        $daily_limit = (int)($settings['sms_daily_limit'] ?? 1000);
        $rate_per_second = (int)($settings['sms_rate_per_second'] ?? 10);
        $retry_attempts = (int)($settings['sms_retry_attempts'] ?? 3);
        $retry_delay = (int)($settings['sms_retry_delay'] ?? 5);

        // بررسی سقف روزانه
        $today_sent = getTodaySentCount($pdo);
        if ($today_sent >= $daily_limit) {
            echo "[WARNING] Daily limit reached ({$daily_limit}). Stopping.\n";
            updateCampaignStatus($pdo, $campaign['id'], 'paused', [
                'pause_reason' => 'Daily limit reached'
            ]);
            return ['sent' => 0, 'failed' => 0];
        }

        // محاسبه تأخیر بین پیام‌ها (میکروثانیه)
        $delay_microseconds = (int)(1000000 / $rate_per_second);

        // ارسال به هر گیرنده
        foreach ($recipients as $recipient) {
            // بررسی سقف روزانه در هر iteration
            if (getTodaySentCount($pdo) >= $daily_limit) {
                echo "[WARNING] Daily limit reached during execution. Stopping.\n";
                break;
            }

            // بررسی تکراری نبودن
            if (isDuplicateLog($pdo, $campaign['id'], $recipient['phone'])) {
                echo "[SKIP] Duplicate: {$recipient['phone']}\n";
                continue;
            }

            // شخصی‌سازی محتوا
            $content = personalizeContent($template['content'], $recipient);

            // ارسال با Retry Logic
            $success = false;
            $message_id = null;
            $error_message = null;

            for ($attempt = 1; $attempt <= $retry_attempts; $attempt++) {
                try {
                    $response = $client->sendSms($recipient['phone'], $content);

                    if ($response['success']) {
                        $success = true;
                        $message_id = $response['message_id'];
                        break;
                    } else {
                        $error_message = $response['error'] ?? 'Unknown error';
                    }
                } catch (Exception $e) {
                    $error_message = $e->getMessage();
                }

                if ($attempt < $retry_attempts) {
                    echo "[RETRY] Attempt {$attempt} failed. Waiting {$retry_delay}s...\n";
                    sleep($retry_delay);
                }
            }

            // ثبت لاگ
            logSmsAttempt($pdo, [
                'campaign_id' => $campaign['id'],
                'phone' => $recipient['phone'],
                'content' => $content,
                'message_id' => $message_id,
                'status' => $success ? 'sent' : 'failed',
                'error_message' => $error_message,
                'cost' => $success ? (float)($settings['sms_cost_per_message'] ?? 0) : 0,
                'metadata' => json_encode([
                    'recipient_name' => $recipient['name'] ?? null,
                    'recipient_id' => $recipient['id'] ?? null,
                    'attempts' => $attempt
                ], JSON_UNESCAPED_UNICODE)
            ]);

            if ($success) {
                $sent_count++;
                echo "[SENT] {$recipient['phone']} (ID: {$message_id})\n";
            } else {
                $failed_count++;
                echo "[FAILED] {$recipient['phone']}: {$error_message}\n";
            }

            // Rate Limiting Delay
            usleep($delay_microseconds);
        }

        // به‌روزرسانی آمار کمپین
        updateCampaignStats($pdo, $campaign['id']);

        // بررسی تکمیل کمپین
        $stats = getCampaignStats($pdo, $campaign['id']);
        if ($stats['progress'] >= 100) {
            updateCampaignStatus($pdo, $campaign['id'], 'completed');
            echo "[INFO] Campaign completed.\n";
        }

    } catch (Exception $e) {
        echo "[ERROR] Campaign processing failed: " . $e->getMessage() . "\n";
        updateCampaignStatus($pdo, $campaign['id'], 'failed', [
            'error' => $e->getMessage()
        ]);
        error_log("Campaign {$campaign['id']} error: " . $e->getMessage());
    }

    return ['sent' => $sent_count, 'failed' => $failed_count];
}

/**
 * تغییر وضعیت کمپین
 */
function updateCampaignStatus(PDO $pdo, int $campaign_id, string $status, array $metadata = []): void {
    $stmt = $pdo->prepare("
        UPDATE sms_campaigns
        SET status = ?,
            started_at = CASE WHEN ? = 'running' AND started_at IS NULL THEN NOW() ELSE started_at END,
            completed_at = CASE WHEN ? = 'completed' THEN NOW() ELSE completed_at END,
            metadata = ?,
            updated_at = NOW()
        WHERE id = ?
    ");

    $stmt->execute([
        $status,
        $status,
        $status,
        json_encode($metadata, JSON_UNESCAPED_UNICODE),
        $campaign_id
    ]);

    // ثبت لاگ فعالیت
    logActivity(0, 'campaign_status_change', 'sms_campaigns', $campaign_id, [
        'new_status' => $status,
        'metadata' => $metadata
    ]);
}

/**
 * بارگذاری الگو
 */
function loadTemplate(PDO $pdo, int $template_id): ?array {
    $stmt = $pdo->prepare("
        SELECT id, title, content, variables
        FROM sms_templates
        WHERE id = ? AND status = 'active' AND deleted_at IS NULL
    ");
    $stmt->execute([$template_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * بارگذاری گیرندگان
 */
function loadRecipients(PDO $pdo, array $campaign): array {
    $filter = json_decode($campaign['recipient_filter'], true);

    switch ($campaign['recipient_type']) {
        case 'all_customers':
            return loadAllCustomers($pdo, $filter);
        
        case 'all_leads':
            return loadAllLeads($pdo, $filter);
        
        case 'custom':
            return loadCustomRecipients($pdo, $filter);
        
        default:
            return [];
    }
}

/**
 * بارگذاری تمام مشتریان
 */
function loadAllCustomers(PDO $pdo, ?array $filter): array {
    $sql = "
        SELECT 
            id,
            CONCAT(first_name, ' ', last_name) as name,
            mobile as phone,
            email
        FROM customers
        WHERE deleted_at IS NULL
          AND mobile IS NOT NULL
          AND mobile != ''
    ";

    if (!empty($filter['status'])) {
        $sql .= " AND status = " . $pdo->quote($filter['status']);
    }

    if (!empty($filter['city'])) {
        $sql .= " AND city = " . $pdo->quote($filter['city']);
    }

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * بارگذاری تمام سرنخ‌ها
 */
function loadAllLeads(PDO $pdo, ?array $filter): array {
    $sql = "
        SELECT 
            id,
            contact_name as name,
            phone,
            email
        FROM leads
        WHERE deleted_at IS NULL
          AND phone IS NOT NULL
          AND phone != ''
    ";

    if (!empty($filter['status'])) {
        $sql .= " AND status = " . $pdo->quote($filter['status']);
    }

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * بارگذاری گیرندگان سفارشی
 */
function loadCustomRecipients(PDO $pdo, ?array $filter): array {
    if (empty($filter['phone_numbers'])) {
        return [];
    }

    $recipients = [];
    $phones = is_array($filter['phone_numbers']) 
        ? $filter['phone_numbers'] 
        : explode("\n", $filter['phone_numbers']);

    foreach ($phones as $phone) {
        $phone = trim($phone);
        if (!empty($phone)) {
            $recipients[] = [
                'id' => null,
                'name' => null,
                'phone' => $phone,
                'email' => null
            ];
        }
    }

    return $recipients;
}

/**
 * شخصی‌سازی محتوا
 */
function personalizeContent(string $content, array $recipient): string {
    $replacements = [
        '{name}' => $recipient['name'] ?? '',
        '{phone}' => $recipient['phone'] ?? '',
        '{email}' => $recipient['email'] ?? '',
        '{date}' => jdate('Y/m/d'),
        '{time}' => jdate('H:i'),
    ];

    return str_replace(array_keys($replacements), array_values($replacements), $content);
}

/**
 * ثبت لاگ پیامک
 */
function logSmsAttempt(PDO $pdo, array $data): void {
    $stmt = $pdo->prepare("
        INSERT INTO sms_logs (
            campaign_id, phone, content, message_id, status, 
            error_message, cost, metadata, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $data['campaign_id'],
        $data['phone'],
        $data['content'],
        $data['message_id'],
        $data['status'],
        $data['error_message'],
        $data['cost'],
        $data['metadata']
    ]);
}

/**
 * بررسی تکراری بودن
 */
function isDuplicateLog(PDO $pdo, int $campaign_id, string $phone): bool {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM sms_logs 
        WHERE campaign_id = ? AND phone = ?
    ");
    $stmt->execute([$campaign_id, $phone]);
    return $stmt->fetchColumn() > 0;
}

/**
 * دریافت تعداد ارسال امروز
 */
function getTodaySentCount(PDO $pdo): int {
    $stmt = $pdo->query("
        SELECT COUNT(*) 
        FROM sms_logs 
        WHERE DATE(created_at) = CURDATE()
          AND status IN ('sent', 'delivered')
    ");
    return (int)$stmt->fetchColumn();
}

/**
 * به‌روزرسانی آمار کمپین
 */
function updateCampaignStats(PDO $pdo, int $campaign_id): void {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status IN ('sent', 'delivered') THEN 1 ELSE 0 END) as sent,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
        FROM sms_logs
        WHERE campaign_id = ?
    ");
    $stmt->execute([$campaign_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($stats && $stats['total'] > 0) {
        $progress = round((($stats['sent'] + $stats['failed']) / $stats['total']) * 100, 2);

        $update = $pdo->prepare("
            UPDATE sms_campaigns
            SET sent_count = ?,
                failed_count = ?,
                pending_count = ?,
                progress = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $update->execute([
            $stats['sent'],
            $stats['failed'],
            $stats['pending'],
            $progress,
            $campaign_id
        ]);
    }
}

/**
 * دریافت آمار کمپین
 */
function getCampaignStats(PDO $pdo, int $campaign_id): array {
    $stmt = $pdo->prepare("
        SELECT sent_count, failed_count, pending_count, progress
        FROM sms_campaigns
        WHERE id = ?
    ");
    $stmt->execute([$campaign_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
        'sent_count' => 0,
        'failed_count' => 0,
        'pending_count' => 0,
        'progress' => 0
    ];
}
