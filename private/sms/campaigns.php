<?php
/**
 * ══════════════════════════════════════════════════════════════════════════════
 * ReadyCRM V3.6 - SMS CAMPAIGNS DASHBOARD
 * ══════════════════════════════════════════════════════════════════════════════
 * داشبورد پیشرفته مدیریت کمپین‌های پیامکی با قابلیت‌های:
 * - نمایش آمار (کل کمپین‌ها، ارسال‌شده، هزینه، نرخ موفقیت)
 * - فیلتر پیشرفته (وضعیت، تاریخ، جستجو)
 * - سوییچ View (Grid / List)
 * - عملیات (شروع، متوقف، حذف)
 *
 * @version 3.6.0
 * @author ReadyCRM Team
 * @package ReadyCRM\SMS
 * ══════════════════════════════════════════════════════════════════════════════
 */

$page_title = 'مدیریت کمپین‌های پیامکی';
$breadcrumb = [
    ['title' => 'داشبورد', 'url' => 'dashboard.php'],
    ['title' => 'کمپین‌های پیامکی']
];

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/database.php';
require_once __DIR__ . '/../private/auth.php';
require_once __DIR__ . '/../private/functions.php';
require_once __DIR__ . '/../private/sms/SmsCampaignService.php';

// ─── AUTH CHECK ─────────────────────────────────────────────────────────────
requireLogin();
if (!hasPermission('view_sms_campaigns')) {
    setMessage('شما دسترسی لازم برای مشاهده این صفحه را ندارید', 'error');
    header('Location: dashboard.php');
    exit();
}

// ─── SVG ICONS REPOSITORY ───────────────────────────────────────────────────
$icons = [
    'plus' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>',
    'grid' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>',
    'list' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>',
    'search' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>',
    'message' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>',
    'send' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>',
    'dollar' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>',
    'check-circle' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
    'clock' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>',
    'calendar' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
    'users' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
    'more' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>',
    'trash' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>',
    'edit' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>',
    'eye' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
    'play' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>',
    'pause' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>'
];

// ─── HANDLE ACTIONS ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (verifyCSRFToken($csrf_token)) {
        $campaign_id = (int)($_POST['campaign_id'] ?? 0);

        try {
            $campaignService = new SmsCampaignService($pdo, $_SESSION['user_id']);

            switch ($action) {
                case 'delete':
                    if (hasPermission('delete_sms_campaigns')) {
                        $pdo->prepare("UPDATE sms_campaigns SET status = 'deleted' WHERE id = ?")
                            ->execute([$campaign_id]);
                        logActivity($_SESSION['user_id'], 'delete_sms_campaign', 'sms_campaigns', $campaign_id);
                        setMessage('کمپین با موفقیت حذف شد', 'success');
                    } else {
                        setMessage('شما دسترسی حذف ندارید', 'error');
                    }
                    break;

                case 'start':
                    $result = $campaignService->executeCampaign($campaign_id);
                    if ($result['success']) {
                        setMessage('کمپین با موفقیت آغاز شد', 'success');
                        logActivity($_SESSION['user_id'], 'start_sms_campaign', 'sms_campaigns', $campaign_id);
                    } else {
                        setMessage('خطا: ' . $result['message'], 'error');
                    }
                    break;

                case 'pause':
                    $pdo->prepare("UPDATE sms_campaigns SET status = 'paused' WHERE id = ?")
                        ->execute([$campaign_id]);
                    setMessage('کمپین متوقف شد', 'success');
                    logActivity($_SESSION['user_id'], 'pause_sms_campaign', 'sms_campaigns', $campaign_id);
                    break;

                default:
                    setMessage('عملیات نامعتبر', 'error');
            }

            header("Location: campaigns.php");
            exit();

        } catch (Exception $e) {
            setMessage('خطا در انجام عملیات: ' . $e->getMessage(), 'error');
            error_log("Campaign action error: " . $e->getMessage());
            header("Location: campaigns.php");
            exit();
        }
    }
}

// ─── FILTERS & SEARCH ──────────────────────────────────────────────────────
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

$where = ["status != 'deleted'"];
$params = [];

if ($search) {
    $where[] = "(name LIKE ? OR id = ?)";
    $params[] = "%$search%";
    $params[] = $search;
}
if ($status) {
    $where[] = "status = ?";
    $params[] = $status;
}

