<?php
/**
 * ══════════════════════════════════════════════════════════════════════════════
 * ReadyCRM V3.5 - ADVANCED TASKS MANAGER
 * ══════════════════════════════════════════════════════════════════════════════
 * مدیریت پیشرفته وظایف با قابلیت‌های عملیات گروهی، محاسبه پیشرفت، خروجی اکسل
 * و طراحی مدرن فلت.
 * * @version 3.5.0
 * @author Ready Studio
 * ══════════════════════════════════════════════════════════════════════════════
 */

$page_title = 'مدیریت وظایف';
$breadcrumb = [
    ['title' => 'داشبورد', 'url' => 'dashboard.php'],
    ['title' => 'لیست وظایف']
];

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/database.php';
require_once __DIR__ . '/../private/auth.php';
require_once __DIR__ . '/../private/functions.php';

// ─── AUTH CHECK ─────────────────────────────────────────────────────────────
if (!hasPermission('view_tasks')) {
    setMessage('شما دسترسی لازم برای مشاهده این صفحه را ندارید', 'error');
    header('Location: dashboard.php');
    exit();
}

// ─── ICONS REPOSITORY (MODERN SVG) ──────────────────────────────────────────
$icons = [
    'plus' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>',
    'search' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>',
    'filter' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>',
    'eye' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
    'edit' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>',
    'trash' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>',
    'check' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>',
    'clock' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>',
    'calendar' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
    'check-circle' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
    'alert-circle' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>',
    'download' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>',
    'briefcase' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>',
    'more' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>'
];

// ─── HANDLE POST ACTIONS ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (verifyCSRFToken($csrf_token)) {
        
        // 1. Single Delete
        if ($action === 'delete' && hasPermission('delete_task')) {
            $task_id = (int)$_POST['task_id'];
            $pdo->prepare("DELETE FROM tasks WHERE id = ?")->execute([$task_id]);
            logActivity($_SESSION['user_id'], 'delete_task', 'tasks', $task_id);
            setMessage('وظیفه با موفقیت حذف شد', 'success');
        }

        // 2. Quick Add
        if ($action === 'quick_add' && hasPermission('add_task')) {
            $title = sanitizeInput($_POST['quick_title']);
            $due_date = $_POST['quick_due_date'] ?: null;
            $assigned_to = (int)$_POST['quick_assigned_to'] ?: null;

            if ($title) {
                $stmt = $pdo->prepare("INSERT INTO tasks (title, due_date, assigned_to, created_by, status) VALUES (?, ?, ?, ?, 'pending')");
                $stmt->execute([$title, $due_date, $assigned_to, $_SESSION['user_id']]);
                logActivity($_SESSION['user_id'], 'create_task', 'tasks', $pdo->lastInsertId());
                setMessage('وظیفه جدید اضافه شد', 'success');
            }
        }

        // 3. Bulk Actions (Delete)
        if ($action === 'bulk_delete' && hasPermission('delete_task')) {
            $ids = explode(',', $_POST['task_ids'] ?? '');
            $ids = array_map('intval', $ids);
            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("DELETE FROM tasks WHERE id IN ($placeholders)");
                $stmt->execute($ids);
                setMessage(count($ids) . ' وظیفه با موفقیت حذف شد', 'success');
            }
        }

        // 4. Bulk Actions (Status Update)
        if ($action === 'bulk_status' && hasPermission('edit_task')) {
            $ids = explode(',', $_POST['task_ids'] ?? '');
            $new_status = $_POST['new_status'];
            $ids = array_map('intval', $ids);
            
            if (!empty($ids) && in_array($new_status, ['pending', 'in_progress', 'completed', 'cancelled'])) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $completed_at = $new_status === 'completed' ? 'NOW()' : 'NULL';
                
                // Construct query carefully
                $sql = "UPDATE tasks SET status = '$new_status', completed_at = $completed_at WHERE id IN ($placeholders)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($ids);
                setMessage(count($ids) . ' وظیفه بروزرسانی شد', 'success');
            }
        }

        // 5. Export to CSV
        if ($action === 'export') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=tasks_export_' . date('Y-m-d') . '.csv');
            $output = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 compatibility
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($output, ['ID', 'عنوان', 'وضعیت', 'اولویت', 'سررسید', 'مسئول', 'ایجاد کننده']);
            
            // Re-run the filter query (simplified for export)
            // ... (Logic assumes filters from GET are passed, for simplicity we export latest 1000)
            $stmt = $pdo->query("SELECT t.id, t.title, t.status, t.priority, t.due_date, 
                               CONCAT(u.first_name, ' ', u.last_name) as assignee,
                               CONCAT(c.first_name, ' ', c.last_name) as creator
                               FROM tasks t 
                               LEFT JOIN users u ON t.assigned_to = u.id
                               LEFT JOIN users c ON t.created_by = c.id
                               ORDER BY t.created_at DESC LIMIT 1000");
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $row['status'] = getStatusTitle($row['status'], 'task');
                $row['priority'] = getPriorityTitle($row['priority']);
                fputcsv($output, $row);
            }
            fclose($output);
            exit();
        }
    }
}

