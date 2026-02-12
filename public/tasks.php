<?php
$page_title = 'مدیریت وظایف';
$breadcrumb = [
    ['title' => 'داشبورد', 'url' => 'dashboard.php'],
    ['title' => 'وظایف']
];

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/database.php';
require_once __DIR__ . '/../private/auth.php';
require_once __DIR__ . '/../private/functions.php';

// بررسی دسترسی
if (!hasPermission('view_tasks')) {
    setMessage('شما دسترسی لازم برای مشاهده این صفحه را ندارید', 'error');
    header('Location: dashboard.php');
    exit();
}

// پردازش درخواست‌ها
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete' && hasPermission('delete_task')) {
        $task_id = (int)$_POST['task_id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
            $stmt->execute([$task_id]);

            if ($stmt->rowCount() > 0) {
                logActivity($_SESSION['user_id'], 'delete_task', 'tasks', $task_id);
                setMessage('وظیفه با موفقیت حذف شد', 'success');
            } else {
                setMessage('وظیفه یافت نشد', 'error');
            }
        } catch (PDOException $e) {
            error_log("خطا در حذف وظیفه: " . $e->getMessage());
            setMessage('خطا در حذف وظیفه', 'error');
        }
    }

    if ($action === 'update_status' && hasPermission('edit_task')) {
        $task_id = (int)$_POST['task_id'];
        $new_status = $_POST['new_status'];

        try {
            $completed_at = $new_status === 'completed' ? 'NOW()' : 'NULL';

            $stmt = $pdo->prepare("UPDATE tasks SET status = ?, completed_at = $completed_at WHERE id = ?");
            $stmt->execute([$new_status, $task_id]);

            if ($stmt->rowCount() > 0) {
                logActivity($_SESSION['user_id'], 'update_task_status', 'tasks', $task_id, ['status' => $new_status]);
                setMessage('وضعیت وظیفه بروزرسانی شد', 'success');
            }
        } catch (PDOException $e) {
            error_log("خطا در بروزرسانی وضعیت وظیفه: " . $e->getMessage());
            setMessage('خطا در بروزرسانی وضعیت', 'error');
        }
    }

    if ($action === 'quick_add' && hasPermission('add_task')) {
        $title = sanitizeInput($_POST['quick_title']);
        $due_date = $_POST['quick_due_date'];
        $assigned_to = (int)$_POST['quick_assigned_to'];

        if ($title) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO tasks (title, due_date, assigned_to, created_by)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$title, $due_date ?: null, $assigned_to ?: null, $_SESSION['user_id']]);

                logActivity($_SESSION['user_id'], 'create_task', 'tasks', $pdo->lastInsertId());
                setMessage('وظیفه جدید اضافه شد', 'success');
            } catch (PDOException $e) {
                error_log("خطا در افزودن وظیفه: " . $e->getMessage());
                setMessage('خطا در افزودن وظیفه', 'error');
            }
        }
    }
}

// دریافت فیلترها
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$priority = $_GET['priority'] ?? '';
$type = $_GET['type'] ?? '';
$assigned_to = $_GET['assigned_to'] ?? '';
$due_filter = $_GET['due_filter'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$per_page = RECORDS_PER_PAGE;
$offset = ($page - 1) * $per_page;

// ساخت کوئری
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(t.title LIKE ? OR t.description LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term]);
}

if ($status) {
    $where_conditions[] = "t.status = ?";
    $params[] = $status;
}

if ($priority) {
    $where_conditions[] = "t.priority = ?";
    $params[] = $priority;
}

if ($type) {
    $where_conditions[] = "t.type = ?";
    $params[] = $type;
}

if ($assigned_to) {
    $where_conditions[] = "t.assigned_to = ?";
    $params[] = $assigned_to;
}

