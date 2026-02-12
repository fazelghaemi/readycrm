<?php
/**
 * ══════════════════════════════════════════════════════════════════════════════
 * ReadyCRM V3.5 - PROJECTS DASHBOARD
 * ══════════════════════════════════════════════════════════════════════════════
 * داشبورد پیشرفته مدیریت پروژه‌ها با قابلیت سوییچ نما (گرید/لیست)،
 * مدیریت اعضا، بودجه‌بندی و پیگیری پیشرفت.
 *
 * @version 3.5.0
 * @author Ready Studio
 * ══════════════════════════════════════════════════════════════════════════════
 */

$page_title = 'مدیریت پروژه‌ها';
$breadcrumb = [
    ['title' => 'داشبورد', 'url' => 'dashboard.php'],
    ['title' => 'پروژه‌ها']
];

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/database.php';
require_once __DIR__ . '/../private/auth.php';
require_once __DIR__ . '/../private/functions.php';

// ─── AUTH CHECK ─────────────────────────────────────────────────────────────
requireLogin();
// اگر سیستم پرمیشن view_projects ندارد، می‌توانید این خط را کامنت کنید
// checkPermission('view_projects');

// ─── SVG ICONS REPOSITORY ───────────────────────────────────────────────────
$icons = [
    'plus' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>',
    'grid' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>',
    'list' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>',
    'search' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>',
    'filter' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>',
    'briefcase' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>',
    'activity' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>',
    'dollar' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>',
    'clock' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>',
    'calendar' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
    'users' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
    'more' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>',
    'trash' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>',
    'edit' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>',
    'eye' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
];

// ─── HANDLE ACTIONS ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (verifyCSRFToken($csrf_token)) {

        // Delete Project
        if ($action === 'delete') {
            $project_id = (int)$_POST['project_id'];
            try {
                // حذف وابستگی‌ها
                $pdo->prepare("DELETE FROM project_members WHERE project_id = ?")->execute([$project_id]);
                $pdo->prepare("DELETE FROM project_milestones WHERE project_id = ?")->execute([$project_id]);
                $pdo->prepare("DELETE FROM tasks WHERE project_id = ?")->execute([$project_id]);
                // حذف کامنت‌های مرتبط
                $pdo->prepare("DELETE FROM comments WHERE related_type = 'project' AND related_id = ?")->execute([$project_id]);
                
                // حذف پروژه
                $pdo->prepare("DELETE FROM projects WHERE id = ?")->execute([$project_id]);
                
                logActivity($_SESSION['user_id'], 'delete_project', 'projects', $project_id);
                setMessage('پروژه با موفقیت حذف شد', 'success');
            } catch (PDOException $e) {
                setMessage('خطا در حذف پروژه: ' . $e->getMessage(), 'error');
            }
            header("Location: projects.php");
            exit();
        }

        // Quick Add Project
        if ($action === 'quick_add') {
            $title = sanitizeInput($_POST['title']);
            $code = 'PRJ-' . rand(1000, 9999);
            $manager_id = (int)$_POST['manager_id'];
            $start_date = $_POST['start_date'] ?: date('Y-m-d');

            if ($title && $manager_id) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO projects (project_code, title, manager_id, start_date, created_by, status) 
                        VALUES (?, ?, ?, ?, ?, 'not_started')
                    ");
                    $stmt->execute([$code, $title, $manager_id, $start_date, $_SESSION['user_id']]);

                    $new_id = $pdo->lastInsertId();
                    
                    // اضافه کردن مدیر به اعضای پروژه
                    $pdo->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'manager')")
                        ->execute([$new_id, $manager_id]);

                    setMessage('پروژه جدید با موفقیت ایجاد شد', 'success');
                } catch (PDOException $e) {
                    setMessage('خطا در ایجاد پروژه: ' . $e->getMessage(), 'error');
                }
            }
            header("Location: projects.php");
            exit();
        }
    }
}