// ─── QUERY BUILDER ──────────────────────────────────────────────────────────
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$priority = $_GET['priority'] ?? '';
$assigned_to = $_GET['assigned_to'] ?? '';
$date_filter = $_GET['date_filter'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

$where = ["1=1"];
$params = [];

// Filters
if ($search) {
    $where[] = "(t.title LIKE ? OR t.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($status) {
    $where[] = "t.status = ?";
    $params[] = $status;
}
if ($priority) {
    $where[] = "t.priority = ?";
    $params[] = $priority;
}
if ($assigned_to) {
    $where[] = "t.assigned_to = ?";
    $params[] = $assigned_to;
}
if ($date_filter === 'today') {
    $where[] = "DATE(t.due_date) = CURDATE()";
} elseif ($date_filter === 'overdue') {
    $where[] = "t.due_date < NOW() AND t.status != 'completed'";
} elseif ($date_filter === 'week') {
    $where[] = "YEARWEEK(t.due_date, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($date_filter === 'my_tasks') {
    $where[] = "t.assigned_to = " . $_SESSION['user_id'];
}

// Sorting
$order_by = "t.created_at DESC";
if ($sort === 'oldest') $order_by = "t.created_at ASC";
if ($sort === 'due_asc') $order_by = "ISNULL(t.due_date), t.due_date ASC";
if ($sort === 'due_desc') $order_by = "t.due_date DESC";
if ($sort === 'priority') $order_by = "FIELD(t.priority, 'urgent', 'high', 'medium', 'low')";

$where_sql = implode(' AND ', $where);

// Fetch Stats (Advanced)
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn(),
    'my_pending' => $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'pending'")->execute([$_SESSION['user_id']]) ? $pdo->query("SELECT COUNT(*) FROM tasks WHERE assigned_to = {$_SESSION['user_id']} AND status = 'pending'")->fetchColumn() : 0,
    'urgent' => $pdo->query("SELECT COUNT(*) FROM tasks WHERE priority = 'urgent' AND status != 'completed'")->fetchColumn(),
    'overdue' => $pdo->query("SELECT COUNT(*) FROM tasks WHERE due_date < NOW() AND status != 'completed'")->fetchColumn(),
];

// Fetch Tasks with Subtask Progress
$sql = "
    SELECT t.*, 
           CONCAT(u.first_name, ' ', u.last_name) as assignee_name, 
           u.avatar as assignee_avatar,
           CASE 
                WHEN t.related_type = 'customer' THEN (SELECT CONCAT(first_name, ' ', last_name) FROM customers WHERE id = t.related_id)
                WHEN t.related_type = 'lead' THEN (SELECT CONCAT(first_name, ' ', last_name) FROM leads WHERE id = t.related_id)
           END as related_name,
           (SELECT COUNT(*) FROM task_subtasks WHERE task_id = t.id) as total_subs,
           (SELECT COUNT(*) FROM task_subtasks WHERE task_id = t.id AND is_completed = 1) as done_subs
    FROM tasks t
    LEFT JOIN users u ON t.assigned_to = u.id
    WHERE $where_sql
    ORDER BY $order_by
    LIMIT $limit OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// Pagination count
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks t WHERE $where_sql");
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

