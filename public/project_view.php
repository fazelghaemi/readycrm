<?php
/**
 * ══════════════════════════════════════════════════════════════════════════════
 * ReadyCRM V3.5 - PROJECT HUB (DETAIL VIEW)
 * ══════════════════════════════════════════════════════════════════════════════
 * هاب مرکزی پروژه شامل داشبورد، تایم‌لاین، تسک‌ها، اعضا، فایل‌ها و مالی.
 * @version 3.5.0
 * @author Ready Studio
 * ══════════════════════════════════════════════════════════════════════════════
 */

$project_id = (int)($_GET['id'] ?? 0);
$page_title = 'جزئیات پروژه';
$breadcrumb = [
    ['title' => 'داشبورد', 'url' => 'dashboard.php'],
    ['title' => 'پروژه‌ها', 'url' => 'projects.php'],
    ['title' => 'مشاهده پروژه']
];

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/database.php';
require_once __DIR__ . '/../private/auth.php';
require_once __DIR__ . '/../private/functions.php';

// ─── AUTH CHECK ─────────────────────────────────────────────────────────────
if (!$project_id) {
    header('Location: projects.php');
    exit();
}

// ─── ICONS REPOSITORY (SVG) ─────────────────────────────────────────────────
$icons = [
    'arrow-right' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>',
    'edit' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>',
    'trash' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>',
    'check-circle' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
    'clock' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>',
    'calendar' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
    'users' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
    'layers' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>',
    'plus' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>',
    'flag' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>',
    'message' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>',
    'paperclip' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>',
    'dollar' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>',
];

// ─── HANDLE ACTIONS ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (verifyCSRFToken($csrf_token)) {
        
        // 1. Add Milestone
        if ($action === 'add_milestone') {
            $title = sanitizeInput($_POST['title']);
            $due_date = $_POST['due_date'];
            $pdo->prepare("INSERT INTO project_milestones (project_id, title, due_date) VALUES (?, ?, ?)")
                ->execute([$project_id, $title, $due_date]);
            setMessage('مایل‌ستون جدید اضافه شد', 'success');
        }

        // 2. Add Team Member
        if ($action === 'add_member') {
            $user_id = (int)$_POST['user_id'];
            $role = sanitizeInput($_POST['role']);
            
            // Check if exists
            $exists = $pdo->prepare("SELECT id FROM project_members WHERE project_id = ? AND user_id = ?");
            $exists->execute([$project_id, $user_id]);
            
            if (!$exists->fetch()) {
                $pdo->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)")
                    ->execute([$project_id, $user_id, $role]);
                setMessage('عضو جدید به تیم اضافه شد', 'success');
            } else {
                setMessage('این کاربر قبلاً عضو پروژه است', 'error');
            }
        }

        // 3. Add Comment
        if ($action === 'add_comment') {
            $body = sanitizeInput($_POST['comment']);
            if ($body) {
                $pdo->prepare("INSERT INTO comments (related_type, related_id, user_id, body) VALUES ('project', ?, ?, ?)")
                    ->execute([$project_id, $_SESSION['user_id'], $body]);
            }
        }
        
        // 4. Update Status (Quick Action)
        if ($action === 'update_status') {
            $status = $_POST['status'];
            $pdo->prepare("UPDATE projects SET status = ? WHERE id = ?")->execute([$status, $project_id]);
            setMessage('وضعیت پروژه تغییر کرد', 'success');
        }

        // Redirect to avoid resubmission
        header("Location: project_view.php?id=$project_id");
        exit();
    }
}

// ─── FETCH DATA ─────────────────────────────────────────────────────────────

// 1. Main Project Info
$stmt = $pdo->prepare("
    SELECT p.*, 
           CONCAT(m.first_name, ' ', m.last_name) as manager_name, m.avatar as manager_avatar,
           c.name as customer_name
    FROM projects p
    LEFT JOIN users m ON p.manager_id = m.id
    LEFT JOIN customers c ON p.customer_id = c.id
    WHERE p.id = ?
");
$stmt->execute([$project_id]);
$project = $stmt->fetch();

if (!$project) {
    header('Location: projects.php');
    exit();
}

// 2. Stats & Tasks
$task_stats = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status != 'completed' AND due_date < NOW() THEN 1 ELSE 0 END) as overdue
    FROM tasks WHERE project_id = ?