if ($due_filter) {
    switch ($due_filter) {
        case 'today':
            $where_conditions[] = "DATE(t.due_date) = CURDATE()";
            break;
        case 'tomorrow':
            $where_conditions[] = "DATE(t.due_date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)";
            break;
        case 'this_week':
            $where_conditions[] = "WEEK(t.due_date) = WEEK(CURDATE()) AND YEAR(t.due_date) = YEAR(CURDATE())";
            break;
        case 'overdue':
            $where_conditions[] = "t.due_date < NOW() AND t.status != 'completed'";
            break;
    }
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// دریافت تعداد کل رکوردها
$count_sql = "SELECT COUNT(*) FROM tasks t $where_clause";
$total_records = $pdo->prepare($count_sql);
$total_records->execute($params);
$total_records = $total_records->fetchColumn();

// دریافت وظایف
$sql = "
    SELECT
        t.*,
        CONCAT(u.first_name, ' ', u.last_name) as assigned_user,
        CONCAT(cb.first_name, ' ', cb.last_name) as created_user,
        CASE
            WHEN t.related_type = 'customer' THEN (SELECT CONCAT(first_name, ' ', last_name) FROM customers WHERE id = t.related_id)
            WHEN t.related_type = 'lead' THEN (SELECT CONCAT(first_name, ' ', last_name) FROM leads WHERE id = t.related_id)
            ELSE NULL
        END as related_name
    FROM tasks t
    LEFT JOIN users u ON t.assigned_to = u.id
    LEFT JOIN users cb ON t.created_by = cb.id
    $where_clause
    ORDER BY
        CASE t.status WHEN 'completed' THEN 3 WHEN 'cancelled' THEN 4 ELSE 1 END,
        CASE t.priority
            WHEN 'urgent' THEN 1
            WHEN 'high' THEN 2
            WHEN 'medium' THEN 3
            WHEN 'low' THEN 4
        END,
        t.due_date ASC,
        t.created_at DESC
    LIMIT $per_page OFFSET $offset
";

$tasks = $pdo->prepare($sql);
$tasks->execute($params);
$tasks = $tasks->fetchAll();

// دریافت کاربران برای فیلتر
$users = $pdo->query("SELECT id, first_name, last_name FROM users WHERE status = 'active' ORDER BY first_name")->fetchAll();

// آمار وظایف
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn(),
    'pending' => $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'pending'")->fetchColumn(),
    'in_progress' => $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'in_progress'")->fetchColumn(),
    'completed' => $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed'")->fetchColumn(),
    'overdue' => $pdo->query("SELECT COUNT(*) FROM tasks WHERE due_date < NOW() AND status NOT IN ('completed', 'cancelled')")->fetchColumn(),
];

include __DIR__ . '/../private/header.php';
?>

<style>
/* ============================================================
   TASKS PAGE SPECIFIC STYLES
   ============================================================ */

/* Stats Cards Row */
.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 1.25rem;
    margin-bottom: 2rem;
}

.stat-card-mini {
    background: var(--brand-dark-card);
    border: 1px solid var(--brand-dark-border);
    border-radius: var(--radius-lg);
    padding: 1.25rem 1rem;
    text-align: center;
    transition: var(--transition-smooth);
    position: relative;
    overflow: hidden;
}

.stat-card-mini::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--gradient-primary);
    opacity: 0;
    transition: var(--transition-base);
}

.stat-card-mini:hover::before {
    opacity: 1;
}

.stat-card-mini:hover {
    transform: translateY(-4px);
    border-color: var(--brand-primary);
    box-shadow: var(--shadow-brand);
}

.stat-card-mini .stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 0.4rem;
    color: var(--brand-primary);
}

.stat-card-mini .stat-label {
    font-size: 0.8rem;
    color: var(--text-gray-400);
    font-weight: 500;
}

