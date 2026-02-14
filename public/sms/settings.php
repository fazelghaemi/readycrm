<?php
/**
 * ══════════════════════════════════════════════════════════════════════════════
 * ReadyCRM V3.6 - SMS MODULE SETTINGS
 * ══════════════════════════════════════════════════════════════════════════════
 * تنظیمات ماژول پیامک (MessageWay API)
 * - مدیریت API Key و Sender Line
 * - تنظیمات ارسال و Rate Limiting
 * - تست اتصال به API
 * - پیش‌فرض‌های کمپین
 *
 * @version 3.6.0
 * @author ReadyCRM Team
 * @package ReadyCRM\SMS
 * ══════════════════════════════════════════════════════════════════════════════
 */

$page_title = 'تنظیمات پیامک';
$breadcrumb = [
    ['title' => 'داشبورد', 'url' => 'dashboard.php'],
    ['title' => 'تنظیمات پیامک'],
];

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/database.php';
require_once __DIR__ . '/../private/auth.php';
require_once __DIR__ . '/../private/functions.php';
require_once __DIR__ . '/../private/sms/MsgWayClient.php';

// ─── AUTH CHECK ─────────────────────────────────────────────────────────────
requireLogin();
if (!hasRole('admin')) {
    setMessage('فقط مدیران سیستم به این صفحه دسترسی دارند', 'error');
    header('Location: dashboard.php');
    exit();
}

// ─── SVG ICONS ──────────────────────────────────────────────────────────────
$icons = [
    'key'       => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>',
    'phone'     => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.42 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.34 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.09 6.09l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
    'settings'  => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
    'check'     => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>',
    'send'      => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>',
    'save'      => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>',
    'eye'       => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
    'eye_off'   => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>',
    'alert'     => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    'info'      => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
    'link'      => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
    'zap'       => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
    'activity'  => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>',
];

// ─── LOAD CURRENT SETTINGS ───────────────────────────────────────────────────
function getSmsSettings(PDO $pdo): array {
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'sms_%'");
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        return $rows ?: [];
    } catch (PDOException $e) {
        error_log('getSmsSettings error: ' . $e->getMessage());
        return [];
    }
}

function saveSmsSettings(PDO $pdo, array $data, int $user_id): bool {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, setting_value, updated_by, updated_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value),
                updated_by    = VALUES(updated_by),
                updated_at    = NOW()
        ");
        foreach ($data as $key => $value) {
            $stmt->execute([$key, $value, $user_id]);
        }
        return true;
    } catch (PDOException $e) {
        error_log('saveSmsSettings error: ' . $e->getMessage());
        return false;
    }
}

