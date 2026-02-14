<?php
/**
 * ══════════════════════════════════════════════════════════════════════════════
 * ReadyCRM V3.6 - SMS TEMPLATES MANAGEMENT
 * ══════════════════════════════════════════════════════════════════════════════
 * مدیریت الگوهای پیامکی با قابلیت‌های:
 * - ایجاد، ویرایش، حذف الگو
 * - پیش‌نمایش زنده متن پیام
 * - همگام‌سازی با MessageWay API
 * - استفاده از متغیرهای داینامیک
 *
 * @version 3.6.0
 * @author ReadyCRM Team
 * @package ReadyCRM\SMS
 * ══════════════════════════════════════════════════════════════════════════════
 */

$page_title = 'مدیریت الگوهای پیامکی';
$breadcrumb = [
    ['title' => 'داشبورد', 'url' => 'dashboard.php'],
    ['title' => 'الگوهای پیامکی']
];

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/database.php';
require_once __DIR__ . '/../private/auth.php';
require_once __DIR__ . '/../private/functions.php';
require_once __DIR__ . '/../private/sms/SmsTemplateService.php';
require_once __DIR__ . '/../private/sms/MsgWayClient.php';

// ─── AUTH CHECK ─────────────────────────────────────────────────────────────
requireLogin();
if (!hasPermission('view_sms_templates')) {
    setMessage('شما دسترسی لازم برای مشاهده این صفحه را ندارید', 'error');
    header('Location: dashboard.php');
    exit();
}

// ─── SVG ICONS ──────────────────────────────────────────────────────────────
$icons = [
    'plus' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>',
    'search' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>',
    'edit' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>',
    'trash' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>',
    'sync' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>',
    'file' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>',
    'eye' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
    'copy' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>',
];

// ─── HANDLE ACTIONS ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (verifyCSRFToken($csrf_token)) {
        try {
            $templateService = new SmsTemplateService($pdo, $_SESSION['user_id']);

            switch ($action) {
                case 'create':
                case 'update':
                    $template_id = $action === 'update' ? (int)$_POST['template_id'] : null;
                    $data = [
                        'name' => trim($_POST['name']),
                        'content' => trim($_POST['content']),
                        'category' => trim($_POST['category'] ?? ''),
                        'variables' => $_POST['variables'] ?? [],
                        'is_active' => isset($_POST['is_active']) ? 1 : 0,
                    ];

                    if ($action === 'create') {
                        if (!hasPermission('create_sms_templates')) {
                            throw new Exception('شما دسترسی ایجاد ندارید');
                        }
                        $result = $templateService->createTemplate($data);
                        setMessage('الگو با موفقیت ایجاد شد', 'success');
                        logActivity($_SESSION['user_id'], 'create_sms_template', 'sms_templates', $result['template_id']);
                    } else {
                        if (!hasPermission('edit_sms_templates')) {
                            throw new Exception('شما دسترسی ویرایش ندارید');
                        }
                        $result = $templateService->updateTemplate($template_id, $data);
                        setMessage('الگو با موفقیت به‌روزرسانی شد', 'success');
                        logActivity($_SESSION['user_id'], 'update_sms_template', 'sms_templates', $template_id);
                    }
                    break;

                case 'delete':
                    if (!hasPermission('delete_sms_templates')) {
                        throw new Exception('شما دسترسی حذف ندارید');
                    }
                    $template_id = (int)$_POST['template_id'];
                    $pdo->prepare("UPDATE sms_templates SET is_active = 0 WHERE id = ?")->execute([$template_id]);
                    setMessage('الگو با موفقیت حذف شد', 'success');
                    logActivity($_SESSION['user_id'], 'delete_sms_template', 'sms_templates', $template_id);
                    break;

                case 'sync':
                    $result = $templateService->syncWithMessageWay();
                    if ($result['success']) {
                        setMessage("همگام‌سازی موفق: {$result['synced']} الگو به‌روزرسانی شد", 'success');
                    } else {
                        setMessage('خطا در همگام‌سازی: ' . $result['message'], 'error');
                    }
                    break;

                default:
                    throw new Exception('عملیات نامعتبر');
            }

            header("Location: templates.php");
            exit();

        } catch (Exception $e) {
            setMessage('خطا: ' . $e->getMessage(), 'error');
            error_log("Template action error: " . $e->getMessage());
            header("Location: templates.php");
            exit();
        }
    }
}

// ─── FETCH TEMPLATES ────────────────────────────────────────────────────────
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

$where = ["1=1"];
$params = [];

if ($search) {
    $where[] = "(name LIKE ? OR content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($category) {
    $where[] = "category = ?";
    $params[] = $category;
}

$where_sql = implode(' AND ', $where);

