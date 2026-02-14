<?php
/**
 * ══════════════════════════════════════════════════════════════════════════════
 * ReadyCRM V3.6 - SMS CAMPAIGN VIEW (DETAIL PAGE)
 * ══════════════════════════════════════════════════════════════════════════════
 * صفحه جزئیات کمپین پیامکی با قابلیت‌های:
 * - نمایش اطلاعات کامل کمپین
 * - آمار و وضعیت ارسال
 * - لاگ پیام‌های ارسال‌شده
 * - مدیریت وضعیت کمپین (شروع، توقف، حذف)
 * - Timeline فعالیت‌ها
 *
 * @version 3.6.0
 * @author ReadyCRM Team
 * @package ReadyCRM\SMS
 * ══════════════════════════════════════════════════════════════════════════════
 */

$campaign_id = (int)($_GET['id'] ?? 0);

if (!$campaign_id) {
    header('Location: campaigns.php');
    exit();
}

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/database.php';
require_once __DIR__ . '/../private/auth.php';
require_once __DIR__ . '/../private/functions.php';
require_once __DIR__ . '/../private/sms/SmsCampaignService.php';
require_once __DIR__ . '/../private/sms/SmsLogger.php';

// ─── AUTH CHECK ─────────────────────────────────────────────────────────────
requireLogin();
if (!hasPermission('view_sms_campaigns')) {
    setMessage('شما دسترسی لازم برای مشاهده این صفحه را ندارید', 'error');
    header('Location: campaigns.php');
    exit();
}

// ─── SVG ICONS ──────────────────────────────────────────────────────────────
$icons = [
    'arrow-right' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>',
    'edit' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>',
    'send' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>',
    'pause' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>',
    'trash' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>',
    'users' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
    'message' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>',
    'check-circle' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
    'x-circle' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
    'clock' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>',
    'calendar' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
    'activity' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>'
];

// ─── FETCH CAMPAIGN DATA ────────────────────────────────────────────────────
try {
    $stmt = $pdo->prepare("
        SELECT c.*, 
               t.name as template_name,
               u.first_name, u.last_name
        FROM sms_campaigns c
        LEFT JOIN sms_templates t ON c.template_id = t.id
        LEFT JOIN users u ON c.created_by = u.id
        WHERE c.id = ?
    ");
    $stmt->execute([$campaign_id]);
    $campaign = $stmt->fetch();

    if (!$campaign) {
        setMessage('کمپین یافت نشد', 'error');
        header('Location: campaigns.php');
        exit();
    }

    // Fetch Statistics
    $logger = new SmsLogger($pdo);
    $stats = $logger->getCampaignStats($campaign_id);

    // Fetch Recent Logs (20 latest)
    $logs_stmt = $pdo->prepare("
        SELECT * FROM sms_logs 
        WHERE campaign_id = ? 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $logs_stmt->execute([$campaign_id]);
    $logs = $logs_stmt->fetchAll();

} catch (PDOException $e) {
    setMessage('خطا در بارگذاری اطلاعات کمپین: ' . $e->getMessage(), 'error');
    error_log("Campaign view error: " . $e->getMessage());
    header('Location: campaigns.php');
    exit();
}

// ─── HANDLE ACTIONS ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($csrf_token)) {
        setMessage('توکن امنیتی نامعتبر است', 'error');
        header("Location: campaign_view.php?id=$campaign_id");
        exit();
    }

    try {
        $campaignService = new SmsCampaignService($pdo, $_SESSION['user_id']);

        switch ($action) {
            case 'start':
                if ($campaign['status'] === 'draft' || $campaign['status'] === 'scheduled') {
                    $result = $campaignService->executeCampaign($campaign_id);
                    if ($result['success']) {
                        setMessage('کمپین با موفقیت آغاز شد', 'success');
                        logActivity($_SESSION['user_id'], 'start_sms_campaign', 'sms_campaigns', $campaign_id);
                    } else {
                        setMessage('خطا: ' . $result['message'], 'error');
                    }
                } else {
                    setMessage('این کمپین قابل اجرا نیست', 'warning');
                }
                break;

            case 'pause':
                $pdo->prepare("UPDATE sms_campaigns SET status = 'paused' WHERE id = ?")
                    ->execute([$campaign_id]);
                setMessage('کمپین متوقف شد', 'success');
                logActivity($_SESSION['user_id'], 'pause_sms_campaign', 'sms_campaigns', $campaign_id);
                break;

            case 'delete':
                if (hasPermission('delete_sms_campaigns')) {
                    $pdo->prepare("UPDATE sms_campaigns SET status = 'deleted' WHERE id = ?")
                        ->execute([$campaign_id]);
                    setMessage('کمپین حذف شد', 'success');
                    logActivity($_SESSION['user_id'], 'delete_sms_campaign', 'sms_campaigns', $campaign_id);
                    header('Location: campaigns.php');
                    exit();
                } else {
                    setMessage('شما دسترسی حذف ندارید', 'error');
                }
                break;

            default:
                setMessage('عملیات نامعتبر', 'error');
        }

        header("Location: campaign_view.php?id=$campaign_id");
        exit();

    } catch (Exception $e) {
        setMessage('خطا در انجام عملیات: ' . $e->getMessage(), 'error');
        error_log("Campaign action error: " . $e->getMessage());
        header("Location: campaign_view.php?id=$campaign_id");
        exit();
    }
}