// ─── FILTERS & SEARCH ──────────────────────────────────────────────────────
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$priority = $_GET['priority'] ?? '';
$manager_id = $_GET['manager_id'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

$where = ["1=1"];
$params = [];

if ($search) {
    $where[] = "(p.title LIKE ? OR p.project_code LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($status) {
    $where[] = "p.status = ?";
    $params[] = $status;
}
if ($priority) {
    $where[] = "p.priority = ?";
    $params[] = $priority;
}
if ($manager_id) {
    $where[] = "p.manager_id = ?";
    $params[] = $manager_id;
}

$where_sql = implode(' AND ', $where);

// Sorting
$order_by = "p.created_at DESC";
if ($sort === 'deadline') $order_by = "p.deadline ASC";
if ($sort === 'progress') $order_by = "p.progress DESC";
if ($sort === 'budget') $order_by = "p.budget DESC";

// ─── STATS ─────────────────────────────────────────────────────────────────
try {
    $stats = [
        'total' => (int)$pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn(),
        'active' => (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'in_progress'")->fetchColumn(),
        'budget' => (float)$pdo->query("SELECT COALESCE(SUM(budget), 0) FROM projects")->fetchColumn(),
        'delayed' => (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE deadline < CURDATE() AND status NOT IN ('completed', 'cancelled')")->fetchColumn(),
    ];
} catch (PDOException $e) {
    $stats = ['total' => 0, 'active' => 0, 'budget' => 0, 'delayed' => 0];
    error_log("Stats query error: " . $e->getMessage());
}

// ─── FETCH PROJECTS ────────────────────────────────────────────────────────
// تصحیح: استفاده از company_name به جای c.name
try {
    $sql = "
        SELECT p.*,
               CONCAT(m.first_name, ' ', m.last_name) as manager_name, 
               m.avatar as manager_avatar,
               c.company_name as customer_name,
               (SELECT COUNT(*) FROM project_members WHERE project_id = p.id) as member_count,
               (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status != 'completed') as open_tasks
        FROM projects p
        LEFT JOIN users m ON p.manager_id = m.id
        LEFT JOIN customers c ON p.customer_id = c.id
        WHERE $where_sql
        ORDER BY $order_by
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $projects = $stmt->fetchAll();
} catch (PDOException $e) {
    $projects = [];
    setMessage('خطا در بارگذاری پروژه‌ها: ' . $e->getMessage(), 'error');
    error_log("Projects query error: " . $e->getMessage());
}

// Fetch Users for dropdowns
try {
    $users = $pdo->query("SELECT id, first_name, last_name FROM users WHERE status = 'active' ORDER BY first_name")->fetchAll();
} catch (PDOException $e) {
    $users = [];
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
    .icon-red { background: #fee2e2; color: #dc2626; }

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

    /* ─── Project Grid Cards ─── */
    .projects-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 24px;
    }

    .project-card {
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

    .project-card:hover {
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

    .project-icon {
        width: 48px; height: 48px;
        border-radius: 12px;
        background: var(--brand-light);
        color: var(--brand);
        display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 1.2rem;
    }

    .priority-dot {
        width: 10px; height: 10px; border-radius: 50%; display: inline-block;
    }
    .priority-urgent { background: #ef4444; box-shadow: 0 0 0 3px #fee2e2; }
    .priority-high { background: #f97316; }
    .priority-medium { background: #eab308; }
    .priority-low { background: #22c55e; }

    .project-title {
        font-size: 1.1rem; font-weight: 700; margin-bottom: 8px;
        color: var(--dark); display: block; text-decoration: none;
    }
    .project-title:hover { color: var(--brand); }

    .project-meta {
        font-size: 0.85rem; color: var(--text-gray);
        margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap;
    }

    .progress-wrapper { margin-bottom: 20px; }
    .progress-info { display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 5px; }
    .progress-bar-bg { height: 8px; background: #f1f5f9; border-radius: 10px; overflow: hidden; }
    .progress-fill { height: 100%; background: var(--brand); border-radius: 10px; transition: width 1s ease; }

    .card-footer-custom {
        margin-top: auto;
        padding-top: 16px;
        border-top: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Team Avatars */
    .team-stack { display: flex; padding-right: 10px; }
    .team-member {
        width: 32px; height: 32px; border-radius: 50%;
        border: 2px solid white; background: #e2e8f0;
        margin-right: -10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.75rem; color: #64748b; font-weight: 600;
        position: relative;
    }
    .team-member:first-child { margin-right: 0; }

    /* ─── List View ─── */
    .projects-list { display: none; }
    .custom-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .custom-table th { 
        text-align: right; padding: 16px; border-bottom: 2px solid #e2e8f0; 
        color: var(--text-gray); font-weight: 600; background: #f8fafc;
    }
    .custom-table td { padding: 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; background: white; }
    .custom-table tbody tr:hover td { background: #f8fafc; }

    /* ─── Utility ─── */
    .btn-brand {
        background: var(--brand); color: white; padding: 10px 20px; border-radius: 12px;
        border: none; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
        transition: var(--transition); text-decoration: none;
    }
    .btn-brand:hover { background: var(--brand-hover); color: white; }

    .status-badge {
        padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;
    }
    .status-in_progress { background: #e0f2fe; color: #0369a1; }
    .status-completed { background: #dcfce7; color: #15803d; }
    .status-on_hold { background: #fef9c3; color: #854d0e; }
    .status-cancelled { background: #fee2e2; color: #991b1b; }
    .status-not_started { background: #f1f5f9; color: #475569; }

    .empty-state {
        text-align: center; padding: 60px 20px; color: var(--text-gray);
    }
    .empty-state svg { width: 120px; height: 120px; opacity: 0.3; margin-bottom: 20px; }

    @media (max-width: 768px) {
        .toolbar-card { flex-direction: column; align-items: stretch; }
        .search-box { max-width: 100%; }
        .projects-grid { grid-template-columns: 1fr; }
    }
</style>

<!-- ─── FLASH MESSAGES ────────────────────────────────────────────────────── -->
<?php echo displayMessage(); ?>

<!-- ─── STATS SECTION ─────────────────────────────────────────────────────── -->
<div class="stats-container">
    <div class="stat-card">
        <div class="stat-content">
            <h3><?php echo number_format($stats['total']); ?></h3>
            <p>کل پروژه‌ها</p>
        </div>
        <div class="stat-icon-wrapper icon-blue">
            <?php echo $icons['briefcase']; ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <h3><?php echo number_format($stats['active']); ?></h3>
            <p>پروژه‌های فعال</p>
        </div>
        <div class="stat-icon-wrapper icon-green">
            <?php echo $icons['activity']; ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <h3 style="font-size: 1.5rem;"><?php echo formatMoney($stats['budget']); ?></h3>
            <p>بودجه تعریف شده</p>
        </div>
        <div class="stat-icon-wrapper icon-purple">
            <?php echo $icons['dollar']; ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <h3><?php echo number_format($stats['delayed']); ?></h3>
            <p>پروژه دارای تاخیر</p>
        </div>
        <div class="stat-icon-wrapper icon-red">
            <?php echo $icons['clock']; ?>
        </div>
    </div>
</div>

<!-- ─── TOOLBAR & FILTERS ─────────────────────────────────────────────────── -->
<div class="toolbar-card">
    <form method="GET" class="d-flex flex-grow-1 flex-wrap gap-3 align-items-center w-100">
        <!-- Search -->
        <div class="search-box">
            <?php echo $icons['search']; ?>
            <input type="text" name="search" placeholder="جستجوی نام یا کد پروژه..." value="<?php echo htmlspecialchars($search); ?>">
        </div>

        <!-- Filters -->
        <div class="filter-group flex-grow-1">
            <select name="status" class="custom-select" onchange="this.form.submit()">
                <option value="">همه وضعیت‌ها</option>
                <option value="in_progress" <?php echo $status == 'in_progress' ? 'selected' : ''; ?>>در حال انجام</option>
                <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>تکمیل شده</option>
                <option value="not_started" <?php echo $status == 'not_started' ? 'selected' : ''; ?>>شروع نشده</option>
                <option value="on_hold" <?php echo $status == 'on_hold' ? 'selected' : ''; ?>>متوقف شده</option>
            </select>

            <select name="priority" class="custom-select" onchange="this.form.submit()">
                <option value="">همه اولویت‌ها</option>
                <option value="urgent" <?php echo $priority == 'urgent' ? 'selected' : ''; ?>>فوری</option>
                <option value="high" <?php echo $priority == 'high' ? 'selected' : ''; ?>>بالا</option>
                <option value="medium" <?php echo $priority == 'medium' ? 'selected' : ''; ?>>متوسط</option>
                <option value="low" <?php echo $priority == 'low' ? 'selected' : ''; ?>>کم</option>
            </select>

            <select name="sort" class="custom-select" onchange="this.form.submit()">
                <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>جدیدترین</option>
                <option value="deadline" <?php echo $sort == 'deadline' ? 'selected' : ''; ?>>نزدیکترین ددلاین</option>
                <option value="progress" <?php echo $sort == 'progress' ? 'selected' : ''; ?>>بیشترین پیشرفت</option>
                <option value="budget" <?php echo $sort == 'budget' ? 'selected' : ''; ?>>بیشترین بودجه</option>
            </select>
        </div>

        <!-- Actions -->
        <div class="d-flex align-items-center gap-3">
            <div class="view-switcher">
                <button type="button" class="view-btn active" id="btnGrid" onclick="setView('grid')"><?php echo $icons['grid']; ?></button>
                <button type="button" class="view-btn" id="btnList" onclick="setView('list')"><?php echo $icons['list']; ?></button>
            </div>

            <a href="project_form.php" class="btn-brand">
                <?php echo $icons['plus']; ?> پروژه جدید
            </a>
        </div>
    </form>
</div>

<!-- ─── PROJECTS DISPLAY (GRID VIEW) ──────────────────────────────────────── -->
<div id="projectsGrid" class="projects-grid">
    <?php if (empty($projects)): ?>
        <div class="col-12">
            <div class="empty-state">
                <?php echo $icons['briefcase']; ?>
                <h5>هیچ پروژه‌ای یافت نشد</h5>
                <p class="text-muted">برای شروع، یک پروژه جدید ایجاد کنید.</p>
                <a href="project_form.php" class="btn-brand mt-3">
                    <?php echo $icons['plus']; ?> ایجاد اولین پروژه
                </a>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($projects as $prj):
            $days_left = $prj['deadline'] ? ceil((strtotime($prj['deadline']) - time()) / 86400) : null;
            $deadline_color = ($days_left !== null && $days_left < 3 && $days_left >= 0) ? 'text-danger' : 'text-muted';
        ?>
        <div class="project-card">
            <div class="card-header-custom">
                <div class="d-flex gap-3 align-items-center">
                    <div class="project-icon">
                        <?php echo mb_substr($prj['title'], 0, 1, 'UTF-8'); ?>
                    </div>
                    <div>
                        <span class="status-badge status-<?php echo $prj['status']; ?> mb-1 d-inline-block">
                            <?php echo getStatusTitle($prj['status'], 'project'); ?>
                        </span>
                        <div class="text-muted small" style="font-size: 0.75rem;"><?php echo htmlspecialchars($prj['project_code']); ?></div>
                    </div>
                </div>
                <div class="dropdown">
                    <button class="btn btn-link text-muted p-0" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo $icons['more']; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-3">
                        <li><a class="dropdown-item" href="project_view.php?id=<?php echo $prj['id']; ?>"><?php echo $icons['eye']; ?> مشاهده</a></li>
                        <li><a class="dropdown-item" href="project_form.php?id=<?php echo $prj['id']; ?>"><?php echo $icons['edit']; ?> ویرایش</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="#" onclick="confirmDelete(<?php echo $prj['id']; ?>); return false;"><?php echo $icons['trash']; ?> حذف</a></li>
                    </ul>
                </div>
            </div>

            <a href="project_view.php?id=<?php echo $prj['id']; ?>" class="project-title"><?php echo htmlspecialchars($prj['title']); ?></a>

            <div class="project-meta">
                <?php if($prj['customer_name']): ?>
                    <span><i class="fas fa-building ms-1"></i> <?php echo htmlspecialchars($prj['customer_name']); ?></span>
                <?php endif; ?>
                <span><i class="fas fa-tasks ms-1"></i> <?php echo (int)$prj['open_tasks']; ?> وظیفه باز</span>
            </div>

            <div class="progress-wrapper">
                <div class="progress-info">
                    <span class="text-muted">پیشرفت</span>
                    <span class="fw-bold text-dark"><?php echo (int)$prj['progress']; ?>%</span>
                </div>
                <div class="progress-bar-bg">
                    <div class="progress-fill" style="width: <?php echo (int)$prj['progress']; ?>%"></div>
                </div>
            </div>

            <div class="card-footer-custom">
                <div class="team-stack">
                    <?php if($prj['manager_name']): ?>
                        <div class="team-member" title="مدیر: <?php echo htmlspecialchars($prj['manager_name']); ?>" style="border-color: var(--brand); color: var(--brand);">
                             <?php echo mb_substr($prj['manager_name'], 0, 1, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>

                    <?php 
                    $member_count = (int)$prj['member_count'];
                    for($i=0; $i < min(3, $member_count); $i++): 
                    ?>
                        <div class="team-member">M</div>
                    <?php endfor; ?>

                    <?php if($member_count > 3): ?>
                        <div class="team-member" style="background: white; border-color: #cbd5e1;">+<?php echo $member_count - 3; ?></div>
                    <?php endif; ?>
                </div>

                <div class="d-flex align-items-center gap-2 <?php echo $deadline_color; ?>" style="font-size: 0.85rem;">
                    <?php echo $icons['calendar']; ?>
                    <?php
                        if ($days_left !== null) {
                            if ($days_left < 0) {
                                echo abs($days_left) . ' روز تاخیر';
                            } elseif ($days_left == 0) {
                                echo 'امروز';
                            } else {
                                echo $days_left . ' روز مانده';
                            }
                        } else {
                            echo 'بدون ددلاین';
                        }
                    ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ─── PROJECTS DISPLAY (LIST VIEW) ──────────────────────────────────────── -->
<div id="projectsList" class="projects-list">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>کد پروژه</th>
                        <th>نام پروژه</th>
                        <th>مدیر</th>
                        <th>وضعیت</th>
                        <th>پیشرفت</th>
                        <th>ددلاین</th>
                        <th>بودجه</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($projects)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                هیچ پروژه‌ای یافت نشد
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($projects as $prj): ?>
                        <tr>
                            <td class="text-muted fw-bold"><?php echo htmlspecialchars($prj['project_code']); ?></td>
                            <td>
                                <a href="project_view.php?id=<?php echo $prj['id']; ?>" class="text-dark fw-bold text-decoration-none">
                                    <?php echo htmlspecialchars($prj['title']); ?>
                                </a>
                                <?php if($prj['priority'] == 'urgent'): ?>
                                    <span class="badge bg-danger rounded-pill ms-2" style="font-size:0.6rem;">فوری</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="team-member" style="width:28px;height:28px;font-size:0.7rem;margin:0;">
                                        <?php echo mb_substr($prj['manager_name'] ?? '?', 0, 1, 'UTF-8'); ?>
                                    </div>
                                    <span class="small"><?php echo htmlspecialchars($prj['manager_name'] ?? 'نامشخص'); ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $prj['status']; ?>">
                                    <?php echo getStatusTitle($prj['status'], 'project'); ?>
                                </span>
                            </td>
                            <td style="width: 150px;">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 6px;">
                                        <div class="progress-bar bg-info" style="width: <?php echo (int)$prj['progress']; ?>%"></div>
                                    </div>
                                    <span class="small text-muted"><?php echo (int)$prj['progress']; ?>%</span>
                                </div>
                            </td>
                            <td class="small text-muted">
                                <?php echo $prj['deadline'] ? formatPersianDate($prj['deadline']) : '-'; ?>
                            </td>
                            <td class="small fw-bold text-dark">
                                <?php echo formatMoney($prj['budget'] ?? 0); ?>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="project_form.php?id=<?php echo $prj['id']; ?>" class="btn btn-sm btn-light text-primary" title="ویرایش">
                                        <?php echo $icons['edit']; ?>
                                    </a>
                                    <button onclick="confirmDelete(<?php echo $prj['id']; ?>)" class="btn btn-sm btn-light text-danger" title="حذف">
                                        <?php echo $icons['trash']; ?>
                                    </button>
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
        const grid = document.getElementById('projectsGrid');
        const list = document.getElementById('projectsList');
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
        localStorage.setItem('projects_view_mode', view);
    }

    // Initialize View based on storage
    document.addEventListener('DOMContentLoaded', () => {
        const savedView = localStorage.getItem('projects_view_mode') || 'grid';
        setView(savedView);
    });

    // Delete Confirmation
    function confirmDelete(id) {
        Swal.fire({
            title: 'حذف پروژه',
            text: "آیا از حذف این پروژه و تمام تسک‌های آن اطمینان دارید؟",
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
                    <input type="hidden" name="project_id" value="${id}">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>

<?php include __DIR__ . '/../private/footer.php'; ?>