try {
    $sql = "
        SELECT t.*,
               CONCAT(u.first_name, ' ', u.last_name) as creator_name,
               (SELECT COUNT(*) FROM sms_campaigns WHERE template_id = t.id) as usage_count
        FROM sms_templates t
        LEFT JOIN users u ON t.created_by = u.id
        WHERE $where_sql
        ORDER BY created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $templates = $stmt->fetchAll();

    // Get categories
    $categories = $pdo->query("SELECT DISTINCT category FROM sms_templates WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    $templates = [];
    $categories = [];
    setMessage('خطا در بارگذاری الگوها: ' . $e->getMessage(), 'error');
    error_log("Templates query error: " . $e->getMessage());
}

// Stats
$stats = [
    'total' => count($templates),
    'active' => count(array_filter($templates, fn($t) => $t['is_active'] == 1)),
    'categories' => count($categories),
];

$csrf_token = generateCSRFToken();
include __DIR__ . '/../private/header.php';
?>

<!-- ─── STYLES ────────────────────────────────────────────────────────────── -->
<style>
    :root {
        --brand: #00b0a4;
        --brand-hover: #00968c;
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
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        border-radius: var(--card-radius);
        padding: 24px;
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

    .stat-content h3 { font-size: 2rem; font-weight: 800; margin: 0; color: var(--dark); }
    .stat-content p { margin: 5px 0 0; color: var(--text-gray); font-size: 0.9rem; }

    .stat-icon-wrapper {
        width: 60px; height: 60px;
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-size: 1.5rem;
    }

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
        max-width: 350px;
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

    .btn-brand {
        background: var(--brand); color: white; padding: 10px 20px; border-radius: 12px;
        border: none; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
        transition: var(--transition); text-decoration: none;
    }
    .btn-brand:hover { background: var(--brand-hover); color: white; transform: translateY(-2px); }

    .btn-secondary {
        background: #64748b; color: white; padding: 10px 20px; border-radius: 12px;
        border: none; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
        transition: var(--transition);
    }
    .btn-secondary:hover { background: #475569; }

    /* ─── Templates Grid ─── */
    .templates-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
        gap: 24px;
    }

    .template-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: var(--card-radius);
        padding: 24px;
        transition: var(--transition);
        position: relative;
        display: flex;
        flex-direction: column;
    }

    .template-card:hover {
        border-color: var(--brand);
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.05);
    }

    .template-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 16px;
    }

    .template-icon {
        width: 50px; height: 50px;
        border-radius: 12px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem;
    }

    .template-title {
        font-size: 1.15rem; font-weight: 700; margin-bottom: 8px;
        color: var(--dark); cursor: pointer;
    }
    .template-title:hover { color: var(--brand); }

    .template-category {
        display: inline-block;
        background: #f1f5f9;
        color: var(--text-gray);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-bottom: 12px;
    }

    .template-content {
        background: #f8fafc;
        padding: 16px;
        border-radius: 10px;
        margin-bottom: 16px;
        font-size: 0.9rem;
        line-height: 1.7;
        color: #334155;
        max-height: 120px;
        overflow-y: auto;
        border-right: 3px solid var(--brand);
    }

    .template-variables {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 16px;
    }

    .variable-badge {
        background: #e0f2f1;
        color: #00796b;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        font-family: monospace;
    }

    .template-footer {
        margin-top: auto;
        padding-top: 16px;
        border-top: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .template-meta {
        font-size: 0.8rem;
        color: var(--text-gray);
    }

    .template-actions {
        display: flex;
        gap: 8px;
    }

    .template-actions button,
    .template-actions a {
        background: transparent;
        border: 1px solid #e2e8f0;
        padding: 8px 12px;
        border-radius: 8px;
        cursor: pointer;
        transition: var(--transition);
        color: var(--text-gray);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }

    .template-actions button:hover { border-color: var(--brand); color: var(--brand); }
    .template-actions .btn-danger:hover { border-color: #ef4444; color: #ef4444; }

    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .status-active { background: #d1fae5; color: #065f46; }
    .status-inactive { background: #fee2e2; color: #991b1b; }

    /* ─── Modal ─── */
    .modal-content { border-radius: 16px; border: none; }
    .modal-header { border-bottom: 1px solid #e2e8f0; background: #f8fafc; }
    .modal-footer { border-top: 1px solid #e2e8f0; background: #f8fafc; }

    .form-label { font-weight: 600; color: var(--dark); margin-bottom: 8px; }
    .form-control { border-radius: 10px; border: 1px solid #cbd5e1; padding: 10px 14px; }
    .form-control:focus { border-color: var(--brand); box-shadow: 0 0 0 3px rgba(0, 176, 164, 0.1); }

    .empty-state {
        text-align: center; padding: 60px 20px; color: var(--text-gray);
    }
    .empty-state svg { width: 120px; height: 120px; opacity: 0.3; margin-bottom: 20px; }

    @media (max-width: 768px) {
        .toolbar-card { flex-direction: column; align-items: stretch; }
        .search-box { max-width: 100%; }
        .templates-grid { grid-template-columns: 1fr; }
    }
</style>

<!-- ─── FLASH MESSAGES ────────────────────────────────────────────────────── -->
<?php echo displayMessage(); ?>

<!-- ─── STATS SECTION ─────────────────────────────────────────────────────── -->
<div class="stats-container">
    <div class="stat-card">
        <div class="stat-content">
            <h3><?php echo $stats['total']; ?></h3>
            <p>کل الگوها</p>
        </div>
        <div class="stat-icon-wrapper">
            <?php echo $icons['file']; ?>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-content">
            <h3><?php echo $stats['active']; ?></h3>
            <p>الگوهای فعال</p>
        </div>
        <div class="stat-icon-wrapper">
            <?php echo $icons['file']; ?>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-content">
            <h3><?php echo $stats['categories']; ?></h3>
            <p>دسته‌بندی‌ها</p>
        </div>
        <div class="stat-icon-wrapper">
            <?php echo $icons['file']; ?>
        </div>
    </div>
</div>

<!-- ─── TOOLBAR ───────────────────────────────────────────────────────────── -->
<div class="toolbar-card">
    <form method="GET" class="d-flex flex-grow-1 flex-wrap gap-3 align-items-center w-100">
        <div class="search-box">
            <?php echo $icons['search']; ?>
            <input type="text" name="search" placeholder="جستجوی نام یا محتوای الگو..." value="<?php echo htmlspecialchars($search); ?>">
        </div>

        <select name="category" class="custom-select" onchange="this.form.submit()">
            <option value="">همه دسته‌ها</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category == $cat ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cat); ?>
            </option>
            <?php endforeach; ?>
        </select>

        <div class="ms-auto d-flex gap-2">
            <?php if (hasPermission('sync_sms_templates')): ?>
            <form method="POST" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="sync">
                <button type="submit" class="btn-secondary">
                    <?php echo $icons['sync']; ?> همگام‌سازی
                </button>
            </form>
            <?php endif; ?>

            <?php if (hasPermission('create_sms_templates')): ?>
            <button type="button" class="btn-brand" data-bs-toggle="modal" data-bs-target="#templateModal" onclick="openCreateModal()">
                <?php echo $icons['plus']; ?> الگوی جدید
            </button>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- ─── TEMPLATES GRID ────────────────────────────────────────────────────── -->
<div class="templates-grid">
    <?php if (empty($templates)): ?>
        <div class="col-12">
            <div class="empty-state">
                <?php echo $icons['file']; ?>
                <h5>هیچ الگویی یافت نشد</h5>
                <p class="text-muted">برای شروع، یک الگو ایجاد کنید.</p>
                <?php if (hasPermission('create_sms_templates')): ?>
                <button class="btn-brand mt-3" data-bs-toggle="modal" data-bs-target="#templateModal" onclick="openCreateModal()">
                    <?php echo $icons['plus']; ?> ایجاد اولین الگو
                </button>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($templates as $t): ?>
        <div class="template-card">
            <div class="template-header">
                <div class="d-flex gap-3 align-items-center">
                    <div class="template-icon">
                        <?php echo $icons['file']; ?>
                    </div>
                    <div>
                        <div class="template-title" onclick="showPreview(<?php echo $t['id']; ?>)">
                            <?php echo htmlspecialchars($t['name']); ?>
                        </div>
                        <?php if ($t['category']): ?>
                        <span class="template-category"><?php echo htmlspecialchars($t['category']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="status-badge status-<?php echo $t['is_active'] ? 'active' : 'inactive'; ?>">
                    <?php echo $t['is_active'] ? 'فعال' : 'غیرفعال'; ?>
                </span>
            </div>

            <div class="template-content">
                <?php echo nl2br(htmlspecialchars($t['content'])); ?>
            </div>

            <?php if (!empty($t['variables'])): ?>
            <div class="template-variables">
                <?php 
                $vars = is_string($t['variables']) ? json_decode($t['variables'], true) : $t['variables'];
                if (is_array($vars)) {
                    foreach ($vars as $var) {
                        echo '<span class="variable-badge">{' . htmlspecialchars($var) . '}</span>';
                    }
                }
                ?>
            </div>
            <?php endif; ?>

            <div class="template-footer">
                <div class="template-meta">
                    <div><i class="fas fa-user ms-1"></i> <?php echo htmlspecialchars($t['creator_name']); ?></div>
                    <div class="mt-1"><i class="fas fa-chart-line ms-1"></i> <?php echo $t['usage_count']; ?> استفاده</div>
                </div>

                <div class="template-actions">
                    <button type="button" title="پیش‌نمایش" onclick="showPreview(<?php echo $t['id']; ?>)">
                        <?php echo $icons['eye']; ?>
                    </button>

                    <?php if (hasPermission('edit_sms_templates')): ?>
                    <button type="button" title="ویرایش" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($t)); ?>)">
                        <?php echo $icons['edit']; ?>
                    </button>
                    <?php endif; ?>

                    <?php if (hasPermission('delete_sms_templates')): ?>
                    <button type="button" class="btn-danger" title="حذف" onclick="confirmDelete(<?php echo $t['id']; ?>)">
                        <?php echo $icons['trash']; ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ─── CREATE/EDIT MODAL ─────────────────────────────────────────────────── -->
<div class="modal fade" id="templateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="templateForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="template_id" id="templateId">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">الگوی جدید</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">نام الگو <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="templateName" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">دسته‌بندی</label>
                            <input type="text" class="form-control" name="category" id="templateCategory" list="categoryList">
                            <datalist id="categoryList">
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <div class="col-12">
                            <label class="form-label">محتوای پیام <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="content" id="templateContent" rows="6" required></textarea>
                            <small class="text-muted">تعداد کاراکتر: <span id="charCount">0</span></small>
                        </div>

                        <div class="col-12">
                            <label class="form-label">متغیرهای موجود</label>
                            <div class="d-flex flex-wrap gap-2" id="variablesContainer">
                                <?php
                                $available_vars = ['name', 'phone', 'email', 'company', 'amount', 'date'];
                                foreach ($available_vars as $var): ?>
                                <span class="variable-badge" style="cursor: pointer;" onclick="insertVariable('<?php echo $var; ?>')">
                                    {<?php echo $var; ?>}
                                </span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="templateActive" checked>
                                <label class="form-check-label" for="templateActive">
                                    الگو فعال است
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn-brand">ذخیره</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ─── PREVIEW MODAL ─────────────────────────────────────────────────────── -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">پیش‌نمایش الگو</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="previewContent" class="template-content"></div>
            </div>
        </div>
    </div>
</div>

<!-- ─── SCRIPTS ───────────────────────────────────────────────────────────── -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Character Counter
    document.getElementById('templateContent')?.addEventListener('input', function() {
        document.getElementById('charCount').textContent = this.value.length;
    });

    // Open Create Modal
    function openCreateModal() {
        document.getElementById('modalTitle').textContent = 'الگوی جدید';
        document.getElementById('formAction').value = 'create';
        document.getElementById('templateForm').reset();
        document.getElementById('charCount').textContent = '0';
    }

    // Open Edit Modal
    function openEditModal(template) {
        document.getElementById('modalTitle').textContent = 'ویرایش الگو';
        document.getElementById('formAction').value = 'update';
        document.getElementById('templateId').value = template.id;
        document.getElementById('templateName').value = template.name;
        document.getElementById('templateCategory').value = template.category || '';
        document.getElementById('templateContent').value = template.content;
        document.getElementById('templateActive').checked = template.is_active == 1;
        document.getElementById('charCount').textContent = template.content.length;

        new bootstrap.Modal(document.getElementById('templateModal')).show();
    }

    // Insert Variable
    function insertVariable(varName) {
        const textarea = document.getElementById('templateContent');
        const cursorPos = textarea.selectionStart;
        const textBefore = textarea.value.substring(0, cursorPos);
        const textAfter = textarea.value.substring(cursorPos);
        textarea.value = textBefore + '{' + varName + '}' + textAfter;
        textarea.focus();
        textarea.selectionStart = textarea.selectionEnd = cursorPos + varName.length + 2;
        document.getElementById('charCount').textContent = textarea.value.length;
    }

    // Show Preview
    function showPreview(templateId) {
        const templates = <?php echo json_encode($templates); ?>;
        const template = templates.find(t => t.id == templateId);
        if (template) {
            document.getElementById('previewContent').innerHTML = template.content.replace(/\n/g, '<br>');
            new bootstrap.Modal(document.getElementById('previewModal')).show();
        }
    }

    // Delete Confirmation
    function confirmDelete(id) {
        Swal.fire({
            title: 'حذف الگو',
            text: "آیا از حذف این الگو اطمینان دارید؟",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'بله، حذف کن',
            cancelButtonText: 'لغو'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="template_id" value="${id}">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>

<?php include __DIR__ . '/../private/footer.php'; ?>