// ─── STATUS BADGE ───────────────────────────────────────────────────────────
function getStatusBadge($status) {
    $badges = [
        'draft' => '<span class="badge-status draft">پیش‌نویس</span>',
        'scheduled' => '<span class="badge-status scheduled">برنامه‌ریزی‌شده</span>',
        'active' => '<span class="badge-status active">در حال ارسال</span>',
        'completed' => '<span class="badge-status completed">تکمیل شده</span>',
        'paused' => '<span class="badge-status paused">متوقف</span>',
        'failed' => '<span class="badge-status failed">خطا</span>',
        'deleted' => '<span class="badge-status deleted">حذف شده</span>'
    ];
    return $badges[$status] ?? '<span class="badge-status">نامشخص</span>';
}

$page_title = 'جزئیات کمپین: ' . $campaign['name'];
$breadcrumb = [
    ['title' => 'داشبورد', 'url' => 'dashboard.php'],
    ['title' => 'کمپین‌های پیامکی', 'url' => 'campaigns.php'],
    ['title' => $campaign['name']]
];

$csrf_token = generateCSRFToken();
include __DIR__ . '/../private/header.php';
?>

<!-- ─── STYLES ────────────────────────────────────────────────────────────── -->
<style>
    :root {
        --brand: #00b0a4;
        --brand-hover: #00968c;
        --brand-soft: #e0f2f1;
        --dark: #010101;
        --gray-border: #e2e8f0;
        --gray-text: #64748b;
        --bg-light: #f8fafc;
        --radius-card: 16px;
        --radius-elem: 12px;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --info: #3b82f6;
    }

    body {
        background-color: var(--bg-light);
        color: var(--dark);
    }

    /* Cards */
    .view-card {
        background: white;
        border: 1px solid var(--gray-border);
        border-radius: var(--radius-card);
        padding: 24px;
        margin-bottom: 20px;
    }

    .card-header-custom {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 16px;
        border-bottom: 1px solid var(--gray-border);
        margin-bottom: 20px;
    }

    .card-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Status Badges */
    .badge-status {
        display: inline-flex;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .badge-status.draft { background: #f3f4f6; color: #6b7280; }
    .badge-status.scheduled { background: #dbeafe; color: #1e40af; }
    .badge-status.active { background: #d1fae5; color: #065f46; }
    .badge-status.completed { background: #dcfce7; color: #166534; }
    .badge-status.paused { background: #fef3c7; color: #92400e; }
    .badge-status.failed { background: #fee2e2; color: #991b1b; }
    .badge-status.deleted { background: #f1f5f9; color: #475569; }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }

    .stat-box {
        background: linear-gradient(135deg, var(--brand-soft) 0%, white 100%);
        border: 1px solid var(--gray-border);
        border-radius: var(--radius-elem);
        padding: 20px;
        text-align: center;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 800;
        color: var(--brand);
        line-height: 1;
    }

    .stat-label {
        font-size: 0.875rem;
        color: var(--gray-text);
        margin-top: 8px;
    }

    .stat-box.success .stat-value { color: var(--success); }
    .stat-box.warning .stat-value { color: var(--warning); }
    .stat-box.danger .stat-value { color: var(--danger); }

    /* Info Row */
    .info-row {
        display: flex;
        padding: 12px 0;
        border-bottom: 1px solid var(--gray-border);
    }

    .info-row:last-child { border-bottom: none; }

    .info-label {
        font-weight: 600;
        color: var(--gray-text);
        width: 150px;
        flex-shrink: 0;
    }

    .info-value {
        color: var(--dark);
        flex: 1;
    }

    /* Message Preview */
    .message-preview-box {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: var(--radius-elem);
        padding: 20px;
        color: white;
        white-space: pre-wrap;
        line-height: 1.8;
        font-size: 0.95rem;
    }

    /* Logs Table */
    .logs-table {
        width: 100%;
        border-collapse: collapse;
    }

    .logs-table th {
        background: var(--bg-light);
        padding: 12px;
        text-align: right;
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--gray-text);
        border-bottom: 2px solid var(--gray-border);
    }

    .logs-table td {
        padding: 12px;
        border-bottom: 1px solid var(--gray-border);
        font-size: 0.9rem;
    }

    .logs-table tr:hover {
        background: var(--bg-light);
    }

    .log-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .log-status.success {
        background: #dcfce7;
        color: #166534;
    }

    .log-status.failed {
        background: #fee2e2;
        color: #991b1b;
    }

    .log-status.pending {
        background: #fef3c7;
        color: #92400e;
    }

    /* Buttons */
    .btn-action {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: var(--radius-elem);
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: 0.2s;
        border: none;
        text-decoration: none;
    }

    .btn-primary {
        background: var(--brand);
        color: white;
    }
    .btn-primary:hover {
        background: var(--brand-hover);
        transform: translateY(-1px);
    }

    .btn-outline {
        background: white;
        border: 1px solid var(--gray-border);
        color: var(--gray-text);
    }
    .btn-outline:hover {
        border-color: var(--dark);
        color: var(--dark);
    }

    .btn-danger {
        background: var(--danger);
        color: white;
    }
    .btn-danger:hover {
        background: #dc2626;
    }

    .btn-warning {
        background: var(--warning);
        color: white;
    }
    .btn-warning:hover {
        background: #d97706;
    }

    /* Timeline */
    .timeline {
        position: relative;
        padding-right: 30px;
    }

    .timeline::before {
        content: '';
        position: absolute;
        right: 4px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: var(--gray-border);
    }

    .timeline-item {
        position: relative;
        padding-bottom: 24px;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        right: -26px;
        top: 4px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: var(--brand);
        border: 2px solid white;
        box-shadow: 0 0 0 2px var(--brand);
    }

    .timeline-content {
        background: var(--bg-light);
        padding: 12px;
        border-radius: var(--radius-elem);
    }

    .timeline-time {
        font-size: 0.8rem;
        color: var(--gray-text);
        margin-top: 4px;
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- ─── PAGE HEADER ───────────────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="campaigns.php" class="btn-outline" style="padding: 10px;">
            <?php echo $icons['arrow-right']; ?>
        </a>
        <div>
            <h4 class="mb-0 fw-bold" style="color: var(--dark);">
                <?php echo htmlspecialchars($campaign['name']); ?>
            </h4>
            <div class="text-muted small">
                کمپین #<?php echo $campaign['id']; ?> •
                ایجاد شده توسط <?php echo htmlspecialchars($campaign['first_name'] . ' ' . $campaign['last_name']); ?>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <?php if ($campaign['status'] === 'draft' || $campaign['status'] === 'scheduled'): ?>
        <a href="campaign_form.php?id=<?php echo $campaign_id; ?>" class="btn-action btn-outline">
            <?php echo $icons['edit']; ?> ویرایش
        </a>
        <?php endif; ?>

        <?php if (hasPermission('delete_sms_campaigns')): ?>
        <form method="POST" style="display: inline;" onsubmit="return confirm('آیا مطمئن هستید؟');">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" value="delete">
            <button type="submit" class="btn-action btn-danger">
                <?php echo $icons['trash']; ?> حذف
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php echo displayMessage(); ?>

<!-- ─── STATS GRID ────────────────────────────────────────────────────────── -->
<div class="stats-grid">
    <div class="stat-box">
        <div class="stat-value"><?php echo number_format($stats['total_recipients']); ?></div>
        <div class="stat-label">کل مخاطبان</div>
    </div>

    <div class="stat-box success">
        <div class="stat-value"><?php echo number_format($stats['sent_count']); ?></div>
        <div class="stat-label">ارسال شده</div>
    </div>

    <div class="stat-box danger">
        <div class="stat-value"><?php echo number_format($stats['failed_count']); ?></div>
        <div class="stat-label">ناموفق</div>
    </div>

    <div class="stat-box warning">
        <div class="stat-value"><?php echo number_format($stats['pending_count']); ?></div>
        <div class="stat-label">در انتظار</div>
    </div>
</div>

<!-- ─── MAIN INFO ─────────────────────────────────────────────────────────── -->
<div class="row">
    <div class="col-lg-8">
        <!-- Campaign Details -->
        <div class="view-card">
            <div class="card-header-custom">
                <div class="card-title">
                    <?php echo $icons['activity']; ?> جزئیات کمپین
                </div>
                <?php echo getStatusBadge($campaign['status']); ?>
            </div>

            <div class="info-row">
                <div class="info-label">نام کمپین:</div>
                <div class="info-value"><?php echo htmlspecialchars($campaign['name']); ?></div>
            </div>

            <div class="info-row">
                <div class="info-label">الگو:</div>
                <div class="info-value">
                    <?php echo $campaign['template_name'] ? htmlspecialchars($campaign['template_name']) : '—'; ?>
                </div>
            </div>

            <div class="info-row">
                <div class="info-label">نوع مخاطب:</div>
                <div class="info-value">
                    <?php
                    $types = [
                        'all_customers' => 'تمام مشتریان',
                        'customers' => 'مشتریان منتخب',
                        'all_leads' => 'تمام لیدها',
                        'leads' => 'لیدهای منتخب',
                        'manual' => 'دستی',
                        'csv' => 'فایل CSV'
                    ];
                    echo $types[$campaign['recipient_type']] ?? 'نامشخص';
                    ?>
                </div>
            </div>

            <div class="info-row">
                <div class="info-label">زمان ارسال:</div>
                <div class="info-value">
                    <?php
                    if ($campaign['scheduled_at']) {
                        echo formatPersianDate($campaign['scheduled_at'], 'Y/m/d - H:i');
                    } else {
                        echo 'بلافاصله';
                    }
                    ?>
                </div>
            </div>

            <div class="info-row">
                <div class="info-label">تاریخ ایجاد:</div>
                <div class="info-value"><?php echo formatPersianDate($campaign['created_at'], 'Y/m/d - H:i'); ?></div>
            </div>

            <?php if ($campaign['executed_at']): ?>
            <div class="info-row">
                <div class="info-label">تاریخ اجرا:</div>
                <div class="info-value"><?php echo formatPersianDate($campaign['executed_at'], 'Y/m/d - H:i'); ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Message Preview -->
        <div class="view-card">
            <div class="card-header-custom">
                <div class="card-title">
                    <?php echo $icons['message']; ?> متن پیام
                </div>
            </div>

            <div class="message-preview-box">
                <?php echo nl2br(htmlspecialchars($campaign['message_text'])); ?>
            </div>

            <div class="mt-3 text-muted small">
                طول پیام: <?php echo mb_strlen($campaign['message_text'], 'UTF-8'); ?> کاراکتر
                • تعداد پیام: <?php echo ceil(mb_strlen($campaign['message_text'], 'UTF-8') / 70); ?>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="view-card">
            <div class="card-header-custom">
                <div class="card-title">
                    <?php echo $icons['clock']; ?> لاگ ارسال (<?php echo count($logs); ?> اخیر)
                </div>
            </div>

            <?php if (count($logs) > 0): ?>
            <div style="overflow-x: auto;">
                <table class="logs-table">
                    <thead>
                        <tr>
                            <th>شماره موبایل</th>
                            <th>وضعیت</th>
                            <th>کد پیگیری</th>
                            <th>زمان ارسال</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['phone_number']); ?></td>
                            <td>
                                <?php
                                $status_class = ($log['status'] === 'sent') ? 'success' : (($log['status'] === 'failed') ? 'failed' : 'pending');
                                $status_icon = ($log['status'] === 'sent') ? $icons['check-circle'] : (($log['status'] === 'failed') ? $icons['x-circle'] : $icons['clock']);
                                ?>
                                <span class="log-status <?php echo $status_class; ?>">
                                    <?php echo $status_icon; ?>
                                    <?php
                                    $statuses = ['sent' => 'ارسال شد', 'failed' => 'ناموفق', 'pending' => 'در انتظار'];
                                    echo $statuses[$log['status']] ?? 'نامشخص';
                                    ?>
                                </span>
                            </td>
                            <td>
                                <code style="font-size: 0.85rem;"><?php echo $log['msgway_id'] ?: '—'; ?></code>
                            </td>
                            <td><?php echo formatPersianDate($log['created_at'], 'Y/m/d H:i'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center text-muted py-5">
                هنوز پیامی ارسال نشده است
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Actions -->
        <div class="view-card">
            <div class="card-header-custom">
                <div class="card-title">عملیات</div>
            </div>

            <div class="d-flex flex-column gap-2">
                <?php if ($campaign['status'] === 'draft' || $campaign['status'] === 'scheduled'): ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="start">
                    <button type="submit" class="btn-action btn-primary w-100">
                        <?php echo $icons['send']; ?> شروع ارسال
                    </button>
                </form>
                <?php endif; ?>

                <?php if ($campaign['status'] === 'active'): ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="pause">
                    <button type="submit" class="btn-action btn-warning w-100">
                        <?php echo $icons['pause']; ?> توقف کمپین
                    </button>
                </form>
                <?php endif; ?>

                <a href="campaigns.php" class="btn-action btn-outline w-100">
                    بازگشت به لیست
                </a>
            </div>
        </div>

        <!-- Progress -->
        <div class="view-card">
            <div class="card-header-custom">
                <div class="card-title">پیشرفت ارسال</div>
            </div>

            <?php
            $total = $stats['total_recipients'];
            $completed = $stats['sent_count'] + $stats['failed_count'];
            $progress = $total > 0 ? round(($completed / $total) * 100) : 0;
            ?>

            <div style="background: var(--bg-light); height: 24px; border-radius: 12px; overflow: hidden;">
                <div style="
                    width: <?php echo $progress; ?>%;
                    height: 100%;
                    background: linear-gradient(90deg, var(--brand) 0%, var(--success) 100%);
                    transition: width 0.3s;
                "></div>
            </div>

            <div class="text-center mt-3">
                <strong style="font-size: 1.5rem; color: var(--brand);"><?php echo $progress; ?>%</strong>
                <div class="text-muted small mt-1">
                    <?php echo number_format($completed); ?> از <?php echo number_format($total); ?>
                </div>
            </div>
        </div>

        <!-- Timeline -->
        <?php
        $timeline_events = [];

        if ($campaign['created_at']) {
            $timeline_events[] = [
                'title' => 'کمپین ایجاد شد',
                'time' => $campaign['created_at']
            ];
        }

        if ($campaign['executed_at']) {
            $timeline_events[] = [
                'title' => 'ارسال آغاز شد',
                'time' => $campaign['executed_at']
            ];
        }

        if ($campaign['completed_at']) {
            $timeline_events[] = [
                'title' => 'ارسال تکمیل شد',
                'time' => $campaign['completed_at']
            ];
        }

        if (!empty($timeline_events)):
        ?>
        <div class="view-card">
            <div class="card-header-custom">
                <div class="card-title">
                    <?php echo $icons['calendar']; ?> تاریخچه
                </div>
            </div>

            <div class="timeline">
                <?php foreach ($timeline_events as $event): ?>
                <div class="timeline-item">
                    <div class="timeline-content">
                        <strong><?php echo $event['title']; ?></strong>
                        <div class="timeline-time">
                            <?php echo formatPersianDate($event['time'], 'Y/m/d - H:i'); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../private/footer.php'; ?>