$where_sql = implode(' AND ', $where);

// Sorting
$order_by = "created_at DESC";
if ($sort === 'sent_count') $order_by = "sent_count DESC";
if ($sort === 'cost') $order_by = "estimated_cost DESC";
if ($sort === 'scheduled') $order_by = "scheduled_at ASC";

// ─── STATS ─────────────────────────────────────────────────────────────────
try {
    $stats = [
        'total' => (int)$pdo->query("SELECT COUNT(*) FROM sms_campaigns WHERE status != 'deleted'")->fetchColumn(),
        'sent' => (int)$pdo->query("SELECT SUM(sent_count) FROM sms_campaigns WHERE status = 'completed'")->fetchColumn(),
        'cost' => (float)$pdo->query("SELECT SUM(estimated_cost) FROM sms_campaigns WHERE status != 'deleted'")->fetchColumn(),
        'active' => (int)$pdo->query("SELECT COUNT(*) FROM sms_campaigns WHERE status = 'active'")->fetchColumn(),
    ];

    // محاسبه نرخ موفقیت
    $total_sent = (int)$pdo->query("SELECT SUM(sent_count) FROM sms_campaigns")->fetchColumn();
    $total_failed = (int)$pdo->query("SELECT SUM(failed_count) FROM sms_campaigns")->fetchColumn();
    $stats['success_rate'] = ($total_sent + $total_failed) > 0 
        ? round(($total_sent / ($total_sent + $total_failed)) * 100, 1) 
        : 0;

} catch (PDOException $e) {
    $stats = ['total' => 0, 'sent' => 0, 'cost' => 0, 'active' => 0, 'success_rate' => 0];
    error_log("Stats query error: " . $e->getMessage());
}

// ─── FETCH CAMPAIGNS ────────────────────────────────────────────────────────
try {
    $sql = "
        SELECT c.*,
               t.name as template_name,
               CONCAT(u.first_name, ' ', u.last_name) as creator_name,
               (SELECT COUNT(*) FROM sms_logs WHERE campaign_id = c.id AND status = 'sent') as sent_count,
               (SELECT COUNT(*) FROM sms_logs WHERE campaign_id = c.id AND status = 'failed') as failed_count
        FROM sms_campaigns c
        LEFT JOIN sms_templates t ON c.template_id = t.id
        LEFT JOIN users u ON c.created_by = u.id
        WHERE $where_sql
        ORDER BY $order_by
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $campaigns = $stmt->fetchAll();

} catch (PDOException $e) {
    $campaigns = [];
    setMessage('خطا در بارگذاری کمپین‌ها: ' . $e->getMessage(), 'error');
    error_log("Campaigns query error: " . $e->getMessage());
}

$csrf_token = generateCSRFToken();
include __DIR__ . '/../private/header.php';
?>

