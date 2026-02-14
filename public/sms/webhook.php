<?php
/**
 * ══════════════════════════════════════════════════════════════════════════════
 * ReadyCRM V3.6 - SMS WEBHOOK HANDLER
 * ══════════════════════════════════════════════════════════════════════════════
 * دریافت و پردازش Delivery Reports از MessageWay API
 * 
 * این endpoint توسط MessageWay فراخوانی می‌شود هنگامی که وضعیت پیامک تغییر کند:
 * - delivered (تحویل داده شد)
 * - failed (ناموفق)
 * - expired (منقضی شد)
 * - rejected (رد شد)
 *
 * @version 3.6.0
 * @author ReadyCRM Team
 * @package ReadyCRM\SMS\Webhook
 * ══════════════════════════════════════════════════════════════════════════════
 */

// ─── ONLY ACCEPT POST REQUESTS ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ─── BOOTSTRAP ───────────────────────────────────────────────────────────────
require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/database.php';
require_once __DIR__ . '/../../private/functions.php';

// ─── LOAD WEBHOOK SECRET FROM SETTINGS ───────────────────────────────────────
function getWebhookSecret(PDO $pdo): ?string {
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'sms_webhook_secret'");
        $stmt->execute();
        return $stmt->fetchColumn() ?: null;
    } catch (PDOException $e) {
        error_log('Webhook getWebhookSecret error: ' . $e->getMessage());
        return null;
    }
}

$secret = getWebhookSecret($pdo);

if (empty($secret)) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'Webhook not configured'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ─── GET RAW POST DATA ───────────────────────────────────────────────────────
$raw_payload = file_get_contents('php://input');

if (empty($raw_payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Empty payload'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ─── SIGNATURE VALIDATION ────────────────────────────────────────────────────
$signature = $_SERVER['HTTP_X_MSGWAY_SIGNATURE'] ?? '';

if (empty($signature)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Missing signature'], JSON_UNESCAPED_UNICODE);
    logWebhookAttempt('signature_missing', $raw_payload);
    exit;
}

$expected_signature = hash_hmac('sha256', $raw_payload, $secret);

if (!hash_equals($expected_signature, $signature)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid signature'], JSON_UNESCAPED_UNICODE);
    logWebhookAttempt('signature_invalid', $raw_payload);
    exit;
}

// ─── PARSE JSON PAYLOAD ──────────────────────────────────────────────────────
$payload = json_decode($raw_payload, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON'], JSON_UNESCAPED_UNICODE);
    logWebhookAttempt('json_invalid', $raw_payload);
    exit;
}

// ─── VALIDATE REQUIRED FIELDS ────────────────────────────────────────────────
$required_fields = ['message_id', 'status', 'phone', 'delivered_at'];

foreach ($required_fields as $field) {
    if (!isset($payload[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Missing field: {$field}"], JSON_UNESCAPED_UNICODE);
        logWebhookAttempt('missing_field_' . $field, $raw_payload);
        exit;
    }
}

// ─── EXTRACT DATA ────────────────────────────────────────────────────────────
$message_id   = trim($payload['message_id']);
$status       = trim($payload['status']);
$phone        = trim($payload['phone']);
$delivered_at = trim($payload['delivered_at']);
$error_code   = isset($payload['error_code']) ? trim($payload['error_code']) : null;
$error_msg    = isset($payload['error_message']) ? trim($payload['error_message']) : null;

// ─── MAP STATUS VALUES ───────────────────────────────────────────────────────
$status_map = [
    'delivered' => 'delivered',
    'failed'    => 'failed',
    'expired'   => 'failed',
    'rejected'  => 'failed',
    'pending'   => 'pending',
    'sent'      => 'sent',
];

$db_status = $status_map[$status] ?? 'unknown';

// ─── UPDATE SMS LOG ──────────────────────────────────────────────────────────
try {
    // Check if record exists
    $stmt = $pdo->prepare("SELECT id, campaign_id, status FROM sms_logs WHERE message_id = ?");
    $stmt->execute([$message_id]);
    $log = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$log) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Message ID not found'], JSON_UNESCAPED_UNICODE);
        logWebhookAttempt('message_not_found', $raw_payload);
        exit;
    }

    // Don't overwrite final statuses (delivered/failed) with intermediate ones
    if (in_array($log['status'], ['delivered', 'failed']) && $db_status === 'sent') {
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Status already final'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Update the log
    $update_stmt = $pdo->prepare("
        UPDATE sms_logs
        SET status          = ?,
            delivered_at    = ?,
            error_code      = ?,
            error_message   = ?,
            webhook_payload = ?,
            updated_at      = NOW()
        WHERE message_id = ?
    ");

    $update_stmt->execute([
        $db_status,
        $delivered_at,
        $error_code,
        $error_msg,
        $raw_payload,
        $message_id
    ]);

    // Update campaign stats if applicable
    if (!empty($log['campaign_id'])) {
        updateCampaignStats($pdo, (int)$log['campaign_id']);
    }

    // Log activity
    logActivity(0, 'webhook_delivery_report', 'sms_logs', $log['id'], [
        'message_id' => $message_id,
        'phone'      => maskPhone($phone),
        'status'     => $db_status,
    ]);

    // Success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Delivery report processed',
        'data'    => [
            'log_id'     => $log['id'],
            'message_id' => $message_id,
            'status'     => $db_status,
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log('Webhook DB error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error'], JSON_UNESCAPED_UNICODE);
    logWebhookAttempt('db_error', $raw_payload);
}

exit;

// ═══════════════════════════════════════════════════════════════════════════════
// HELPER FUNCTIONS
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Update campaign statistics after delivery report
 */
function updateCampaignStats(PDO $pdo, int $campaign_id): void {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
            FROM sms_logs
            WHERE campaign_id = ?
        ");
        $stmt->execute([$campaign_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($stats && $stats['total'] > 0) {
            $progress = round((($stats['delivered'] + $stats['failed']) / $stats['total']) * 100, 2);

            $update_stmt = $pdo->prepare("
                UPDATE sms_campaigns
                SET sent_count    = ?,
                    failed_count  = ?,
                    pending_count = ?,
                    progress      = ?,
                    updated_at    = NOW()
                WHERE id = ?
            ");

            $update_stmt->execute([
                $stats['delivered'],
                $stats['failed'],
                $stats['pending'],
                $progress,
                $campaign_id
            ]);

            // Auto-complete campaign if all messages processed
            if ($progress >= 100) {
                $complete_stmt = $pdo->prepare("
                    UPDATE sms_campaigns
                    SET status       = 'completed',
                        completed_at = NOW()
                    WHERE id = ? AND status = 'running'
                ");
                $complete_stmt->execute([$campaign_id]);
            }
        }
    } catch (PDOException $e) {
        error_log('updateCampaignStats error: ' . $e->getMessage());
    }
}

/**
 * Mask phone number for logging (privacy)
 */
function maskPhone(string $phone): string {
    if (strlen($phone) < 4) {
        return $phone;
    }
    return substr($phone, 0, 4) . str_repeat('*', strlen($phone) - 7) . substr($phone, -3);
}

/**
 * Log failed webhook attempts for debugging
 */
function logWebhookAttempt(string $reason, string $payload): void {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO webhook_logs (event_type, reason, payload, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            'sms_delivery',
            $reason,
            $payload,
            getRealIpAddr(),
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
    } catch (PDOException $e) {
        error_log('logWebhookAttempt error: ' . $e->getMessage());
    }
}

/**
 * Get real IP address (handles proxies/load balancers)
 */
function getRealIpAddr(): string {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
}