");
$task_stats->execute([$project_id]);
$t_stats = $task_stats->fetch();
$completion_rate = $t_stats['total'] > 0 ? round(($t_stats['completed'] / $t_stats['total']) * 100) : 0;

// 3. Members
$members = $pdo->prepare("
    SELECT pm.*, CONCAT(u.first_name, ' ', u.last_name) as name, u.avatar 
    FROM project_members pm
    JOIN users u ON pm.user_id = u.id
    WHERE pm.project_id = ?
");
$members->execute([$project_id]);
$team = $members->fetchAll();

// 4. Milestones
$milestones = $pdo->prepare("SELECT * FROM project_milestones WHERE project_id = ? ORDER BY due_date ASC");
$milestones->execute([$project_id]);
$roadmap = $milestones->fetchAll();

// 5. Recent Tasks
$tasks_query = $pdo->prepare("
    SELECT t.*, CONCAT(u.first_name, ' ', u.last_name) as assignee 
    FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id 
    WHERE t.project_id = ? ORDER BY t.created_at DESC LIMIT 10
");
$tasks_query->execute([$project_id]);
$recent_tasks = $tasks_query->fetchAll();

// 6. Comments
$comments_query = $pdo->prepare("
    SELECT c.*, CONCAT(u.first_name, ' ', u.last_name) as user_name 
    FROM comments c JOIN users u ON c.user_id = u.id 
    WHERE c.related_type = 'project' AND c.related_id = ? 
    ORDER BY c.created_at DESC
");
$comments_query->execute([$project_id]);
$comments = $comments_query->fetchAll();

// Calculate Days
$days_total = (strtotime($project['deadline']) - strtotime($project['start_date'])) / 86400;
$days_passed = (time() - strtotime($project['start_date'])) / 86400;
$time_progress = $days_total > 0 ? min(100, max(0, round(($days_passed / $days_total) * 100))) : 0;

// All Users (for modal)
$all_users = $pdo->query("SELECT id, first_name, last_name FROM users WHERE status='active'")->fetchAll();

$csrf_token = generateCSRFToken();
include __DIR__ . '/../private/header.php';
?>

<!-- ─── STYLES ────────────────────────────────────────────────────────────── -->
<style>
    :root {
        --brand: #00b0a4;
        --brand-light: #e0f2f1;
        --dark: #1e293b;
        --gray-text: #64748b;
        --gray-border: #e2e8f0;
        --bg-body: #f8fafc;
        --radius: 16px;
    }

    body { background-color: var(--bg-body); color: var(--dark); }

    /* Layout */
    .project-grid {
        display: grid;
        grid-template-columns: 2.5fr 1fr;
        gap: 24px;
        margin-top: 24px;
    }

    /* Cards */
    .app-card {
        background: white; border: 1px solid var(--gray-border);
        border-radius: var(--radius); padding: 24px; margin-bottom: 24px;
    }

    /* Header */
    .project-header-card {
        background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%);
        display: flex; justify-content: space-between; align-items: flex-start;
    }
    .project-icon-lg {
        width: 64px; height: 64px; background: var(--brand); color: white;
        border-radius: 16px; display: flex; align-items: center; justify-content: center;
        font-size: 2rem; font-weight: 800; box-shadow: 0 10px 20px rgba(0, 176, 164, 0.2);
    }
    
    /* Tabs */
    .nav-pills-custom {
        display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid var(--gray-border); padding-bottom: 16px;
    }
    .nav-link-custom {
        background: transparent; border: none; padding: 10px 20px;
        border-radius: 12px; color: var(--gray-text); font-weight: 600;
        cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 8px;
    }
    .nav-link-custom.active {
        background: var(--brand); color: white; box-shadow: 0 4px 12px rgba(0, 176, 164, 0.3);
    }
    .nav-link-custom:hover:not(.active) { background: #e2e8f0; color: var(--dark); }

    /* Timeline (Milestones) */
    .timeline { position: relative; padding-left: 20px; border-right: 2px solid #e2e8f0; margin-right: 10px; }
    .timeline-item { position: relative; margin-bottom: 30px; padding-right: 24px; }
    .timeline-dot {
        width: 16px; height: 16px; background: white; border: 3px solid var(--brand);
        border-radius: 50%; position: absolute; right: -9px; top: 4px;
    }
    .timeline-date { font-size: 0.8rem; color: var(--gray-text); margin-bottom: 4px; }
    .timeline-title { font-weight: 700; font-size: 1rem; color: var(--dark); }

    /* SVG Donut Chart */
    .donut-chart { position: relative; width: 120px; height: 120px; }
    .donut-ring { fill: none; stroke: #e2e8f0; stroke-width: 8; }
    .donut-segment {
        fill: none; stroke: var(--brand); stroke-width: 8; stroke-linecap: round;
        stroke-dasharray: 0 100; transition: stroke-dasharray 1s ease;
    }
    .donut-text {
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
        text-align: center;
    }
    
    /* Mini Task List */
    .task-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: 12px; border-bottom: 1px solid #f1f5f9; transition: 0.2s;
    }
    .task-row:hover { background: #f8fafc; }
    .task-row:last-child { border-bottom: none; }

    /* Team Grid */
    .team-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 16px; }
    .member-card {
        border: 1px solid var(--gray-border); border-radius: 12px; padding: 16px;
        text-align: center; transition: 0.2s;
    }
    .member-card:hover { border-color: var(--brand); background: #f0fdfa; }
    .member-avatar {
        width: 48px; height: 48px; background: #e2e8f0; border-radius: 50%;
        margin: 0 auto 10px; display: flex; align-items: center; justify-content: center;
        font-weight: bold; color: var(--gray-text);
    }

    /* Badges */
    .badge-soft { padding: 6px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; }
    .badge-in_progress { background: #e0f2fe; color: #0369a1; }
    .badge-completed { background: #dcfce7; color: #15803d; }
    .badge-urgent { background: #fee2e2; color: #b91c1c; }

    /* Utility */
    .text-brand { color: var(--brand); }
    .bg-brand-soft { background: var(--brand-light); }
    .btn-icon { background: none; border: 1px solid var(--gray-border); border-radius: 8px; padding: 8px; color: var(--gray-text); cursor: pointer; }
    .btn-icon:hover { border-color: var(--brand); color: var(--brand); }
    .btn-brand { background: var(--brand); color: white; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }

    @media (max-width: 900px) {
        .project-grid { grid-template-columns: 1fr; }
    }
</style>

<!-- ─── HEADER AREA ───────────────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="projects.php" class="btn btn-light border p-2 rounded-3 text-muted">
            <?php echo $icons['arrow-right']; ?>
        </a>
        <div>
            <h4 class="mb-0 fw-bold"><?php echo htmlspecialchars($project['title']); ?></h4>
            <div class="d-flex gap-2 align-items-center text-muted small mt-1">
                <span><?php echo $project['project_code']; ?></span>
                <span>•</span>
                <span><?php echo $project['customer_name'] ?? 'بدون مشتری'; ?></span>
            </div>
        </div>
    </div>
    
    <div class="d-flex gap-2">
        <form method="POST" class="d-inline">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <select name="status" class="form-select d-inline-block w-auto" onchange="this.form.submit()">
                <option value="in_progress" <?php echo $project['status'] == 'in_progress' ? 'selected' : ''; ?>>در حال انجام</option>
                <option value="completed" <?php echo $project['status'] == 'completed' ? 'selected' : ''; ?>>تکمیل شده</option>
                <option value="on_hold" <?php echo $project['status'] == 'on_hold' ? 'selected' : ''; ?>>متوقف</option>
            </select>
        </form>
        
        <?php if (hasPermission('edit_project')): ?>
            <a href="project_form.php?id=<?php echo $project_id; ?>" class="btn btn-light border fw-bold">
                <?php echo $icons['edit']; ?> <span class="d-none d-md-inline ms-1">ویرایش</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="project-grid">

    <!-- ─── LEFT COLUMN: MAIN CONTENT ────────────────────────────────────── -->
    <div class="left-col">
        
        <!-- Header Card -->
        <div class="app-card project-header-card">
            <div class="d-flex gap-3">
                <div class="project-icon-lg">
                    <?php echo mb_substr($project['title'], 0, 1); ?>
                </div>
                <div>
                    <h5 class="fw-bold mb-2">توضیحات پروژه</h5>
                    <p class="text-secondary mb-0" style="line-height: 1.6; max-width: 600px;">
                        <?php echo $project['description'] ? nl2br(htmlspecialchars($project['description'])) : 'توضیحاتی ثبت نشده است.'; ?>
                    </p>
                    <div class="mt-3">
                        <?php 
                        $tags = explode(',', $project['tags']);
                        foreach($tags as $tag): if(trim($tag)): ?>
                            <span class="badge bg-white text-dark border me-1">#<?php echo trim($tag); ?></span>
                        <?php endif; endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Dynamic Health/Progress Circle -->
            <div class="d-flex flex-column align-items-center">
                <div class="donut-chart">
                    <svg viewBox="0 0 36 36" class="w-100 h-100">
                        <path class="donut-ring" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="donut-segment" stroke-dasharray="<?php echo $completion_rate; ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <div class="donut-text">
                        <div class="fw-bold fs-4"><?php echo $completion_rate; ?>%</div>
                        <div class="small text-muted" style="font-size: 0.6rem;">پیشرفت</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="nav-pills-custom">
            <button class="nav-link-custom active" onclick="switchTab('overview')"><?php echo $icons['layers']; ?> نمای کلی</button>
            <button class="nav-link-custom" onclick="switchTab('tasks')"><?php echo $icons['check-circle']; ?> وظایف</button>
            <button class="nav-link-custom" onclick="switchTab('milestones')"><?php echo $icons['flag']; ?> مایل‌ستون‌ها</button>
            <button class="nav-link-custom" onclick="switchTab('team')"><?php echo $icons['users']; ?> تیم پروژه</button>
            <button class="nav-link-custom" onclick="switchTab('files')"><?php echo $icons['paperclip']; ?> فایل‌ها</button>
        </div>

        <!-- ─── TAB 1: OVERVIEW ──────────────────────────────────────────── -->
        <div id="tab-overview" class="tab-content">
            <div class="row g-3 mb-4">
                <!-- Stats Cards -->
                <div class="col-md-4">
                    <div class="app-card h-100 d-flex flex-column justify-content-center">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-brand-soft text-brand p-3 rounded-3"><?php echo $icons['check-circle']; ?></div>
                            <div>
                                <h3 class="mb-0 fw-bold"><?php echo $t_stats['total']; ?></h3>
                                <div class="text-muted small">کل وظایف</div>
                            </div>
                        </div>
                        <div class="mt-3 small">
                            <span class="text-success fw-bold"><?php echo $t_stats['completed']; ?></span> تکمیل شده • 
                            <span class="text-danger fw-bold"><?php echo $t_stats['overdue']; ?></span> تاخیر
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="app-card h-100">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-warning-subtle text-warning p-3 rounded-3"><?php echo $icons['clock']; ?></div>
                            <div>
                                <h5 class="mb-0 fw-bold">زمان‌بندی</h5>
                                <div class="text-muted small"><?php echo $days_total > 0 ? round($days_passed) . ' روز گذشته' : '-'; ?></div>
                            </div>
                        </div>
                        <div class="progress mt-3" style="height: 6px;">
                            <div class="progress-bar bg-warning" style="width: <?php echo $time_progress; ?>%"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-2 small text-muted">
                            <span>شروع: <?php echo formatPersianDate($project['start_date']); ?></span>
                            <span>پایان: <?php echo formatPersianDate($project['deadline']); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="app-card h-100">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-success-subtle text-success p-3 rounded-3"><?php echo $icons['dollar']; ?></div>
                            <div>
                                <h5 class="mb-0 fw-bold">بودجه پروژه</h5>
                                <div class="text-muted small">مدیریت هزینه</div>
                            </div>
                        </div>
                        <h4 class="mt-3 fw-bold text-dark"><?php echo formatMoney($project['budget']); ?> <span class="fs-6 text-muted">تومان</span></h4>
                        <div class="small text-muted">بودجه تخصیص یافته</div>
                    </div>
                </div>
            </div>

            <!-- Recent Comments -->
            <div class="app-card">
                <h6 class="fw-bold mb-3 border-bottom pb-2"><?php echo $icons['message']; ?> آخرین گفتگوها</h6>
                <div class="comments-list" style="max-height: 300px; overflow-y: auto;">
                    <?php foreach($comments as $cm): ?>
                        <div class="d-flex gap-3 mb-3">
                            <div class="flex-shrink-0" style="width: 40px; height: 40px; background: #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                <?php echo mb_substr($cm['user_name'], 0, 1); ?>
                            </div>
                            <div class="bg-light p-3 rounded-3 flex-grow-1">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="fw-bold small"><?php echo $cm['user_name']; ?></span>
                                    <span class="text-muted" style="font-size: 0.7rem;"><?php echo formatPersianDate($cm['created_at']); ?></span>
                                </div>
                                <p class="mb-0 small text-dark"><?php echo nl2br(htmlspecialchars($cm['body'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <form method="POST" class="mt-3 d-flex gap-2">
                    <input type="hidden" name="action" value="add_comment">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="text" name="comment" class="form-control" placeholder="نوشتن پیام جدید..." required>
                    <button class="btn btn-primary px-3">ارسال</button>
                </form>
            </div>
        </div>

        <!-- ─── TAB 2: TASKS ─────────────────────────────────────────────── -->
        <div id="tab-tasks" class="tab-content" style="display: none;">
            <div class="app-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">لیست وظایف</h5>
                    <a href="task_form.php?project_id=<?php echo $project_id; ?>" class="btn-brand">
                        <?php echo $icons['plus']; ?> وظیفه جدید
                    </a>
                </div>

                <?php if (empty($recent_tasks)): ?>
                    <div class="text-center text-muted py-4">هنوز وظیفه‌ای تعریف نشده است.</div>
                <?php else: ?>
                    <div class="task-list">
                        <?php foreach($recent_tasks as $task): 
                             $is_done = $task['status'] == 'completed';
                        ?>
                            <div class="task-row">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle border d-flex align-items-center justify-content-center <?php echo $is_done ? 'bg-success border-success text-white' : ''; ?>" style="width: 24px; height: 24px;">
                                        <?php if($is_done) echo '<i class="fas fa-check" style="font-size: 10px;"></i>'; ?>
                                    </div>
                                    <div>
                                        <a href="task_view.php?id=<?php echo $task['id']; ?>" class="text-decoration-none text-dark fw-bold <?php echo $is_done ? 'text-decoration-line-through text-muted' : ''; ?>">
                                            <?php echo htmlspecialchars($task['title']); ?>
                                        </a>
                                        <div class="small text-muted">مسئول: <?php echo $task['assignee'] ?: 'تعیین نشده'; ?></div>
                                    </div>
                                </div>
                                <div>
                                    <span class="badge-soft badge-<?php echo $task['status']; ?>"><?php echo getStatusTitle($task['status'], 'task'); ?></span>
                                    <span class="text-muted small ms-2"><?php echo $task['due_date'] ? formatPersianDate($task['due_date']) : ''; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="tasks.php?project_id=<?php echo $project_id; ?>" class="btn btn-sm btn-light border">مشاهده همه وظایف</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ─── TAB 3: MILESTONES ────────────────────────────────────────── -->
        <div id="tab-milestones" class="tab-content" style="display: none;">
            <div class="app-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">نقشه راه پروژه</h5>
                    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#addMilestoneModal"><?php echo $icons['plus']; ?> افزودن فاز</button>
                </div>
                
                <div class="timeline">
                    <?php if(empty($roadmap)): ?>
                        <p class="text-muted">هیچ مایل‌استونی تعریف نشده است.</p>
                    <?php else: ?>
                        <?php foreach($roadmap as $ms): 
                             $ms_passed = strtotime($ms['due_date']) < time();
                        ?>
                            <div class="timeline-item">
                                <div class="timeline-dot <?php echo $ms_passed ? 'bg-secondary' : 'bg-white'; ?>"></div>
                                <div class="timeline-date <?php echo $ms_passed ? 'text-danger' : ''; ?>">
                                    <?php echo formatPersianDate($ms['due_date']); ?>
                                </div>
                                <div class="timeline-title"><?php echo htmlspecialchars($ms['title']); ?></div>
                                <div class="small text-muted"><?php echo htmlspecialchars($ms['description'] ?? ''); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ─── TAB 4: TEAM ──────────────────────────────────────────────── -->
        <div id="tab-team" class="tab-content" style="display: none;">
            <div class="app-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">اعضای تیم</h5>
                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addMemberModal"><?php echo $icons['plus']; ?> افزودن عضو</button>
                </div>
                
                <div class="team-grid">
                    <!-- Manager -->
                    <div class="member-card border-primary bg-primary-subtle">
                        <div class="member-avatar bg-white text-primary border border-primary">
                            <?php echo mb_substr($project['manager_name'], 0, 1); ?>
                        </div>
                        <div class="fw-bold text-dark small"><?php echo $project['manager_name']; ?></div>
                        <div class="text-primary small" style="font-size: 0.75rem;">مدیر پروژه</div>
                    </div>

                    <!-- Members -->
                    <?php foreach($team as $member): if($member['user_id'] == $project['manager_id']) continue; ?>
                        <div class="member-card">
                            <div class="member-avatar">
                                <?php echo mb_substr($member['name'], 0, 1); ?>
                            </div>
                            <div class="fw-bold text-dark small"><?php echo $member['name']; ?></div>
                            <div class="text-muted small" style="font-size: 0.75rem;"><?php echo $member['role']; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- ─── TAB 5: FILES ─────────────────────────────────────────────── -->
        <div id="tab-files" class="tab-content" style="display: none;">
            <div class="app-card text-center py-5">
                <div class="text-muted mb-3" style="font-size: 3rem; opacity: 0.3;">
                    <?php echo $icons['paperclip']; ?>
                </div>
                <h5>مدیریت فایل‌ها</h5>
                <p class="text-muted small">برای مشاهده و مدیریت فایل‌های پروژه به بخش فایل‌ها مراجعه کنید.</p>
                <a href="files.php?project_id=<?php echo $project_id; ?>" class="btn btn-outline-primary">ورود به فایل‌ها</a>
            </div>
        </div>

    </div>

    <!-- ─── RIGHT COLUMN: SIDEBAR ────────────────────────────────────────── -->
    <div class="right-col">
        <div class="app-card">
            <h6 class="fw-bold border-bottom pb-2 mb-3">اطلاعات کلیدی</h6>
            
            <div class="mb-3">
                <label class="small text-muted d-block">مدیر پروژه</label>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; font-weight: bold;">
                        <?php echo mb_substr($project['manager_name'], 0, 1); ?>
                    </div>
                    <span class="fw-bold small"><?php echo $project['manager_name']; ?></span>
                </div>
            </div>

            <div class="mb-3">
                <label class="small text-muted d-block">مشتری / کارفرما</label>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <?php if($project['customer_name']): ?>
                        <span class="text-dark fw-bold small"><?php echo $project['customer_name']; ?></span>
                    <?php else: ?>
                        <span class="text-muted small fst-italic">تعیین نشده</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mb-3">
                <label class="small text-muted d-block">اولویت</label>
                <span class="badge badge-soft badge-<?php echo $project['priority']; ?> mt-1">
                    <?php echo getPriorityTitle($project['priority']); ?>
                </span>
            </div>

            <div class="mb-3">
                <label class="small text-muted d-block">تاریخ ایجاد</label>
                <span class="small text-dark fw-bold"><?php echo formatPersianDate($project['created_at']); ?></span>
            </div>
        </div>
    </div>

</div>

<!-- ─── MODALS ────────────────────────────────────────────────────────────── -->

<!-- Add Milestone Modal -->
<div class="modal fade" id="addMilestoneModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add_milestone">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">افزودن مایل‌ستون جدید</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">عنوان فاز / هدف</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تاریخ سررسید</label>
                        <input type="date" name="due_date" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">ذخیره</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add_member">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">افزودن عضو به تیم</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">انتخاب کاربر</label>
                        <select name="user_id" class="form-select" required>
                            <?php foreach($all_users as $u): ?>
                                <option value="<?php echo $u['id']; ?>"><?php echo $u['first_name'].' '.$u['last_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">نقش در پروژه</label>
                        <input type="text" name="role" class="form-control" placeholder="مثال: طراح، برنامه نویس..." value="عضو تیم">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">افزودن</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ─── SCRIPTS ───────────────────────────────────────────────────────────── -->
<script>
    function switchTab(tabName) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
        // Show selected
        document.getElementById('tab-' + tabName).style.display = 'block';
        
        // Update Nav State
        document.querySelectorAll('.nav-link-custom').forEach(el => el.classList.remove('active'));
        event.currentTarget.classList.add('active');
    }
</script>

<?php include __DIR__ . '/../private/footer.php'; ?>