.stat-card-mini.stat-warning .stat-value { color: #fbbf24; }
.stat-card-mini.stat-info .stat-value { color: #60a5fa; }
.stat-card-mini.stat-success .stat-value { color: #34d399; }
.stat-card-mini.stat-danger .stat-value { color: #f87171; }

/* Page Header */
.page-header-tasks {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.page-header-tasks .header-left h4 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-white);
    margin-bottom: 0.35rem;
}

.page-header-tasks .header-left p {
    color: var(--text-gray-400);
    font-size: 0.9rem;
    margin: 0;
}

.page-header-tasks .header-right {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

/* Filter Card */
.filter-card {
    background: var(--brand-dark-card);
    border: 1px solid var(--brand-dark-border);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    margin-bottom: 1.75rem;
}

.filter-card .form-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--text-gray-300);
    margin-bottom: 0.5rem;
    letter-spacing: 0.01em;
}

.filter-card .form-control,
.filter-card .form-select {
    background: var(--brand-dark-input);
    border: 1.5px solid var(--brand-dark-border);
    border-radius: var(--radius-md);
    color: var(--text-white);
    font-size: 0.9rem;
    padding: 0.65rem 0.85rem;
    transition: var(--transition-base);
}

.filter-card .form-control:focus,
.filter-card .form-select:focus {
    border-color: var(--brand-primary);
    box-shadow: 0 0 0 3px rgba(0, 176, 164, 0.15);
    background: var(--brand-dark);
}

.filter-card .form-control::placeholder {
    color: var(--text-gray-500);
}

.filter-card .btn-outline-primary {
    border-color: var(--brand-primary);
    color: var(--brand-primary);
}

.filter-card .btn-outline-primary:hover {
    background: var(--brand-primary);
    color: white;
}

/* Tasks Table Card */
.tasks-table-card {
    background: var(--brand-dark-card);
    border: 1px solid var(--brand-dark-border);
    border-radius: var(--radius-lg);
    overflow: hidden;
}

.tasks-table-card .card-header {
    background: linear-gradient(135deg, #0d0d0d 0%, #1a1a1a 100%);
    border-bottom: 1px solid var(--brand-dark-border);
    padding: 1.25rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.tasks-table-card .card-header h5 {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-white);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.65rem;
}

.tasks-table-card .card-header h5 i {
    color: var(--brand-primary);
    font-size: 1rem;
}

.tasks-table-card .card-header .badge {
    background: var(--brand-primary);
    color: white;
    font-size: 0.8rem;
    padding: 0.35rem 0.75rem;
    border-radius: var(--radius-full);
    font-weight: 600;
}

.tasks-table-card .btn-outline-secondary {
    border-color: var(--brand-dark-border);
    color: var(--text-gray-300);
    font-size: 0.85rem;
}

.tasks-table-card .btn-outline-secondary:hover {
    background: var(--brand-dark-input);
    border-color: var(--brand-primary);
    color: var(--brand-primary);
}

/* Table Styles */
.table-modern {
    width: 100%;
    margin: 0;
    border-collapse: separate;
    border-spacing: 0;
}

.table-modern thead th {
    background: var(--brand-dark);
    color: var(--text-gray-300);
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--brand-dark-border);
    white-space: nowrap;
}

.table-modern tbody tr {
    transition: var(--transition-base);
    border-bottom: 1px solid var(--brand-dark-border);
}

.table-modern tbody tr:last-child {
    border-bottom: none;
}

.table-modern tbody tr:hover {
    background: rgba(0, 176, 164, 0.04);
}

.table-modern tbody td {
    padding: 1rem 1.25rem;
    color: var(--text-gray-100);
    font-size: 0.9rem;
    vertical-align: middle;
}

/* Task Title Cell */
.task-title-cell {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.task-title-cell .task-main-title {
    font-weight: 600;
    color: var(--text-white);
    font-size: 0.95rem;
}

.task-title-cell .task-subtitle {
    font-size: 0.78rem;
    color: var(--text-gray-400);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.task-title-cell .task-subtitle i {
    font-size: 0.7rem;
}

/* Priority Badge */
.priority-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.35rem 0.85rem;
    border-radius: var(--radius-full);
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.02em;
}

.priority-badge.priority-urgent {
    background: rgba(239, 68, 68, 0.15);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.priority-badge.priority-high {
    background: rgba(251, 191, 36, 0.15);
    color: #fbbf24;
    border: 1px solid rgba(251, 191, 36, 0.3);
}

.priority-badge.priority-medium {
    background: rgba(96, 165, 250, 0.15);
    color: #60a5fa;
    border: 1px solid rgba(96, 165, 250, 0.3);
}

.priority-badge.priority-low {
    background: rgba(52, 211, 153, 0.15);
    color: #34d399;
    border: 1px solid rgba(52, 211, 153, 0.3);
}

.priority-badge i {
    font-size: 0.7rem;
}

/* Status Badge */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.35rem 0.85rem;
    border-radius: var(--radius-full);
    font-size: 0.75rem;
    font-weight: 600;
}

.status-badge.status-pending {
    background: rgba(251, 191, 36, 0.15);
    color: #fbbf24;
}

.status-badge.status-in_progress {
    background: rgba(96, 165, 250, 0.15);
    color: #60a5fa;
}

.status-badge.status-completed {
    background: rgba(52, 211, 153, 0.15);
    color: #34d399;
}

.status-badge.status-cancelled {
    background: rgba(156, 163, 175, 0.15);
    color: #9ca3af;
}

/* Type Badge */
.type-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.3rem 0.75rem;
    background: var(--brand-dark-input);
    border: 1px solid var(--brand-dark-border);
    border-radius: var(--radius-md);
    font-size: 0.75rem;
    color: var(--text-gray-300);
}

.type-badge i {
    color: var(--brand-primary);
    font-size: 0.7rem;
}

/* Due Date */
.due-date-cell {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.due-date-cell .due-date-main {
    font-size: 0.85rem;
    color: var(--text-white);
    font-weight: 500;
}

.due-date-cell .due-date-sub {
    font-size: 0.75rem;
    color: var(--text-gray-500);
}

.due-date-cell.overdue .due-date-main {
    color: #f87171;
}

/* User Avatar */
.user-avatar-mini {
    width: 32px;
    height: 32px;
    border-radius: var(--radius-full);
    background: var(--gradient-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.action-buttons .btn-sm {
    padding: 0.4rem 0.75rem;
    border-radius: var(--radius-md);
    font-size: 0.8rem;
    transition: var(--transition-base);
}

.action-buttons .btn-outline-info {
    border-color: #60a5fa;
    color: #60a5fa;
}

.action-buttons .btn-outline-info:hover {
    background: rgba(96, 165, 250, 0.15);
    border-color: #60a5fa;
}

.action-buttons .btn-outline-warning {
    border-color: #fbbf24;
    color: #fbbf24;
}

.action-buttons .btn-outline-warning:hover {
    background: rgba(251, 191, 36, 0.15);
    border-color: #fbbf24;
}

.action-buttons .btn-outline-danger {
    border-color: #f87171;
    color: #f87171;
}

.action-buttons .btn-outline-danger:hover {
    background: rgba(239, 68, 68, 0.15);
    border-color: #f87171;
}

/* Empty State */
.empty-state-tasks {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-state-tasks i {
    font-size: 4rem;
    color: var(--brand-dark-border);
    margin-bottom: 1.5rem;
}

.empty-state-tasks h5 {
    font-size: 1.25rem;
    color: var(--text-gray-300);
    margin-bottom: 0.75rem;
}

.empty-state-tasks p {
    color: var(--text-gray-500);
    font-size: 0.9rem;
}

/* Pagination */
.pagination-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    background: var(--brand-dark);
    border-top: 1px solid var(--brand-dark-border);
}

.pagination-info {
    font-size: 0.85rem;
    color: var(--text-gray-400);
}

/* Quick Add Modal */
.modal-content {
    background: var(--brand-dark-card);
    border: 1px solid var(--brand-dark-border);
    border-radius: var(--radius-xl);
}

.modal-header {
    background: linear-gradient(135deg, #0d0d0d 0%, #1a1a1a 100%);
    border-bottom: 1px solid var(--brand-dark-border);
    padding: 1.5rem;
}

.modal-header .modal-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--text-white);
}

.modal-header .btn-close {
    filter: invert(1);
    opacity: 0.6;
}

.modal-header .btn-close:hover {
    opacity: 1;
}

.modal-body {
    padding: 1.75rem;
}

.modal-footer {
    border-top: 1px solid var(--brand-dark-border);
    padding: 1.25rem 1.75rem;
}

/* Responsive */
@media (max-width: 768px) {
    .stats-row {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }

    .page-header-tasks {
        flex-direction: column;
        align-items: flex-start;
    }

    .table-modern {
        font-size: 0.85rem;
    }

    .table-modern thead th,
    .table-modern tbody td {
        padding: 0.75rem 0.85rem;
    }

    .action-buttons {
        flex-direction: column;
    }
}
</style>

<!-- آمار کوتاه -->
<div class="stats-row">
    <div class="stat-card-mini">
        <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
        <div class="stat-label">کل وظایف</div>
    </div>
    <div class="stat-card-mini stat-warning">
        <div class="stat-value"><?php echo number_format($stats['pending']); ?></div>
        <div class="stat-label">در انتظار</div>
    </div>
    <div class="stat-card-mini stat-info">
        <div class="stat-value"><?php echo number_format($stats['in_progress']); ?></div>
        <div class="stat-label">در حال انجام</div>
    </div>
    <div class="stat-card-mini stat-success">
        <div class="stat-value"><?php echo number_format($stats['completed']); ?></div>
        <div class="stat-label">تکمیل شده</div>
    </div>
    <div class="stat-card-mini stat-danger">
        <div class="stat-value"><?php echo number_format($stats['overdue']); ?></div>
        <div class="stat-label">عقب‌افتاده</div>
    </div>
</div>

<!-- Page Header -->
<div class="page-header-tasks">
    <div class="header-left">
        <h4>مدیریت وظایف</h4>
        <p>مشاهده و مدیریت وظایف و پیگیری‌ها</p>
    </div>

    <div class="header-right">
        <?php if (hasPermission('add_task')): ?>
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#quickAddModal">
                <i class="fas fa-plus-circle me-2"></i>
                افزودن سریع
            </button>
            <a href="task_form.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>
                افزودن وظیفه جدید
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- فیلترها -->
<div class="filter-card">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-lg-3 col-md-6 col-12">
            <label class="form-label">جستجو</label>
            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="عنوان، توضیحات...">
        </div>

        <div class="col-lg-2 col-md-6 col-12">
            <label class="form-label">وضعیت</label>
            <select class="form-select" name="status">
                <option value="">همه</option>
                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>در انتظار</option>
                <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>در حال انجام</option>
                <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>تکمیل شده</option>
                <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>لغو شده</option>
            </select>
        </div>

        <div class="col-lg-2 col-md-6 col-12">
            <label class="form-label">اولویت</label>
            <select class="form-select" name="priority">
                <option value="">همه</option>
                <option value="urgent" <?php echo $priority === 'urgent' ? 'selected' : ''; ?>>فوری</option>
                <option value="high" <?php echo $priority === 'high' ? 'selected' : ''; ?>>بالا</option>
                <option value="medium" <?php echo $priority === 'medium' ? 'selected' : ''; ?>>متوسط</option>
                <option value="low" <?php echo $priority === 'low' ? 'selected' : ''; ?>>کم</option>
            </select>
        </div>

        <div class="col-lg-2 col-md-6 col-12">
            <label class="form-label">نوع</label>
            <select class="form-select" name="type">
                <option value="">همه</option>
                <option value="call" <?php echo $type === 'call' ? 'selected' : ''; ?>>تماس</option>
                <option value="email" <?php echo $type === 'email' ? 'selected' : ''; ?>>ایمیل</option>
                <option value="meeting" <?php echo $type === 'meeting' ? 'selected' : ''; ?>>جلسه</option>
                <option value="follow_up" <?php echo $type === 'follow_up' ? 'selected' : ''; ?>>پیگیری</option>
                <option value="other" <?php echo $type === 'other' ? 'selected' : ''; ?>>سایر</option>
            </select>
        </div>

        <div class="col-lg-2 col-md-6 col-12">
            <label class="form-label">سررسید</label>
            <select class="form-select" name="due_filter">
                <option value="">همه</option>
                <option value="today" <?php echo $due_filter === 'today' ? 'selected' : ''; ?>>امروز</option>
                <option value="tomorrow" <?php echo $due_filter === 'tomorrow' ? 'selected' : ''; ?>>فردا</option>
                <option value="this_week" <?php echo $due_filter === 'this_week' ? 'selected' : ''; ?>>این هفته</option>
                <option value="overdue" <?php echo $due_filter === 'overdue' ? 'selected' : ''; ?>>عقب‌افتاده</option>
            </select>
        </div>

        <div class="col-lg-1 col-md-12 col-12">
            <label class="form-label d-none d-lg-block">&nbsp;</label>
            <div class="d-grid">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="fas fa-search me-1"></i>
                    <span class="d-lg-none">جستجو</span>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- جدول وظایف -->
<div class="tasks-table-card">
    <div class="card-header">
        <h5>
            <i class="fas fa-tasks"></i>
            لیست وظایف
            <span class="badge"><?php echo number_format($total_records); ?></span>
        </h5>

        <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="exportTableToCSV('tasksTable', 'tasks.csv')">
                <i class="fas fa-download me-1"></i>
                خروجی CSV
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <?php if (count($tasks) > 0): ?>
            <table class="table-modern" id="tasksTable">
                <thead>
                    <tr>
                        <th>عنوان وظیفه</th>
                        <th>وضعیت</th>
                        <th>اولویت</th>
                        <th>نوع</th>
                        <th>سررسید</th>
                        <th>مسئول</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task):
                        // محاسبه وضعیت سررسید
                        $is_overdue = false;
                        if ($task['due_date'] && strtotime($task['due_date']) < time() && $task['status'] !== 'completed') {
                            $is_overdue = true;
                        }

                        // ترجمه وضعیت
                        $status_labels = [
                            'pending' => 'در انتظار',
                            'in_progress' => 'در حال انجام',
                            'completed' => 'تکمیل شده',
                            'cancelled' => 'لغو شده'
                        ];

                        // ترجمه اولویت
                        $priority_labels = [
                            'urgent' => 'فوری',
                            'high' => 'بالا',
                            'medium' => 'متوسط',
                            'low' => 'کم'
                        ];

                        // ترجمه نوع
                        $type_labels = [
                            'call' => 'تماس',
                            'email' => 'ایمیل',
                            'meeting' => 'جلسه',
                            'follow_up' => 'پیگیری',
                            'other' => 'سایر'
                        ];
                    ?>
                        <tr>
                            <td>
                                <div class="task-title-cell">
                                    <div class="task-main-title">
                                        <?php echo htmlspecialchars($task['title']); ?>
                                    </div>
                                    <?php if ($task['related_name']): ?>
                                        <div class="task-subtitle">
                                            <i class="fas fa-link"></i>
                                            <?php echo htmlspecialchars($task['related_name']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $task['status']; ?>">
                                    <?php echo $status_labels[$task['status']] ?? $task['status']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="priority-badge priority-<?php echo $task['priority']; ?>">
                                    <?php if ($task['priority'] === 'urgent'): ?>
                                        <i class="fas fa-exclamation-triangle"></i>
                                    <?php elseif ($task['priority'] === 'high'): ?>
                                        <i class="fas fa-arrow-up"></i>
                                    <?php elseif ($task['priority'] === 'medium'): ?>
                                        <i class="fas fa-minus"></i>
                                    <?php else: ?>
                                        <i class="fas fa-arrow-down"></i>
                                    <?php endif; ?>
                                    <?php echo $priority_labels[$task['priority']] ?? $task['priority']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="type-badge">
                                    <?php
                                    $type_icons = [
                                        'call' => 'fa-phone',
                                        'email' => 'fa-envelope',
                                        'meeting' => 'fa-users',
                                        'follow_up' => 'fa-redo',
                                        'other' => 'fa-circle'
                                    ];
                                    ?>
                                    <i class="fas <?php echo $type_icons[$task['type']] ?? 'fa-circle'; ?>"></i>
                                    <?php echo $type_labels[$task['type']] ?? $task['type']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($task['due_date']): ?>
                                    <div class="due-date-cell <?php echo $is_overdue ? 'overdue' : ''; ?>">
                                        <div class="due-date-main">
                                            <?php echo jdate('Y/m/d', strtotime($task['due_date'])); ?>
                                        </div>
                                        <div class="due-date-sub">
                                            <?php echo jdate('H:i', strtotime($task['due_date'])); ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size: 0.85rem;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($task['assigned_user']): ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="user-avatar-mini">
                                            <?php
                                            $names = explode(' ', $task['assigned_user']);
                                            echo mb_substr($names[0], 0, 1);
                                            if (isset($names[1])) echo mb_substr($names[1], 0, 1);
                                            ?>
                                        </div>
                                        <span style="font-size: 0.85rem;">
                                            <?php echo htmlspecialchars($task['assigned_user']); ?>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size: 0.85rem;">بدون مسئول</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="task_view.php?id=<?php echo $task['id']; ?>"
                                       class="btn btn-outline-info btn-sm"
                                       data-bs-toggle="tooltip" title="مشاهده">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    <?php if (hasPermission('edit_task')): ?>
                                        <a href="task_form.php?id=<?php echo $task['id']; ?>"
                                           class="btn btn-outline-warning btn-sm"
                                           data-bs-toggle="tooltip" title="ویرایش">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php if (hasPermission('delete_task')): ?>
                                        <button type="button"
                                                class="btn btn-outline-danger btn-sm"
                                                onclick="deleteTask(<?php echo $task['id']; ?>, '<?php echo htmlspecialchars($task['title']); ?>')"
                                                data-bs-toggle="tooltip" title="حذف">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state-tasks">
                <i class="fas fa-inbox"></i>
                <h5>هیچ وظیفه‌ای یافت نشد</h5>
                <p>با استفاده از فیلترها جستجوی دیگری انجام دهید یا وظیفه جدیدی اضافه کنید</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- صفحه‌بندی -->
    <?php if ($total_records > $per_page): ?>
        <div class="pagination-wrapper">
            <div class="pagination-info">
                نمایش <?php echo number_format($offset + 1); ?> تا <?php echo number_format(min($offset + $per_page, $total_records)); ?>
                از <?php echo number_format($total_records); ?> رکورد
            </div>

            <?php
            $base_url = 'tasks.php?' . http_build_query(array_filter([
                'search' => $search,
                'status' => $status,
                'priority' => $priority,
                'type' => $type,
                'assigned_to' => $assigned_to,
                'due_filter' => $due_filter
            ]));
            echo createPagination($page, $total_records, $per_page, $base_url);
            ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal افزودن سریع -->
<?php if (hasPermission('add_task')): ?>
<div class="modal fade" id="quickAddModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">افزودن سریع وظیفه</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="quick_add">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="mb-3">
                        <label for="quick_title" class="form-label">عنوان وظیفه <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="quick_title" name="quick_title" required>
                    </div>

                    <div class="mb-3">
                        <label for="quick_due_date" class="form-label">سررسید</label>
                        <input type="datetime-local" class="form-control" id="quick_due_date" name="quick_due_date">
                    </div>

                    <div class="mb-3">
                        <label for="quick_assigned_to" class="form-label">مسئول</label>
                        <select class="form-select" id="quick_assigned_to" name="quick_assigned_to">
                            <option value="">بدون مسئول</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-primary">افزودن</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function deleteTask(taskId, taskTitle) {
    confirmDelete(`آیا از حذف وظیفه "${taskTitle}" مطمئن هستید؟`).then((confirmed) => {
        if (confirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="task_id" value="${taskId}">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function updateTaskStatus(taskId, newStatus) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="task_id" value="${taskId}">
        <input type="hidden" name="new_status" value="${newStatus}">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Initialize table sorting
document.addEventListener('DOMContentLoaded', function() {
    initTableSort('tasksTable');

    // Set default due date to tomorrow
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    tomorrow.setHours(9, 0, 0, 0);
    const quickDueInput = document.getElementById('quick_due_date');
    if (quickDueInput) {
        quickDueInput.value = tomorrow.toISOString().slice(0, 16);
    }
});
</script>

<?php include __DIR__ . '/../private/footer.php'; ?>