// ─── HANDLE POST ACTIONS ─────────────────────────────────────────────────────
$test_result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($csrf_token)) {
        setMessage('درخواست نامعتبر. لطفاً مجدداً تلاش کنید.', 'error');
        header('Location: settings.php');
        exit();
    }

    $action = $_POST['action'] ?? 'save';

    // ─── SAVE SETTINGS ───────────────────────────────────────────────────────
    if ($action === 'save') {
        $settings_to_save = [
            'sms_api_key'           => trim($_POST['sms_api_key'] ?? ''),
            'sms_sender_number'     => trim($_POST['sms_sender_number'] ?? ''),
            'sms_base_url'          => rtrim(trim($_POST['sms_base_url'] ?? 'https://api.msgway.com'), '/'),
            'sms_enabled'           => isset($_POST['sms_enabled']) ? '1' : '0',
            'sms_daily_limit'       => max(1, (int)($_POST['sms_daily_limit'] ?? 1000)),
            'sms_rate_per_second'   => max(1, (int)($_POST['sms_rate_per_second'] ?? 10)),
            'sms_retry_attempts'    => min(5, max(0, (int)($_POST['sms_retry_attempts'] ?? 3))),
            'sms_retry_delay'       => max(1, (int)($_POST['sms_retry_delay'] ?? 60)),
            'sms_default_signature' => trim($_POST['sms_default_signature'] ?? ''),
            'sms_cost_per_sms'      => max(0, (float)($_POST['sms_cost_per_sms'] ?? 0)),
            'sms_log_enabled'       => isset($_POST['sms_log_enabled']) ? '1' : '0',
            'sms_webhook_secret'    => trim($_POST['sms_webhook_secret'] ?? ''),
        ];

        // اگر api_key خالی ارسال شد و قبلاً مقدار داشت، مقدار قبلی را حفظ کن
        $existing = getSmsSettings($pdo);
        if (empty($settings_to_save['sms_api_key']) && !empty($existing['sms_api_key'])) {
            unset($settings_to_save['sms_api_key']);
        }

        $saved = saveSmsSettings($pdo, $settings_to_save, $_SESSION['user_id']);

        if ($saved) {
            logActivity($_SESSION['user_id'], 'update_sms_settings', 'settings', 0, [
                'sender_number' => $settings_to_save['sms_sender_number'] ?? '',
                'sms_enabled'   => $settings_to_save['sms_enabled'],
            ]);
            setMessage('تنظیمات پیامک با موفقیت ذخیره شد', 'success');
        } else {
            setMessage('خطا در ذخیره‌سازی تنظیمات. لطفاً مجدداً تلاش کنید.', 'error');
        }

        header('Location: settings.php');
        exit();
    }

    // ─── TEST CONNECTION ─────────────────────────────────────────────────────
    if ($action === 'test') {
        $api_key = trim($_POST['test_api_key'] ?? '');
        $test_phone = trim($_POST['test_phone'] ?? '');
        $sender    = trim($_POST['test_sender'] ?? '');

        if (empty($api_key) || empty($test_phone) || empty($sender)) {
            $test_result = ['success' => false, 'message' => 'کلید API، شماره فرستنده و شماره آزمایشی الزامی هستند'];
        } elseif (!preg_match('/^09\d{9}$/', $test_phone)) {
            $test_result = ['success' => false, 'message' => 'فرمت شماره آزمایشی نامعتبر است (باید 09XXXXXXXXX باشد)'];
        } else {
            try {
                $client = new MsgWayClient($api_key, $sender);
                $result = $client->send($test_phone, 'پیام آزمایشی از سیستم ReadyCRM — ' . jdate('Y/m/d H:i'));
                $test_result = $result;
                if ($result['success']) {
                    logActivity($_SESSION['user_id'], 'test_sms_connection', 'settings', 0, [
                        'test_phone' => $test_phone,
                    ]);
                }
            } catch (Exception $e) {
                $test_result = ['success' => false, 'message' => 'خطای اتصال: ' . $e->getMessage()];
            }
        }
    }

    // ─── REGENERATE WEBHOOK SECRET ───────────────────────────────────────────
    if ($action === 'regen_webhook') {
        $new_secret = bin2hex(random_bytes(24));
        saveSmsSettings($pdo, ['sms_webhook_secret' => $new_secret], $_SESSION['user_id']);
        logActivity($_SESSION['user_id'], 'regen_sms_webhook_secret', 'settings', 0);
        setMessage('Webhook Secret جدید با موفقیت ساخته شد', 'success');
        header('Location: settings.php');
        exit();
    }
}

// ─── LOAD SETTINGS FOR DISPLAY ───────────────────────────────────────────────
$s = getSmsSettings($pdo);

$defaults = [
    'sms_api_key'           => '',
    'sms_sender_number'     => '',
    'sms_base_url'          => 'https://api.msgway.com',
    'sms_enabled'           => '0',
    'sms_daily_limit'       => '1000',
    'sms_rate_per_second'   => '10',
    'sms_retry_attempts'    => '3',
    'sms_retry_delay'       => '60',
    'sms_default_signature' => '',
    'sms_cost_per_sms'      => '0',
    'sms_log_enabled'       => '1',
    'sms_webhook_secret'    => '',
];
$s = array_merge($defaults, $s);

// آمار خلاصه
try {
    $sent_today    = $pdo->query("SELECT COUNT(*) FROM sms_logs WHERE DATE(sent_at) = CURDATE()")->fetchColumn();
    $sent_month    = $pdo->query("SELECT COUNT(*) FROM sms_logs WHERE MONTH(sent_at) = MONTH(NOW()) AND YEAR(sent_at) = YEAR(NOW())")->fetchColumn();
    $failed_today  = $pdo->query("SELECT COUNT(*) FROM sms_logs WHERE DATE(sent_at) = CURDATE() AND status = 'failed'")->fetchColumn();
    $total_cost    = $pdo->query("SELECT COALESCE(SUM(cost),0) FROM sms_logs WHERE MONTH(sent_at) = MONTH(NOW())")->fetchColumn();
} catch (PDOException $e) {
    $sent_today = $sent_month = $failed_today = $total_cost = 0;
}