<!-- ─── STYLES ────────────────────────────────────────────────────────────── -->
<style>
    :root {
        --brand: #00b0a4;
        --brand-hover: #00968c;
        --brand-light: #e0f2f1;
        --dark: #1e293b;
        --text-gray: #64748b;
        --bg-body: #f1f5f9;
        --card-radius: 16px;
        --transition: all 0.3s ease;
    }

    body { background-color: var(--bg-body); color: var(--dark); }

    /* ─── Stats Cards ─── */
    .stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 24px;
        margin-bottom: 32px;
    }

    .stat-card {
        background: white;
        border-radius: var(--card-radius);
        padding: 24px;
        position: relative;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        border-color: var(--brand);
    }

    .stat-content h3 { font-size: 1.8rem; font-weight: 800; margin: 0; color: var(--dark); }
    .stat-content p { margin: 5px 0 0; color: var(--text-gray); font-size: 0.9rem; }

    .stat-icon-wrapper {
        width: 60px; height: 60px;
        border-radius: 16px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem;
    }
    .icon-blue { background: #e0f2fe; color: #0284c7; }
    .icon-green { background: #dcfce7; color: #16a34a; }
    .icon-purple { background: #f3e8ff; color: #9333ea; }
    .icon-orange { background: #ffedd5; color: #ea580c; }

    /* ─── Toolbar ─── */
    .toolbar-card {
        background: white;
        border-radius: var(--card-radius);
        padding: 20px;
        border: 1px solid #e2e8f0;
        margin-bottom: 24px;
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: center;
        justify-content: space-between;
    }

    .search-box {
        position: relative;
        flex-grow: 1;
        max-width: 300px;
    }
    .search-box input {
        width: 100%;
        padding: 10px 40px 10px 16px;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        outline: none;
        transition: var(--transition);
    }
    .search-box input:focus { border-color: var(--brand); box-shadow: 0 0 0 3px rgba(0, 176, 164, 0.1); }
    .search-box svg {
        position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
        color: var(--text-gray);
    }

    .filter-group { display: flex; gap: 10px; flex-wrap: wrap; }
    .custom-select {
        padding: 10px 16px;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        background: white;
        cursor: pointer;
        outline: none;
        transition: var(--transition);
    }
    .custom-select:focus { border-color: var(--brand); }

    .view-switcher {
        display: flex;
        background: #f1f5f9;
        padding: 4px;
        border-radius: 12px;
    }
    .view-btn {
        border: none;
        background: transparent;
        padding: 8px 12px;
        border-radius: 8px;
        color: var(--text-gray);
        cursor: pointer;
        transition: var(--transition);
    }
    .view-btn.active { background: white; color: var(--brand); box-shadow: 0 2px 5px rgba(0,0,0,0.05); }

    /* ─── Campaign Grid Cards ─── */
    .campaigns-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 24px;
    }

    .campaign-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: var(--card-radius);
        padding: 24px;
        transition: var(--transition);
        position: relative;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .campaign-card:hover {
        border-color: var(--brand);
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.05);
    }

    .card-header-custom {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 16px;
    }

    .campaign-icon {
        width: 48px; height: 48px;
        border-radius: 12px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem;
    }

    .campaign-title {
        font-size: 1.1rem; font-weight: 700; margin-bottom: 8px;
        color: var(--dark); display: block; text-decoration: none;
    }
    .campaign-title:hover { color: var(--brand); }

    .campaign-meta {
        font-size: 0.85rem; color: var(--text-gray);
        margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap;
    }

    .stats-mini {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin-bottom: 16px;
    }

    .stat-mini {
        text-align: center;
        padding: 12px;
        background: #f8fafc;
        border-radius: 8px;
    }

    .stat-mini-value {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--brand);
    }

    .stat-mini-label {
        font-size: 0.75rem;
        color: var(--text-gray);
        margin-top: 4px;
    }

    .card-footer-custom {
        margin-top: auto;
        padding-top: 16px;
        border-top: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* ─── List View ─── */
    .campaigns-list { display: none; }
    .custom-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .custom-table th {
        text-align: right; padding: 16px; border-bottom: 2px solid #e2e8f0;
        color: var(--text-gray); font-weight: 600; background: #f8fafc;
    }
    .custom-table td { padding: 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; background: white; }
    .custom-table tbody tr:hover td { background: #f8fafc; }

    /* ─── Status Badges ─── */
    .status-badge {
        padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;
        display: inline-flex; align-items: center; gap: 4px;
    }
    .status-draft { background: #f3f4f6; color: #6b7280; }
    .status-scheduled { background: #dbeafe; color: #1e40af; }
    .status-active { background: #d1fae5; color: #065f46; }
    .status-completed { background: #dcfce7; color: #166534; }
    .status-paused { background: #fef3c7; color: #92400e; }
    .status-failed { background: #fee2e2; color: #991b1b; }

    /* ─── Utility ─── */
    .btn-brand {
        background: var(--brand); color: white; padding: 10px 20px; border-radius: 12px;
        border: none; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
        transition: var(--transition); text-decoration: none;
    }
    .btn-brand:hover { background: var(--brand-hover); color: white; }

    .empty-state {
        text-align: center; padding: 60px 20px; color: var(--text-gray);
    }
    .empty-state svg { width: 120px; height: 120px; opacity: 0.3; margin-bottom: 20px; }

    @media (max-width: 768px) {
        .toolbar-card { flex-direction: column; align-items: stretch; }
        .search-box { max-width: 100%; }
        .campaigns-grid { grid-template-columns: 1fr; }
    }
</style>

<!-- ─── FLASH MESSAGES ────────────────────────────────────────────────────── -->
<?php echo displayMessage(); ?>

<!-- ─── STATS SECTION ─────────────────────────────────────────────────────── -->
<div class="stats-container">
    <div class="stat-card">
        <div class="stat-content">
            <h3><?php echo number_format($stats['total']); ?></h3>
            <p>کل کمپین‌ها</p>
        </div>
        <div class="stat-icon-wrapper icon-blue">
            <?php echo $icons['message']; ?>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-content">
            <h3><?php echo number_format($stats['sent']); ?></h3>
            <p>پیام ارسال‌شده</p>
        </div>
        <div class="stat-icon-wrapper icon-green">
            <?php echo $icons['send']; ?>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-content">
            <h3 style="font-size: 1.5rem;"><?php echo formatMoney($stats['cost'], 'تومان'); ?></h3>
            <p>هزینه کل</p>
        </div>
        <div class="stat-icon-wrapper icon-purple">
            <?php echo $icons['dollar']; ?>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-content">
            <h3><?php echo $stats['success_rate']; ?>%</h3>
            <p>نرخ موفقیت</p>
        </div>
        <div class="stat-icon-wrapper icon-orange">
            <?php echo $icons['check-circle']; ?>
        </div>
    </div>
</div>

<!-- ─── TOOLBAR & FILTERS ─────────────────────────────────────────────────── -->
<div class="toolbar-card">
    <form method="GET" class="d-flex flex-grow-1 flex-wrap gap-3 align-items-center w-100">
        <!-- Search -->
        <div class="search-box">
            <?php echo $icons['search']; ?>
            <input type="text" name="search" placeholder="جستجوی نام کمپین یا شناسه..." value="<?php echo htmlspecialchars($search); ?>">
        </div>

        <!-- Filters -->
        <div class="filter-group flex-grow-1">
            <select name="status" class="custom-select" onchange="this.form.submit()">
                <option value="">همه وضعیت‌ها</option>
                <option value="draft" <?php echo $status == 'draft' ? 'selected' : ''; ?>>پیش‌نویس</option>
                <option value="scheduled" <?php echo $status == 'scheduled' ? 'selected' : ''; ?>>برنامه‌ریزی‌شده</option>
                <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>در حال ارسال</option>
                <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>تکمیل شده</option>
                <option value="paused" <?php echo $status == 'paused' ? 'selected' : ''; ?>>متوقف شده</option>
                <option value="failed" <?php echo $status == 'failed' ? 'selected' : ''; ?>>خطا</option>
            </select>

            <select name="sort" class="custom-select" onchange="this.form.submit()">
                <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>جدیدترین</option>
                <option value="sent_count" <?php echo $sort == 'sent_count' ? 'selected' : ''; ?>>بیشترین ارسال</option>
                <option value="cost" <?php echo $sort == 'cost' ? 'selected' : ''; ?>>بیشترین هزینه</option>
                <option value="scheduled" <?php echo $sort == 'scheduled' ? 'selected' : ''; ?>>نزدیکترین زمان</option>
            </select>
        </div>

        <!-- Actions -->
        <div class="d-flex align-items-center gap-3">
            <div class="view-switcher">
                <button type="button" class="view-btn active" id="btnGrid" onclick="setView('grid')"><?php echo $icons['grid']; ?></button>
                <button type="button" class="view-btn" id="btnList" onclick="setView('list')"><?php echo $icons['list']; ?></button>
            </div>

            <?php if (hasPermission('create_sms_campaigns')): ?>
            <a href="campaign_form.php" class="btn-brand">
                <?php echo $icons['plus']; ?> کمپین جدید
            </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- ─── CAMPAIGNS DISPLAY (GRID VIEW) ──────────────────────────────────────── -->
<div id="campaignsGrid" class="campaigns-grid">
    <?php if (empty($campaigns)): ?>
        <div class="col-12">
            <div class="empty-state">
                <?php echo $icons['message']; ?>
                <h5>هیچ کمپینی یافت نشد</h5>
                <p class="text-muted">برای شروع، یک کمپین جدید ایجاد کنید.</p>
                <?php if (hasPermission('create_sms_campaigns')): ?>
                <a href="campaign_form.php" class="btn-brand mt-3">
                    <?php echo $icons['plus']; ?> ایجاد اولین کمپین
                </a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($campaigns as $c): ?>
        <div class="campaign-card">
            <div class="card-header-custom">
                <div class="d-flex gap-3 align-items-center">
                    <div class="campaign-icon">
                        <?php echo $icons['message']; ?>
                    </div>
                    <div>
                        <span class="status-badge status-<?php echo $c['status']; ?> mb-1 d-inline-block">
                            <?php
                            $statuses = [
                                'draft' => 'پیش‌نویس',
                                'scheduled' => 'برنامه‌ریزی‌شده',
                                'active' => 'در حال ارسال',
                                'completed' => 'تکمیل شده',
                                'paused' => 'متوقف',
                                'failed' => 'خطا'
                            ];
                            echo $statuses[$c['status']] ?? 'نامشخص';
                            ?>
                        </span>
                        <div class="text-muted small" style="font-size: 0.75rem;">#{<?php echo $c['id']; ?>}</div>
                    </div>
                </div>

                <div class="dropdown">
                    <button class="btn btn-link text-muted p-0" data-bs-toggle="dropdown">
                        <?php echo $icons['more']; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-3">
                        <li><a class="dropdown-item" href="campaign_view.php?id=<?php echo $c['id']; ?>"><?php echo $icons['eye']; ?> مشاهده</a></li>
                        
                        <?php if ($c['status'] === 'draft' || $c['status'] === 'scheduled'): ?>
                        <li><a class="dropdown-item" href="campaign_form.php?id=<?php echo $c['id']; ?>"><?php echo $icons['edit']; ?> ویرایش</a></li>
                        <?php endif; ?>

                        <?php if ($c['status'] === 'draft' || $c['status'] === 'scheduled'): ?>
                        <li>
                            <a class="dropdown-item" href="#" onclick="startCampaign(<?php echo $c['id']; ?>); return false;">
                                <?php echo $icons['play']; ?> شروع ارسال
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if ($c['status'] === 'active'): ?>
                        <li>
                            <a class="dropdown-item" href="#" onclick="pauseCampaign(<?php echo $c['id']; ?>); return false;">
                                <?php echo $icons['pause']; ?> توقف
                            </a>
                        </li>
                        <?php endif; ?>

                        <li><hr class="dropdown-divider"></li>
                        
                        <?php if (hasPermission('delete_sms_campaigns')): ?>
                        <li>
                            <a class="dropdown-item text-danger" href="#" onclick="confirmDelete(<?php echo $c['id']; ?>); return false;">
                                <?php echo $icons['trash']; ?> حذف
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <a href="campaign_view.php?id=<?php echo $c['id']; ?>" class="campaign-title">
                <?php echo htmlspecialchars($c['name']); ?>
            </a>

            <div class="campaign-meta">
                <?php if($c['template_name']): ?>
                    <span><i class="fas fa-file-alt ms-1"></i> <?php echo htmlspecialchars($c['template_name']); ?></span>
                <?php endif; ?>
                <span><i class="fas fa-user ms-1"></i> <?php echo htmlspecialchars($c['creator_name']); ?></span>
            </div>

            <div class="stats-mini">
                <div class="stat-mini">
                    <div class="stat-mini-value"><?php echo number_format($c['total_recipients']); ?></div>
                    <div class="stat-mini-label">مخاطب</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-value"><?php echo number_format($c['sent_count']); ?></div>
                    <div class="stat-mini-label">ارسال‌شده</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-value"><?php echo number_format($c['failed_count']); ?></div>
                    <div class="stat-mini-label">ناموفق</div>
                </div>
            </div>

            <div class="card-footer-custom">
                <div class="d-flex align-items-center gap-2 text-muted" style="font-size: 0.85rem;">
                    <?php echo $icons['calendar']; ?>
                    <?php 
                    if ($c['scheduled_at']) {
                        echo formatPersianDate($c['scheduled_at'], 'Y/m/d - H:i');
                    } else {
                        echo 'بلافاصله';
                    }
                    ?>
                </div>

                <div class="text-muted small">
                    <?php echo formatMoney($c['estimated_cost'], 'تومان'); ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ─── CAMPAIGNS DISPLAY (LIST VIEW) ──────────────────────────────────────── -->
<div id="campaignsList" class="campaigns-list">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>شناسه</th>
                        <th>نام کمپین</th>
                        <th>وضعیت</th>
                        <th>مخاطبان</th>
                        <th>ارسال‌شده</th>
                        <th>هزینه</th>
                        <th>زمان</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($campaigns)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                هیچ کمپینی یافت نشد
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($campaigns as $c): ?>
                        <tr>
                            <td class="text-muted fw-bold">#{<?php echo $c['id']; ?>}</td>
                            <td>
                                <a href="campaign_view.php?id=<?php echo $c['id']; ?>" class="text-dark fw-bold text-decoration-none">
                                    <?php echo htmlspecialchars($c['name']); ?>
                                </a>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $c['status']; ?>">
                                    <?php
                                    $statuses = [
                                        'draft' => 'پیش‌نویس',
                                        'scheduled' => 'برنامه‌ریزی‌شده',
                                        'active' => 'در حال ارسال',
                                        'completed' => 'تکمیل شده',
                                        'paused' => 'متوقف',
                                        'failed' => 'خطا'
                                    ];
                                    echo $statuses[$c['status']] ?? 'نامشخص';
                                    ?>
                                </span>
                            </td>
                            <td><?php echo number_format($c['total_recipients']); ?></td>
                            <td><?php echo number_format($c['sent_count']); ?></td>
                            <td class="small fw-bold"><?php echo formatMoney($c['estimated_cost'], 'تومان'); ?></td>
                            <td class="small text-muted">
                                <?php echo $c['scheduled_at'] ? formatPersianDate($c['scheduled_at'], 'Y/m/d H:i') : 'بلافاصله'; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="campaign_view.php?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-light text-primary" title="مشاهده">
                                        <?php echo $icons['eye']; ?>
                                    </a>
                                    
                                    <?php if ($c['status'] === 'draft' || $c['status'] === 'scheduled'): ?>
                                    <a href="campaign_form.php?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-light text-warning" title="ویرایش">
                                        <?php echo $icons['edit']; ?>
                                    </a>
                                    <?php endif; ?>

                                    <?php if (hasPermission('delete_sms_campaigns')): ?>
                                    <button onclick="confirmDelete(<?php echo $c['id']; ?>)" class="btn btn-sm btn-light text-danger" title="حذف">
                                        <?php echo $icons['trash']; ?>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ─── SCRIPTS ───────────────────────────────────────────────────────────── -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // View Switcher Logic
    function setView(view) {
        const grid = document.getElementById('campaignsGrid');
        const list = document.getElementById('campaignsList');
        const btnGrid = document.getElementById('btnGrid');
        const btnList = document.getElementById('btnList');

        if (view === 'list') {
            grid.style.display = 'none';
            list.style.display = 'block';
            btnList.classList.add('active');
            btnGrid.classList.remove('active');
        } else {
            list.style.display = 'none';
            grid.style.display = 'grid';
            btnGrid.classList.add('active');
            btnList.classList.remove('active');
        }
        localStorage.setItem('campaigns_view_mode', view);
    }

    // Initialize View based on storage
    document.addEventListener('DOMContentLoaded', () => {
        const savedView = localStorage.getItem('campaigns_view_mode') || 'grid';
        setView(savedView);
    });

    // Delete Confirmation
    function confirmDelete(id) {
        Swal.fire({
            title: 'حذف کمپین',
            text: "آیا از حذف این کمپین اطمینان دارید؟",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'بله، حذف کن',
            cancelButtonText: 'لغو',
            cancelButtonColor: '#64748b'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="campaign_id" value="${id}">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    // Start Campaign
    function startCampaign(id) {
        Swal.fire({
            title: 'شروع ارسال',
            text: "آیا از شروع ارسال این کمپین اطمینان دارید؟",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#00b0a4',
            confirmButtonText: 'بله، شروع کن',
            cancelButtonText: 'لغو'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="start">
                    <input type="hidden" name="campaign_id" value="${id}">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    // Pause Campaign
    function pauseCampaign(id) {
        Swal.fire({
            title: 'توقف کمپین',
            text: "آیا می‌خواهید ارسال را متوقف کنید؟",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f59e0b',
            confirmButtonText: 'بله، متوقف کن',
            cancelButtonText: 'لغو'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="pause">
                    <input type="hidden" name="campaign_id" value="${id}">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>

<?php include __DIR__ . '/../private/footer.php'; ?>