$users = $pdo->query("SELECT id, first_name, last_name FROM users WHERE status = 'active'")->fetchAll();
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
    }

    body { background-color: var(--bg-light); color: var(--dark); }

    /* Stats Cards - Modern Flat */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        border: 1px solid var(--gray-border);
        border-radius: var(--radius-card);
        padding: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: transform 0.2s, border-color 0.2s;
        position: relative;
        overflow: hidden;
    }
    .stat-card:hover { border-color: var(--brand); transform: translateY(-2px); }
    .stat-card::after {
        content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%;
        background: var(--brand); opacity: 0; transition: 0.2s;
    }
    .stat-card:hover::after { opacity: 1; }

    .stat-data h3 { font-size: 1.8rem; font-weight: 800; margin: 0; line-height: 1; color: var(--dark); }
    .stat-data p { margin: 8px 0 0; color: var(--gray-text); font-size: 0.9rem; font-weight: 500; }
    .stat-icon {
        width: 56px; height: 56px; border-radius: 16px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem;
    }

    /* Filters Bar */
    .filter-card {
        background: white; border: 1px solid var(--gray-border);
        border-radius: var(--radius-card); padding: 20px; margin-bottom: 24px;
    }
    .filter-row { display: flex; flex-wrap: wrap; gap: 16px; align-items: center; }
    .search-wrapper { flex-grow: 1; min-width: 250px; position: relative; }
    .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--gray-text); }
    
    .form-control-custom {
        width: 100%; padding: 10px 16px 10px 40px;
        border: 1px solid var(--gray-border); border-radius: var(--radius-elem);
        font-size: 0.95rem; outline: none; transition: 0.2s;
    }
    .form-select-custom {
        padding: 10px 36px 10px 16px; /* RTL padding */
        border: 1px solid var(--gray-border); border-radius: var(--radius-elem);
        font-size: 0.9rem; outline: none; background-color: white; cursor: pointer;
    }
    .form-control-custom:focus, .form-select-custom:focus {
        border-color: var(--brand); box-shadow: 0 0 0 3px rgba(0, 176, 164, 0.1);
    }

    /* Bulk Actions Bar */
    .bulk-actions {
        background: var(--brand); color: white;
        padding: 12px 24px; border-radius: var(--radius-elem);
        display: none; align-items: center; justify-content: space-between;
        margin-bottom: 20px; animation: slideDown 0.3s ease;
    }
    @keyframes slideDown { from { transform: translateY(-10px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

    /* Table */
    .table-card {
        background: white; border: 1px solid var(--gray-border);
        border-radius: var(--radius-card); overflow: hidden;
    }
    .custom-table { width: 100%; border-collapse: collapse; }
    .custom-table th {
        background: #f8fafc; padding: 16px; text-align: right;
        font-weight: 600; color: var(--gray-text); font-size: 0.85rem;
        border-bottom: 1px solid var(--gray-border);
    }
    .custom-table td {
        padding: 16px; border-bottom: 1px solid var(--gray-border);
        vertical-align: middle; font-size: 0.95rem; transition: background 0.1s;
    }
    .custom-table tr:hover td { background: #fafafa; }
    .custom-table tr:last-child td { border-bottom: none; }

    /* Progress Bar */
    .progress-mini {
        height: 6px; width: 100px; background: #e2e8f0; border-radius: 10px; overflow: hidden; margin-top: 6px;
    }
    .progress-bar-mini { height: 100%; background: var(--brand); transition: width 0.3s; }

    /* Avatar Group */
    .avatar-circle {
        width: 32px; height: 32px; border-radius: 50%;
        background: #f1f5f9; color: var(--gray-text);
        display: flex; align-items: center; justify-content: center;
        font-size: 0.75rem; font-weight: 700; border: 2px solid white;
    }

    /* Badges */
    .badge-modern {
        padding: 5px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600;
        display: inline-flex; align-items: center; gap: 4px;
    }
    .badge-pending { background: #fff7ed; color: #c2410c; }
    .badge-progress { background: #eff6ff; color: #1d4ed8; }
    .badge-completed { background: #f0fdf4; color: #15803d; }
    .badge-overdue { background: #fef2f2; color: #b91c1c; }
    .badge-priority-urgent { background: #fee2e2; color: #ef4444; }
    .badge-priority-high { background: #ffedd5; color: #f97316; }

    /* Custom Checkbox */
    .check-custom {
        width: 18px; height: 18px; border: 2px solid #cbd5e1;
        border-radius: 4px; cursor: pointer; display: flex; align-items: center;
        justify-content: center; transition: 0.2s;
    }
    .check-custom.checked { background: var(--brand); border-color: var(--brand); }
    .check-custom.checked svg { display: block; }
    .check-custom svg { display: none; color: white; width: 12px; height: 12px; }

    /* Pagination */
    .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 24px; }
    .page-btn {
        width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;
        background: white; border: 1px solid var(--gray-border); border-radius: 8px;
        color: var(--dark); text-decoration: none; transition: 0.2s;
    }
    .page-btn.active { background: var(--brand); color: white; border-color: var(--brand); }
    .page-btn:hover:not(.active) { border-color: var(--brand); color: var(--brand); }

    /* Modal Styling */
    .modal-content { border-radius: var(--radius-card); border: none; }
    .modal-header { border-bottom: 1px solid var(--gray-border); padding: 20px; }
    .modal-body { padding: 24px; }
    .modal-footer { border-top: 1px solid var(--gray-border); padding: 16px 24px; }

    /* Buttons */
    .btn-icon { background: none; border: none; color: var(--gray-text); cursor: pointer; padding: 6px; border-radius: 6px; transition: 0.2s; }
    .btn-icon:hover { background: #f1f5f9; color: var(--brand); }
    .btn-icon.delete:hover { color: #ef4444; background: #fef2f2; }

    .btn-primary-custom {
        background: var(--brand); color: white; border: none; padding: 10px 20px;
        border-radius: var(--radius-elem); font-weight: 600; text-decoration: none;
        display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; cursor: pointer;
    }
    .btn-primary-custom:hover { background: var(--brand-hover); }

    .btn-outline-custom {
        background: white; border: 1px solid var(--gray-border); color: var(--dark);
        padding: 10px 16px; border-radius: var(--radius-elem); font-weight: 500;
        text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; cursor: pointer;
    }
    .btn-outline-custom:hover { border-color: var(--brand); color: var(--brand); }

    @media (max-width: 768px) {
        .filter-row { flex-direction: column; align-items: stretch; }
        .stats-grid { grid-template-columns: 1fr 1fr; }
        .table-responsive { overflow-x: auto; }
    }
</style>

<!-- ─── STATS DASHBOARD ───────────────────────────────────────────────────── -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-data">
            <h3><?php echo number_format($stats['total']); ?></h3>
            <p>کل وظایف سیستم</p>
        </div>
        <div class="stat-icon" style="background: #e0f2fe; color: #0369a1;">
            <?php echo $icons['briefcase']; ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-data">
            <h3><?php echo number_format($stats['my_pending']); ?></h3>
            <p>وظایف باز من</p>
        </div>
        <div class="stat-icon" style="background: var(--brand-soft); color: var(--brand);">
            <?php echo $icons['user']; ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-data">
            <h3><?php echo number_format($stats['urgent']); ?></h3>
            <p>اولویت فوری</p>
        </div>
        <div class="stat-icon" style="background: #ffedd5; color: #c2410c;">
            <?php echo $icons['alert-circle']; ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-data">
            <h3><?php echo number_format($stats['overdue']); ?></h3>
            <p>عقب افتاده</p>
        </div>
        <div class="stat-icon" style="background: #fee2e2; color: #b91c1c;">
            <?php echo $icons['clock']; ?>
        </div>
    </div>
</div>

<!-- ─── HEADER & ACTIONS ──────────────────────────────────────────────────── -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h4 class="mb-1 fw-bold">مدیریت وظایف</h4>
        <p class="text-muted small mb-0">سازماندهی و پیگیری فعالیت‌های تیمی</p>
    </div>
    <div class="d-flex gap-2">
        <form method="POST" id="exportForm">
            <input type="hidden" name="action" value="export">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <!-- Include current filters in export (simplified) -->
            <button type="submit" class="btn-outline-custom">
                <?php echo $icons['download']; ?> خروجی اکسل
            </button>
        </form>
        <?php if (hasPermission('add_task')): ?>
            <button type="button" class="btn-outline-custom" data-bs-toggle="modal" data-bs-target="#quickAddModal">
                <?php echo $icons['plus']; ?> سریع
            </button>
            <a href="task_form.php" class="btn-primary-custom">
                <?php echo $icons['plus']; ?> وظیفه جدید
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- ─── FILTERS ───────────────────────────────────────────────────────────── -->
<div class="filter-card">
    <form method="GET" class="filter-row">
        <div class="search-wrapper">
            <span class="search-icon"><?php echo $icons['search']; ?></span>
            <input type="text" name="search" class="form-control-custom" placeholder="جستجو در عنوان، توضیحات..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        
        <select name="date_filter" class="form-select-custom">
            <option value="">همه زمان‌ها</option>
            <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>امروز</option>
            <option value="week" <?php echo $date_filter === 'week' ? 'selected' : ''; ?>>این هفته</option>
            <option value="overdue" <?php echo $date_filter === 'overdue' ? 'selected' : ''; ?>>عقب افتاده</option>
            <option value="my_tasks" <?php echo $date_filter === 'my_tasks' ? 'selected' : ''; ?>>وظایف من</option>
        </select>

        <select name="status" class="form-select-custom">
            <option value="">همه وضعیت‌ها</option>
            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>در انتظار</option>
            <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>در حال انجام</option>
            <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>تکمیل شده</option>
        </select>

        <select name="assigned_to" class="form-select-custom">
            <option value="">همه کاربران</option>
            <?php foreach ($users as $u): ?>
                <option value="<?php echo $u['id']; ?>" <?php echo $assigned_to == $u['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="sort" class="form-select-custom">
            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>جدیدترین</option>
            <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>قدیمی‌ترین</option>
            <option value="due_asc" <?php echo $sort === 'due_asc' ? 'selected' : ''; ?>>نزدیک‌ترین سررسید</option>
            <option value="priority" <?php echo $sort === 'priority' ? 'selected' : ''; ?>>اولویت بالا</option>
        </select>

        <button type="submit" class="btn-primary-custom" style="min-width: 100px; justify-content: center;">
            <?php echo $icons['filter']; ?> فیلتر
        </button>
        <?php if($search || $status || $priority || $assigned_to || $date_filter): ?>
            <a href="tasks.php" class="btn-outline-custom text-danger border-danger">حذف فیلتر</a>
        <?php endif; ?>
    </form>
</div>

<!-- ─── BULK ACTIONS ──────────────────────────────────────────────────────── -->
<div class="bulk-actions" id="bulkActionsBar">
    <div class="d-flex align-items-center gap-3">
        <span class="fw-bold"><span id="selectedCount">0</span> وظیفه انتخاب شده</span>
        <div class="vr" style="background: rgba(255,255,255,0.3); height: 20px;"></div>
        
        <form method="POST" id="bulkDeleteForm" style="display:inline;">
            <input type="hidden" name="action" value="bulk_delete">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="task_ids" id="bulkDeleteIds">
            <button type="button" onclick="confirmBulkDelete()" class="btn text-white btn-sm p-0 d-flex align-items-center gap-1">
                <?php echo $icons['trash']; ?> حذف گروهی
            </button>
        </form>
    </div>
    
    <div class="d-flex align-items-center gap-2">
        <span class="small opacity-75">تغییر وضعیت به:</span>
        <form method="POST" id="bulkStatusForm" class="d-flex gap-1">
            <input type="hidden" name="action" value="bulk_status">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="task_ids" id="bulkStatusIds">
            
            <button type="submit" name="new_status" value="completed" class="btn btn-sm btn-light text-success border-0">تکمیل</button>
            <button type="submit" name="new_status" value="in_progress" class="btn btn-sm btn-light text-primary border-0">در حال انجام</button>
        </form>
    </div>
</div>

<!-- ─── TASKS TABLE ───────────────────────────────────────────────────────── -->
<div class="table-card">
    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th style="width: 40px;">
                        <div class="check-custom" id="checkAll" onclick="toggleAllCheckboxes()">
                            <?php echo $icons['check']; ?>
                        </div>
                    </th>
                    <th style="min-width: 250px;">عنوان و پیشرفت</th>
                    <th>وضعیت</th>
                    <th>اولویت</th>
                    <th>سررسید</th>
                    <th>مسئول</th>
                    <th>مرتبط با</th>
                    <th class="text-center">عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tasks)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="opacity-50 mb-3" style="color: var(--gray-text);">
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="9" y1="15" x2="15" y2="15"></line></svg>
                            </div>
                            <p class="text-muted">هیچ وظیفه‌ای یافت نشد.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tasks as $task): 
                        $is_overdue = $task['due_date'] && strtotime($task['due_date']) < time() && $task['status'] !== 'completed';
                        $progress_pct = $task['total_subs'] > 0 ? round(($task['done_subs'] / $task['total_subs']) * 100) : ($task['status'] === 'completed' ? 100 : 0);
                    ?>
                        <tr>
                            <td>
                                <div class="check-custom task-checkbox" data-id="<?php echo $task['id']; ?>" onclick="toggleRowCheckbox(this)">
                                    <?php echo $icons['check']; ?>
                                </div>
                            </td>
                            <td>
                                <a href="task_view.php?id=<?php echo $task['id']; ?>" class="d-block text-dark fw-bold text-decoration-none mb-1">
                                    <?php echo htmlspecialchars($task['title']); ?>
                                </a>
                                <?php if ($task['total_subs'] > 0): ?>
                                    <div class="d-flex align-items-center gap-2 small text-muted">
                                        <div class="progress-mini flex-grow-1" style="max-width: 60px;">
                                            <div class="progress-bar-mini" style="width: <?php echo $progress_pct; ?>%"></div>
                                        </div>
                                        <span style="font-size: 0.75rem;"><?php echo $task['done_subs'].'/'.$task['total_subs']; ?></span>
                                    </div>
                                <?php else: ?>
                                    <span class="small text-muted" style="font-size: 0.8rem;">بدون چک‌لیست</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge-modern badge-<?php echo $task['status']; ?>">
                                    <?php echo getStatusTitle($task['status'], 'task'); ?>
                                </span>
                            </td>
                            <td>
                                <?php if($task['priority'] === 'urgent' || $task['priority'] === 'high'): ?>
                                    <span class="badge-modern badge-priority-<?php echo $task['priority']; ?>">
                                        <?php echo getPriorityTitle($task['priority']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small"><?php echo getPriorityTitle($task['priority']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($task['due_date']): ?>
                                    <div class="d-flex align-items-center gap-1 <?php echo $is_overdue ? 'badge-modern badge-overdue' : 'text-muted small'; ?>">
                                        <?php if($is_overdue) echo $icons['alert-circle']; ?>
                                        <?php echo formatPersianDate($task['due_date'], 'Y/m/d'); ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($task['assignee_name']): ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-circle" title="<?php echo htmlspecialchars($task['assignee_name']); ?>">
                                            <?php echo mb_substr($task['assignee_name'], 0, 1); ?>
                                        </div>
                                        <span class="d-none d-lg-block small"><?php echo htmlspecialchars($task['assignee_name']); ?></span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">---</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($task['related_name']): ?>
                                    <a href="<?php echo $task['related_type']; ?>_view.php?id=<?php echo $task['related_id']; ?>" class="badge-modern" style="background: #f1f5f9; color: var(--dark); text-decoration: none;">
                                        <?php echo $task['related_type'] === 'customer' ? 'مشتری' : 'لید'; ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="task_view.php?id=<?php echo $task['id']; ?>" class="btn-icon" title="مشاهده">
                                        <?php echo $icons['eye']; ?>
                                    </a>
                                    <?php if (hasPermission('edit_task')): ?>
                                        <a href="task_form.php?id=<?php echo $task['id']; ?>" class="btn-icon" title="ویرایش">
                                            <?php echo $icons['edit']; ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (hasPermission('delete_task')): ?>
                                        <button onclick="confirmDelete(<?php echo $task['id']; ?>)" class="btn-icon delete" title="حذف">
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

<!-- PAGINATION -->
<?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>&status=<?php echo $status; ?>&sort=<?php echo $sort; ?>" 
               class="page-btn <?php echo $i === $page ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<!-- ─── QUICK ADD MODAL ───────────────────────────────────────────────────── -->
<div class="modal fade" id="quickAddModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="quick_add">
                
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">افزودن سریع وظیفه</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">عنوان وظیفه</label>
                        <input type="text" name="quick_title" class="form-control-custom" required placeholder="مثال: تماس با مشتری...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">سررسید</label>
                        <input type="datetime-local" name="quick_due_date" class="form-control-custom">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">مسئول انجام</label>
                        <select name="quick_assigned_to" class="form-select-custom w-100">
                            <option value="">خودم</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn-primary-custom">ذخیره وظیفه</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ─── SCRIPTS ───────────────────────────────────────────────────────────── -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Delete Confirmation
    function confirmDelete(id) {
        Swal.fire({
            title: 'حذف وظیفه',
            text: "آیا از حذف این مورد اطمینان دارید؟",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'بله، حذف کن',
            cancelButtonText: 'لغو'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="task_id" value="${id}"><input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">`;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    // Bulk Action Logic
    let selectedIds = [];
    const bulkBar = document.getElementById('bulkActionsBar');
    const countSpan = document.getElementById('selectedCount');

    function toggleRowCheckbox(el) {
        el.classList.toggle('checked');
        const id = el.dataset.id;
        if (el.classList.contains('checked')) {
            selectedIds.push(id);
        } else {
            selectedIds = selectedIds.filter(item => item !== id);
        }
        updateBulkUI();
    }

    function toggleAllCheckboxes() {
        const master = document.getElementById('checkAll');
        master.classList.toggle('checked');
        const isChecked = master.classList.contains('checked');
        
        const boxes = document.querySelectorAll('.task-checkbox');
        selectedIds = [];
        
        boxes.forEach(box => {
            if(isChecked) {
                box.classList.add('checked');
                selectedIds.push(box.dataset.id);
            } else {
                box.classList.remove('checked');
            }
        });
        updateBulkUI();
    }

    function updateBulkUI() {
        countSpan.innerText = selectedIds.length;
        if(selectedIds.length > 0) {
            bulkBar.style.display = 'flex';
            document.getElementById('bulkDeleteIds').value = selectedIds.join(',');
            document.getElementById('bulkStatusIds').value = selectedIds.join(',');
        } else {
            bulkBar.style.display = 'none';
        }
    }

    function confirmBulkDelete() {
        Swal.fire({
            title: 'حذف گروهی',
            text: `آیا مطمئن هستید که می‌خواهید ${selectedIds.length} وظیفه را حذف کنید؟`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'بله، حذف همه',
            cancelButtonText: 'لغو'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('bulkDeleteForm').submit();
            }
        });
    }
</script>

<?php include __DIR__ . '/../private/footer.php'; ?>