$csrf_token = generateCSRFToken();
include __DIR__ . '/../private/header.php';
?>

<!-- ─── STYLES ────────────────────────────────────────────────────────────── -->
<style>
    :root {
        --brand: #00b0a4;
        --brand-dark: #008c82;
        --brand-light: #e6f7f7;
        --dark: #1e293b;
        --text-gray: #64748b;
        --border: #e2e8f0;
        --bg: #f1f5f9;
        --card-r: 16px;
        --tr: all .25s ease;
    }

    body { background: var(--bg); }

    /* ─── Stats Row ─── */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 28px;
    }

    .stat-card {
        background: white;
        border-radius: var(--card-r);
        padding: 22px 20px;
        border: 1px solid var(--border);
        display: flex;
        align-items: center;
        gap: 16px;
        transition: var(--tr);
    }

    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.06); border-color: var(--brand); }

    .stat-icon {
        width: 52px; height: 52px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        color: white;
    }

    .stat-icon.teal    { background: linear-gradient(135deg,#00b0a4,#00d4c5); }
    .stat-icon.blue    { background: linear-gradient(135deg,#3b82f6,#2563eb); }
    .stat-icon.red     { background: linear-gradient(135deg,#ef4444,#dc2626); }
    .stat-icon.purple  { background: linear-gradient(135deg,#8b5cf6,#7c3aed); }

    .stat-body h3 { font-size: 1.75rem; font-weight: 800; color: var(--dark); margin: 0; line-height: 1; }
    .stat-body p  { margin: 5px 0 0; color: var(--text-gray); font-size: .85rem; }

    /* ─── Layout ─── */
    .settings-grid {
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 24px;
        align-items: start;
    }

    /* ─── Cards ─── */
    .settings-card {
        background: white;
        border-radius: var(--card-r);
        border: 1px solid var(--border);
        overflow: hidden;
        margin-bottom: 24px;
    }

    .card-header {
        padding: 18px 24px;
        border-bottom: 1px solid var(--border);
        background: #fafbfc;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .card-header svg { color: var(--brand); flex-shrink: 0; }

    .card-header h5 {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: var(--dark);
    }

    .card-body { padding: 24px; }

    /* ─── Form Elements ─── */
    .form-group { margin-bottom: 20px; }
    .form-group:last-child { margin-bottom: 0; }

    .form-label {
        display: block;
        font-size: .875rem;
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 8px;
    }

    .form-label .req { color: #ef4444; margin-right: 2px; }
    .form-label .hint { font-weight: 400; color: var(--text-gray); font-size: .8rem; margin-right: 6px; }

    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1.5px solid var(--border);
        border-radius: 10px;
        font-size: .9375rem;
        color: var(--dark);
        transition: var(--tr);
        background: white;
        font-family: inherit;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--brand);
        box-shadow: 0 0 0 3px rgba(0,176,164,.12);
    }

    .form-control.mono { font-family: 'Courier New', monospace; font-size: .85rem; letter-spacing: .03em; }

    .input-group { display: flex; gap: 0; }
    .input-group .form-control { border-radius: 10px 0 0 10px; border-left: none; flex: 1; }
    .input-group .ig-btn {
        padding: 10px 14px;
        border: 1.5px solid var(--border);
        border-radius: 0 10px 10px 0;
        background: #f8fafc;
        cursor: pointer;
        color: var(--text-gray);
        transition: var(--tr);
        display: flex; align-items: center;
    }
    .input-group .ig-btn:hover { background: var(--brand-light); color: var(--brand); border-color: var(--brand); }

    .form-hint { font-size: .8rem; color: var(--text-gray); margin-top: 6px; display: flex; align-items: center; gap: 4px; }
    .form-hint svg { flex-shrink: 0; }

    /* ─── Toggle Switch ─── */
    .toggle-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .toggle-row:last-child { border-bottom: none; padding-bottom: 0; }
    .toggle-row:first-child { padding-top: 0; }

    .toggle-info h6 { font-size: .9375rem; font-weight: 600; color: var(--dark); margin: 0 0 3px; }
    .toggle-info p  { font-size: .8rem; color: var(--text-gray); margin: 0; }

    .toggle-switch { position: relative; width: 48px; height: 26px; flex-shrink: 0; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .toggle-switch .slider {
        position: absolute; inset: 0;
        background: #cbd5e1; border-radius: 26px;
        cursor: pointer; transition: .3s;
    }
    .toggle-switch .slider::before {
        content: '';
        position: absolute;
        width: 20px; height: 20px;
        left: 3px; top: 3px;
        background: white;
        border-radius: 50%;
        transition: .3s;
        box-shadow: 0 1px 4px rgba(0,0,0,.15);
    }
    .toggle-switch input:checked + .slider { background: var(--brand); }
    .toggle-switch input:checked + .slider::before { transform: translateX(22px); }

    /* ─── Buttons ─── */
    .btn-brand {
        background: var(--brand);
        color: white; border: none;
        padding: 11px 22px;
        border-radius: 10px;
        font-weight: 700;
        font-size: .9375rem;
        cursor: pointer;
        display: inline-flex; align-items: center; gap: 8px;
        transition: var(--tr);
        font-family: inherit;
    }
    .btn-brand:hover { background: var(--brand-dark); transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,176,164,.3); }

    .btn-outline {
        background: white;
        color: var(--text-gray);
        border: 1.5px solid var(--border);
        padding: 10px 18px;
        border-radius: 10px;
        font-weight: 600;
        font-size: .9rem;
        cursor: pointer;
        display: inline-flex; align-items: center; gap: 7px;
        transition: var(--tr);
        font-family: inherit;
        text-decoration: none;
    }
    .btn-outline:hover { border-color: var(--brand); color: var(--brand); }

    .btn-danger {
        background: #fef2f2; color: #dc2626;
        border: 1.5px solid #fecaca;
        padding: 10px 18px; border-radius: 10px;
        font-weight: 600; font-size: .9rem;
        cursor: pointer;
        display: inline-flex; align-items: center; gap: 7px;
        transition: var(--tr); font-family: inherit;
    }
    .btn-danger:hover { background: #fee2e2; border-color: #dc2626; }

    /* ─── Status Badge ─── */
    .status-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 14px; border-radius: 20px;
        font-size: .8rem; font-weight: 700;
    }
    .badge-on  { background: #d1fae5; color: #065f46; }
    .badge-off { background: #fee2e2; color: #991b1b; }
    .badge-dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; animation: pulse 1.5s infinite; }
    @keyframes pulse { 0%,100%{ opacity:1 } 50%{ opacity:.4 } }

    /* ─── Alert Box ─── */
    .alert-box {
        border-radius: 12px; padding: 14px 16px;
        display: flex; align-items: flex-start; gap: 10px;
        margin-bottom: 20px;
        font-size: .9rem;
    }
    .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
    .alert-error   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    .alert-info    { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
    .alert-warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

    /* ─── Sidebar Cards ─── */
    .sidebar-card {
        background: white;
        border-radius: var(--card-r);
        border: 1px solid var(--border);
        overflow: hidden;
        margin-bottom: 20px;
    }

    .sidebar-card .card-header { padding: 16px 20px; }
    .sidebar-card .card-body   { padding: 20px; }

    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #f1f5f9;
        font-size: .875rem;
    }
    .info-row:last-child { border-bottom: none; padding-bottom: 0; }
    .info-row:first-child { padding-top: 0; }
    .info-row .label { color: var(--text-gray); }
    .info-row .value { font-weight: 600; color: var(--dark); font-size: .8rem; word-break: break-all; max-width: 55%; text-align: left; }

    .webhook-url {
        background: #f8fafc;
        border: 1.5px solid var(--border);
        border-radius: 10px;
        padding: 10px 14px;
        font-size: .8rem;
        font-family: monospace;
        color: var(--dark);
        word-break: break-all;
        margin-bottom: 12px;
    }

    /* ─── Divider ─── */
    .section-divider {
        border: none;
        border-top: 1px solid #f1f5f9;
        margin: 20px 0;
    }

    /* ─── Number Input ─── */
    .input-with-unit { position: relative; }
    .input-with-unit .form-control { padding-left: 60px; }
    .input-with-unit .unit {
        position: absolute;
        left: 12px; top: 50%;
        transform: translateY(-50%);
        font-size: .8rem;
        color: var(--text-gray);
        pointer-events: none;
        background: white;
        padding: 0 4px;
    }

    /* ─── Responsive ─── */
    @media (max-width: 1024px) {
        .settings-grid { grid-template-columns: 1fr; }
        .stats-row { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 640px) {
        .stats-row { grid-template-columns: 1fr 1fr; }
    }
</style>

<!-- ─── FLASH MESSAGES ────────────────────────────────────────────────────── -->
<?php echo displayMessage(); ?>

<!-- ─── TEST RESULT ALERT ─────────────────────────────────────────────────── -->
<?php if ($test_result !== null): ?>
<div class="alert-box <?php echo $test_result['success'] ? 'alert-success' : 'alert-error'; ?>">
    <?php echo $test_result['success'] ? $icons['check'] : $icons['alert']; ?>
    <div>
        <strong><?php echo $test_result['success'] ? 'اتصال موفق' : 'خطا در اتصال'; ?></strong><br>
        <small><?php echo htmlspecialchars($test_result['message'] ?? ''); ?></small>
    </div>
</div>
<?php endif; ?>

<!-- ─── STATS ROW ─────────────────────────────────────────────────────────── -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-icon teal"><?php echo $icons['send']; ?></div>
        <div class="stat-body">
            <h3><?php echo number_format($sent_today); ?></h3>
            <p>ارسال امروز</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><?php echo $icons['activity']; ?></div>
        <div class="stat-body">
            <h3><?php echo number_format($sent_month); ?></h3>
            <p>ارسال این ماه</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><?php echo $icons['alert']; ?></div>
        <div class="stat-body">
            <h3><?php echo number_format($failed_today); ?></h3>
            <p>خطای امروز</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><?php echo $icons['zap']; ?></div>
        <div class="stat-body">
            <h3><?php echo formatMoney($total_cost); ?></h3>
            <p>هزینه این ماه</p>
        </div>
    </div>
</div>

<!-- ─── MAIN GRID ─────────────────────────────────────────────────────────── -->
<div class="settings-grid">

    <!-- ══════════ LEFT COLUMN: SETTINGS FORMS ══════════ -->
    <div>

        <!-- ── SECTION 1: API Credentials ── -->
        <div class="settings-card">
            <div class="card-header">
                <?php echo $icons['key']; ?>
                <h5>اعتبارنامه API — MessageWay</h5>
                <span class="status-badge <?php echo $s['sms_enabled'] == '1' ? 'badge-on' : 'badge-off'; ?>" style="margin-right: auto;">
                    <span class="badge-dot"></span>
                    <?php echo $s['sms_enabled'] == '1' ? 'فعال' : 'غیرفعال'; ?>
                </span>
            </div>
            <div class="card-body">
                <form method="POST" id="formMain" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="save">

                    <div class="form-group">
                        <label class="form-label">
                            کلید API <span class="req">*</span>
                            <span class="hint">(برای حفظ کلید فعلی، خالی بگذارید)</span>
                        </label>
                        <div class="input-group">
                            <input type="password" name="sms_api_key" id="apiKeyInput"
                                   class="form-control mono"
                                   placeholder="<?php echo !empty($s['sms_api_key']) ? '••••••••••••••••••••••••' : 'کلید API را وارد کنید'; ?>"
                                   autocomplete="new-password">
                            <button type="button" class="ig-btn" id="toggleApiKey" title="نمایش/مخفی">
                                <span id="eyeIcon"><?php echo $icons['eye']; ?></span>
                            </button>
                        </div>
                        <p class="form-hint">
                            <?php echo $icons['link']; ?>
                            کلید API را از پنل <a href="https://app.msgway.com" target="_blank" style="color:var(--brand);">MessageWay</a> دریافت کنید
                        </p>
                    </div>

                    <div class="form-group">
                        <label class="form-label">شماره فرستنده (Sender Line) <span class="req">*</span></label>
                        <input type="text" name="sms_sender_number" class="form-control"
                               placeholder="مثال: 30007100"
                               value="<?php echo htmlspecialchars($s['sms_sender_number']); ?>">
                        <p class="form-hint"><?php echo $icons['info']; ?> شماره خط اشتراکی ثبت‌شده در سرویس پیامک</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label">آدرس پایه API</label>
                        <input type="url" name="sms_base_url" class="form-control mono"
                               value="<?php echo htmlspecialchars($s['sms_base_url']); ?>">
                        <p class="form-hint"><?php echo $icons['info']; ?> معمولاً نیازی به تغییر ندارد</p>
                    </div>

                    <hr class="section-divider">

                    <!-- ── SECTION 2: Rate Limiting ── -->
                    <h6 style="font-weight:700;color:var(--dark);margin-bottom:18px;display:flex;align-items:center;gap:8px;">
                        <?php echo $icons['zap']; ?> محدودیت‌های ارسال
                    </h6>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div class="form-group">
                            <label class="form-label">حداکثر ارسال روزانه</label>
                            <div class="input-with-unit">
                                <input type="number" name="sms_daily_limit" class="form-control"
                                       min="1" max="100000"
                                       value="<?php echo (int)$s['sms_daily_limit']; ?>">
                                <span class="unit">پیام</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">نرخ ارسال (در ثانیه)</label>
                            <div class="input-with-unit">
                                <input type="number" name="sms_rate_per_second" class="form-control"
                                       min="1" max="100"
                                       value="<?php echo (int)$s['sms_rate_per_second']; ?>">
                                <span class="unit">پیام</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">تعداد تلاش مجدد</label>
                            <div class="input-with-unit">
                                <input type="number" name="sms_retry_attempts" class="form-control"
                                       min="0" max="5"
                                       value="<?php echo (int)$s['sms_retry_attempts']; ?>">
                                <span class="unit">بار</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">تأخیر بین تلاش‌ها</label>
                            <div class="input-with-unit">
                                <input type="number" name="sms_retry_delay" class="form-control"
                                       min="10" max="3600"
                                       value="<?php echo (int)$s['sms_retry_delay']; ?>">
                                <span class="unit">ثانیه</span>
                            </div>
                        </div>
                    </div>

                    <hr class="section-divider">

                    <!-- ── SECTION 3: Defaults ── -->
                    <h6 style="font-weight:700;color:var(--dark);margin-bottom:18px;display:flex;align-items:center;gap:8px;">
                        <?php echo $icons['settings']; ?> پیش‌فرض‌ها
                    </h6>

                    <div class="form-group">
                        <label class="form-label">امضای پیش‌فرض پیام</label>
                        <input type="text" name="sms_default_signature" class="form-control"
                               placeholder="مثال: — گروه انتخاب"
                               maxlength="50"
                               value="<?php echo htmlspecialchars($s['sms_default_signature']); ?>">
                        <p class="form-hint"><?php echo $icons['info']; ?> در صورت تنظیم، به انتهای تمام پیام‌ها اضافه می‌شود</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label">هزینه هر پیامک</label>
                        <div class="input-with-unit">
                            <input type="number" name="sms_cost_per_sms" class="form-control"
                                   min="0" step="0.01"
                                   value="<?php echo (float)$s['sms_cost_per_sms']; ?>">
                            <span class="unit"><?php echo CURRENCY; ?></span>
                        </div>
                        <p class="form-hint"><?php echo $icons['info']; ?> برای محاسبه هزینه کمپین‌ها استفاده می‌شود</p>
                    </div>

                    <hr class="section-divider">

                    <!-- ── SECTION 4: Toggles ── -->
                    <div class="toggle-row">
                        <div class="toggle-info">
                            <h6>فعال‌سازی ماژول پیامک</h6>
                            <p>امکان ارسال و دریافت پیامک در کل سیستم</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="sms_enabled" <?php echo $s['sms_enabled'] == '1' ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="toggle-row">
                        <div class="toggle-info">
                            <h6>ثبت لاگ ارسال‌ها</h6>
                            <p>ذخیره تاریخچه کامل پیام‌های ارسال‌شده</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="sms_log_enabled" <?php echo $s['sms_log_enabled'] == '1' ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <hr class="section-divider">

                    <div style="display:flex;gap:12px;flex-wrap:wrap;">
                        <button type="submit" class="btn-brand">
                            <?php echo $icons['save']; ?> ذخیره تنظیمات
                        </button>
                        <a href="campaigns.php" class="btn-outline">
                            بازگشت به کمپین‌ها
                        </a>
                    </div>

                </form>
            </div>
        </div>

        <!-- ── TEST CONNECTION ── -->
        <div class="settings-card">
            <div class="card-header">
                <?php echo $icons['send']; ?>
                <h5>تست اتصال به API</h5>
            </div>
            <div class="card-body">
                <div class="alert-box alert-info" style="margin-bottom:20px;">
                    <?php echo $icons['info']; ?>
                    <small>یک پیامک آزمایشی به شماره مشخص‌شده ارسال خواهد شد. از موجودی حساب MessageWay شما کسر می‌گردد.</small>
                </div>

                <form method="POST" id="formTest">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="test">

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                        <div>
                            <label class="form-label">کلید API برای تست <span class="req">*</span></label>
                            <input type="password" name="test_api_key" class="form-control mono"
                                   placeholder="کلید API را وارد کنید"
                                   value="<?php echo !empty($s['sms_api_key']) ? '••••' : ''; ?>">
                        </div>
                        <div>
                            <label class="form-label">شماره فرستنده <span class="req">*</span></label>
                            <input type="text" name="test_sender" class="form-control"
                                   placeholder="30007100"
                                   value="<?php echo htmlspecialchars($s['sms_sender_number']); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">شماره موبایل آزمایشی <span class="req">*</span></label>
                        <input type="text" name="test_phone" class="form-control"
                               placeholder="09xxxxxxxxx" maxlength="11">
                    </div>

                    <button type="submit" class="btn-outline">
                        <?php echo $icons['send']; ?> ارسال پیام آزمایشی
                    </button>
                </form>
            </div>
        </div>

    </div>

    <!-- ══════════ RIGHT COLUMN: SIDEBAR ══════════ -->
    <div>

        <!-- ── Connection Info ── -->
        <div class="sidebar-card">
            <div class="card-header">
                <?php echo $icons['activity']; ?>
                <h5>وضعیت اتصال</h5>
            </div>
            <div class="card-body">
                <div class="info-row">
                    <span class="label">وضعیت ماژول</span>
                    <span class="value">
                        <span class="status-badge <?php echo $s['sms_enabled'] == '1' ? 'badge-on' : 'badge-off'; ?>">
                            <span class="badge-dot"></span>
                            <?php echo $s['sms_enabled'] == '1' ? 'فعال' : 'غیرفعال'; ?>
                        </span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="label">شماره فرستنده</span>
                    <span class="value" style="font-family:monospace">
                        <?php echo !empty($s['sms_sender_number']) ? htmlspecialchars($s['sms_sender_number']) : '—'; ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="label">کلید API</span>
                    <span class="value">
                        <?php echo !empty($s['sms_api_key']) ? '<span style="color:#059669">✓ تنظیم شده</span>' : '<span style="color:#dc2626">✗ تنظیم نشده</span>'; ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="label">حداکثر روزانه</span>
                    <span class="value"><?php echo number_format((int)$s['sms_daily_limit']); ?> پیام</span>
                </div>
                <div class="info-row">
                    <span class="label">نرخ ارسال</span>
                    <span class="value"><?php echo (int)$s['sms_rate_per_second']; ?> پیام/ثانیه</span>
                </div>
                <div class="info-row">
                    <span class="label">هزینه هر پیامک</span>
                    <span class="value"><?php echo formatMoney($s['sms_cost_per_sms']); ?></span>
                </div>
            </div>
        </div>

        <!-- ── Webhook ── -->
        <div class="sidebar-card">
            <div class="card-header">
                <?php echo $icons['link']; ?>
                <h5>Webhook Delivery Report</h5>
            </div>
            <div class="card-body">
                <p style="font-size:.85rem;color:var(--text-gray);margin-bottom:14px;">
                    آدرس زیر را در پنل MessageWay به‌عنوان Webhook ثبت کنید تا گزارش تحویل پیام‌ها دریافت شود.
                </p>
                <div class="webhook-url" id="webhookUrl">
                    <?php echo rtrim(BASE_URL, '/'); ?>/public/sms/webhook.php
                </div>

                <div class="form-group" style="margin-bottom:14px;">
                    <label class="form-label">Webhook Secret</label>
                    <div class="input-group">
                        <input type="text" id="webhookSecret" class="form-control mono"
                               value="<?php echo htmlspecialchars($s['sms_webhook_secret'] ?? ''); ?>"
                               readonly>
                        <button type="button" class="ig-btn" onclick="copyToClipboard('webhookSecret')" title="کپی">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <form method="POST" style="margin-top:4px;">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="regen_webhook">
                    <button type="submit" class="btn-danger" style="width:100%;justify-content:center;"
                            onclick="return confirm('Secret جدید ساخته می‌شود. باید پنل MessageWay را هم به‌روزرسانی کنید. ادامه?')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
                            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                        </svg>
                        ساخت Secret جدید
                    </button>
                </form>
            </div>
        </div>

        <!-- ── Quick Links ── -->
        <div class="sidebar-card">
            <div class="card-header">
                <?php echo $icons['link']; ?>
                <h5>دسترسی سریع</h5>
            </div>
            <div class="card-body" style="padding:12px;">
                <?php
                $links = [
                    ['href' => 'campaigns.php',         'icon' => $icons['send'],     'label' => 'مدیریت کمپین‌ها'],
                    ['href' => 'templates.php',          'icon' => $icons['settings'], 'label' => 'الگوهای پیامکی'],
                    ['href' => 'activity_logs.php',      'icon' => $icons['activity'], 'label' => 'لاگ فعالیت‌ها'],
                ];
                foreach ($links as $link): ?>
                <a href="<?php echo $link['href']; ?>" style="display:flex;align-items:center;gap:10px;padding:11px 10px;border-radius:10px;color:var(--text-gray);text-decoration:none;font-size:.9rem;font-weight:600;transition:var(--tr);"
                   onmouseover="this.style.background='var(--brand-light)';this.style.color='var(--brand)'"
                   onmouseout="this.style.background='';this.style.color='var(--text-gray)'">
                    <?php echo $link['icon']; ?>
                    <?php echo $link['label']; ?>
                    <svg style="margin-right:auto;opacity:.4" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ── Docs Note ── -->
        <div class="alert-box alert-warning" style="margin:0;">
            <?php echo $icons['alert']; ?>
            <div style="font-size:.85rem;">
                <strong>مستندات MessageWay</strong><br>
                برای راهنمای کامل API و تنظیم Webhook به
                <a href="https://docs.msgway.com" target="_blank" style="color:inherit;font-weight:700;">docs.msgway.com</a>
                مراجعه کنید.
            </div>
        </div>

    </div>

</div><!-- end settings-grid -->

<!-- ─── SCRIPTS ───────────────────────────────────────────────────────────── -->
<script>
    // ─── Toggle API Key Visibility ───────────────────────────────────────────
    const apiKeyInput  = document.getElementById('apiKeyInput');
    const toggleBtn    = document.getElementById('toggleApiKey');
    const eyeIcon      = document.getElementById('eyeIcon');
    let   isVisible    = false;

    const iconEye    = `<?php echo addslashes($icons['eye']); ?>`;
    const iconEyeOff = `<?php echo addslashes($icons['eye_off']); ?>`;

    toggleBtn.addEventListener('click', () => {
        isVisible = !isVisible;
        apiKeyInput.type = isVisible ? 'text' : 'password';
        eyeIcon.innerHTML = isVisible ? iconEyeOff : iconEye;
    });

    // ─── Copy to Clipboard ───────────────────────────────────────────────────
    function copyToClipboard(inputId) {
        const el = document.getElementById(inputId);
        if (!el || !el.value) return;
        navigator.clipboard.writeText(el.value).then(() => {
            const toast = document.createElement('div');
            toast.textContent = 'کپی شد ✓';
            toast.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#1e293b;color:white;padding:10px 22px;border-radius:24px;font-size:.875rem;font-weight:600;z-index:9999;';
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 2000);
        });
    }

    // ─── Test Form Validation ────────────────────────────────────────────────
    document.getElementById('formTest').addEventListener('submit', function(e) {
        const phone = this.querySelector('[name="test_phone"]').value.trim();
        if (!/^09\d{9}$/.test(phone)) {
            e.preventDefault();
            alert('شماره موبایل باید با 09 شروع شود و 11 رقم باشد');
        }
    });

    // ─── Daily Limit / Rate Live Preview ────────────────────────────────────
    function updateRateInfo() {
        const rate  = parseInt(document.querySelector('[name="sms_rate_per_second"]').value) || 0;
        const daily = parseInt(document.querySelector('[name="sms_daily_limit"]').value) || 0;
        // Accessible in console for debugging
        console.debug('SMS Rate Config — Per second:', rate, '| Daily cap:', daily);
    }

    document.querySelectorAll('[name="sms_rate_per_second"],[name="sms_daily_limit"]').forEach(el => {
        el.addEventListener('input', updateRateInfo);
    });
</script>

<?php include __DIR__ . '/../private/footer.php'; ?>
