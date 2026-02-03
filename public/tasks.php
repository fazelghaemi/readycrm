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

<!-- ========== SVG Sprite (hidden) ========== -->
<svg style="display:none;" width="0" height="0">
    <!-- check-square (tasks) -->
    <symbol id="svg-check-square" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
    </symbol>
    <!-- clock -->
    <symbol id="svg-clock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
    </symbol>
    <!-- activity -->
    <symbol id="svg-activity" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
    </symbol>
    <!-- check-circle -->
    <symbol id="svg-check-circle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
    </symbol>
    <!-- alert-triangle -->
    <symbol id="svg-alert-triangle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
    </symbol>
    <!-- plus-circle -->
    <symbol id="svg-plus-circle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>
    </symbol>
    <!-- plus -->
    <symbol id="svg-plus" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
    </symbol>
    <!-- search -->
    <symbol id="svg-search" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
    </symbol>
    <!-- user -->
    <symbol id="svg-user" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="8" r="4"/>
    </symbol>
    <!-- calendar -->
    <symbol id="svg-calendar" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
    </symbol>
    <!-- eye -->
    <symbol id="svg-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
    </symbol>
    <!-- edit -->
    <symbol id="svg-edit" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
    </symbol>
    <!-- trash -->
    <symbol id="svg-trash" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/>
    </symbol>
    <!-- download -->
    <symbol id="svg-download" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
    </symbol>
    <!-- phone-call -->
    <symbol id="svg-phone-call" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M15.05 5A5 5 0 0 1 19 8.95M15.05 1A9 9 0 0 1 23 8.94m-1 7.98v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
    </symbol>
    <!-- mail -->
    <symbol id="svg-mail" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
    </symbol>
    <!-- users -->
    <symbol id="svg-users" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
    </symbol>
    <!-- link -->
    <symbol id="svg-link" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
    </symbol>
    <!-- more-vertical -->
    <symbol id="svg-more-vertical" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/>
    </symbol>
</svg>

<!-- ========== Tasks Page Styles ========== -->
<style>
/* ---------- Design Tokens (Light Teal 2026) ---------- */
:root {
    --teal:        #14b8a6;
    --teal-light:  #5eead4;
    --teal-dark:   #0d9488;
    --teal-bg:     #ccfbf1;
    --teal-50:     #f0fdfa;

    --page-bg:     #f8fafb;
    --card-bg:     #ffffff;
    --text-1:      #0f172a;
    --text-2:      #475569;
    --text-3:      #64748b;
    --text-muted:  #94a3b8;
    --border:      #e2e8f0;
    --border-mid:  #cbd5e1;

    --shadow-sm:   0 1px 3px  rgba(0,0,0,.06);
    --shadow-md:   0 4px 12px rgba(0,0,0,.08);
    --shadow-lg:   0 8px 24px rgba(0,0,0,.10);

    --r-xl:  20px;
    --r-lg:  16px;
    --r-md:  12px;
    --r-sm:  8px;
    --r-pill:20px;

    --ease: cubic-bezier(.4,0,.2,1);
}

/* ---------- Stats Row ---------- */
.stats-row-2026 {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 28px;
}

.stat-card-2026 {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: var(--r-lg);
    padding: 22px 24px;
    box-shadow: var(--shadow-sm);
    transition: box-shadow .2s var(--ease), transform .2s var(--ease);
    position: relative;
    overflow: hidden;
}

.stat-card-2026:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.stat-card-2026::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 100px;
    height: 100px;
    background: var(--teal-50);
    border-radius: 50%;
    transform: translate(30%, -30%);
    opacity: .6;
    z-index: 0;
}

.stat-card-2026 .stat-content {
    position: relative;
    z-index: 1;
}

.stat-card-2026 .stat-icon {
    width: 50px;
    height: 50px;
    border-radius: var(--r-md);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 14px;
    color: white;
    box-shadow: var(--shadow-sm);
}

.stat-card-2026 .stat-value {
    font-size: 26px;
    font-weight: 700;
    color: var(--text-1);
    margin-bottom: 6px;
    line-height: 1;
}

.stat-card-2026 .stat-label {
    font-size: 13px;
    font-weight: 500;
    color: var(--text-3);
}

/* Icon colors */
.icon-teal    { background: var(--teal); }
.icon-amber   { background: #f59e0b; }
.icon-blue    { background: #3b82f6; }
.icon-green   { background: #10b981; }
.icon-red     { background: #ef4444; }

/* ---------- Page Header ---------- */
.tasks-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.tasks-page-header h4 {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-1);
    margin: 0 0 4px;
}

.tasks-page-header p {
    font-size: 14px;
    color: var(--text-3);
    margin: 0;
}

/* Buttons */
.btn-add-task {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--teal);
    color: #fff;
    border: none;
    border-radius: var(--r-md);
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 600;
    font-family: 'Vazirmatn', sans-serif;
    cursor: pointer;
    text-decoration: none;
    transition: background .2s var(--ease), transform .15s var(--ease), box-shadow .2s var(--ease);
    box-shadow: var(--shadow-sm);
}

.btn-add-task:hover {
    background: var(--teal-dark);
    color: #fff;
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.btn-quick-add {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--page-bg);
    color: var(--teal);
    border: 1.5px solid var(--teal);
    border-radius: var(--r-md);
    padding: 10px 18px;
    font-size: 14px;
    font-weight: 600;
    font-family: 'Vazirmatn', sans-serif;
    cursor: pointer;
    transition: background .2s var(--ease), color .2s var(--ease);
    margin-left: 10px;
}

.btn-quick-add:hover {
    background: var(--teal);
    color: #fff;
}

/* ---------- Filter Card ---------- */
.filter-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: var(--r-lg);
    padding: 22px 24px;
    margin-bottom: 24px;
    box-shadow: var(--shadow-sm);
}

.filter-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr auto;
    gap: 14px;
    align-items: end;
}

.filter-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-2);
    margin-bottom: 6px;
}

.filter-card .form-control,
.filter-card .form-select {
    border: 1.5px solid var(--border);
    border-radius: var(--r-md);
    padding: 9px 14px;
    font-size: 14px;
    font-family: 'Vazirmatn', sans-serif;
    color: var(--text-1);
    background: var(--page-bg);
    transition: border-color .2s, box-shadow .2s;
    outline: none;
}

.filter-card .form-control:focus,
.filter-card .form-select:focus {
    border-color: var(--teal);
    box-shadow: 0 0 0 3px rgba(20,184,166,.18);
    background: #fff;
}

.search-wrap {
    position: relative;
}

.search-wrap .search-icon {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    pointer-events: none;
}

.search-wrap .form-control {
    padding-right: 40px;
}

.btn-filter-submit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    background: var(--teal);
    color: #fff;
    border: none;
    border-radius: var(--r-md);
    padding: 9px 18px;
    font-size: 14px;
    font-weight: 600;
    font-family: 'Vazirmatn', sans-serif;
    cursor: pointer;
    white-space: nowrap;
    transition: background .2s var(--ease), box-shadow .2s var(--ease);
    box-shadow: var(--shadow-sm);
    height: 42px;
}

.btn-filter-submit:hover {
    background: var(--teal-dark);
    box-shadow: var(--shadow-md);
}

/* ---------- Table Card ---------- */
.table-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: var(--r-xl);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}

.table-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
}

.table-card-header h5 {
    font-size: 16px;
    font-weight: 700;
    color: var(--text-1);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.table-card-header h5 svg { color: var(--teal); }

.badge-count {
    background: var(--teal-bg);
    color: var(--teal-dark);
    font-size: 12px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: var(--r-pill);
}

.btn-export {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--page-bg);
    color: var(--text-2);
    border: 1.5px solid var(--border);
    border-radius: var(--r-md);
    padding: 7px 14px;
    font-size: 13px;
    font-weight: 600;
    font-family: 'Vazirmatn', sans-serif;
    cursor: pointer;
    transition: border-color .2s, background .2s, color .2s;
}

.btn-export:hover {
    border-color: var(--teal);
    color: var(--teal);
    background: var(--teal-50);
}

/* ---------- Data Table ---------- */
.tasks-table {
    width: 100%;
    border-collapse: collapse;
    font-family: 'Vazirmatn', sans-serif;
}

.tasks-table thead th {
    background: var(--page-bg);
    font-size: 12px;
    font-weight: 700;
    color: var(--text-3);
    text-transform: uppercase;
    letter-spacing: .4px;
    padding: 14px 18px;
    text-align: right;
    border-bottom: 2px solid var(--border);
    white-space: nowrap;
    user-select: none;
}

.tasks-table tbody tr {
    border-bottom: 1px solid var(--border);
    transition: background .18s var(--ease);
}

.tasks-table tbody tr:last-child { border-bottom: none; }

.tasks-table tbody tr:hover {
    background: var(--teal-50);
}

.tasks-table tbody td {
    padding: 16px 18px;
    font-size: 14px;
    color: var(--text-1);
    vertical-align: middle;
}

/* Task title cell */
.cell-task-title {
    font-weight: 600;
    font-size: 14px;
    color: var(--text-1);
}

.cell-task-desc {
    font-size: 12px;
    color: var(--text-3);
    margin-top: 4px;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Related info */
.related-info {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: var(--teal-bg);
    color: var(--teal-dark);
    font-size: 11px;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: var(--r-pill);
}

/* Due date cell */
.cell-due-date {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: var(--text-2);
}

.cell-due-date svg {
    color: var(--text-muted);
}

.cell-due-date.overdue {
    color: #ef4444;
    font-weight: 600;
}

.cell-due-date.overdue svg {
    color: #ef4444;
}

/* Avatar */
.avatar-md {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--teal-bg);
    color: var(--teal);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: background .2s, color .2s;
}

.tasks-table tbody tr:hover .avatar-md {
    background: var(--teal);
    color: #fff;
}

/* ---------- Badges 2026 ---------- */
.badge-2026 {
    display: inline-flex;
    align-items: center;
    padding: 5px 12px;
    border-radius: var(--r-pill);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .3px;
    white-space: nowrap;
}

/* Status */
.badge-status-pending     { background: #fffbeb; color: #f59e0b; }
.badge-status-in_progress { background: #eff6ff; color: #3b82f6; }
.badge-status-completed   { background: #f0fdf4; color: #16a34a; }
.badge-status-cancelled   { background: #fef2f2; color: #ef4444; }

/* Priority */
.badge-priority-urgent { background: #fef2f2; color: #dc2626; }
.badge-priority-high   { background: #fef3c7; color: #d97706; }
.badge-priority-medium { background: #eff6ff; color: #2563eb; }
.badge-priority-low    { background: #f0fdf4; color: #059669; }

/* Type */
.badge-type-call      { background: #dbeafe; color: #1d4ed8; }
.badge-type-email     { background: #fce7f3; color: #be185d; }
.badge-type-meeting   { background: #e0e7ff; color: #4f46e5; }
.badge-type-follow_up { background: #fef3c7; color: #b45309; }
.badge-type-other     { background: #f3f4f6; color: #374151; }

/* ---------- Action Buttons ---------- */
.actions-group {
    display: flex;
    gap: 6px;
}

.btn-action {
    width: 34px;
    height: 34px;
    border-radius: var(--r-sm);
    border: 1.5px solid var(--border);
    background: var(--page-bg);
    color: var(--text-3);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: border-color .2s, color .2s, background .2s, box-shadow .2s, transform .15s;
}

.btn-action:hover {
    transform: translateY(-1px);
    box-shadow: var(--shadow-sm);
}

.btn-action--view:hover   { border-color: var(--teal);  color: var(--teal);  background: var(--teal-50); }
.btn-action--edit:hover   { border-color: #f59e0b;      color: #f59e0b;      background: #fffbeb; }
.btn-action--delete:hover { border-color: #ef4444;      color: #ef4444;      background: #fef2f2; }

/* ---------- Empty State ---------- */
.empty-state-tasks {
    text-align: center;
    padding: 72px 24px;
}

.empty-state-tasks .empty-icon-wrap {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    background: var(--teal-bg);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--teal);
}

.empty-state-tasks h5 {
    color: var(--text-1);
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 6px;
}

.empty-state-tasks p {
    color: var(--text-3);
    font-size: 14px;
    margin-bottom: 20px;
}

/* ---------- Pagination ---------- */
.pagination-2026 {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 24px;
    border-top: 1px solid var(--border);
}

.pagination-2026 .page-info {
    font-size: 13px;
    color: var(--text-3);
    font-weight: 500;
}

/* ---------- Responsive ---------- */
@media (max-width: 1200px) {
    .filter-row { grid-template-columns: 1fr 1fr 1fr; }
}

@media (max-width: 768px) {
    .filter-row { grid-template-columns: 1fr; }
    .tasks-page-header { flex-direction: column; align-items: flex-start; }
    .stats-row-2026 { grid-template-columns: 1fr; }

    .table-card {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .tasks-table { min-width: 1100px; }
}
</style>

<!-- ========== Stats Row ========== -->
<div class="stats-row-2026">
    <!-- Total Tasks -->
    <div class="stat-card-2026">
        <div class="stat-content">
            <div class="stat-icon icon-teal">
                <svg width="24" height="24"><use href="#svg-check-square"/></svg>
            </div>
            <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
            <div class="stat-label">کل وظایف</div>
        </div>
    </div>

    <!-- Pending -->
    <div class="stat-card-2026">
        <div class="stat-content">
            <div class="stat-icon icon-amber">
                <svg width="24" height="24"><use href="#svg-clock"/></svg>
            </div>
            <div class="stat-value"><?php echo number_format($stats['pending']); ?></div>
            <div class="stat-label">در انتظار</div>
        </div>
    </div>

    <!-- In Progress -->
    <div class="stat-card-2026">
        <div class="stat-content">
            <div class="stat-icon icon-blue">
                <svg width="24" height="24"><use href="#svg-activity"/></svg>
            </div>
            <div class="stat-value"><?php echo number_format($stats['in_progress']); ?></div>
            <div class="stat-label">در حال انجام</div>
        </div>
    </div>

    <!-- Completed -->
    <div class="stat-card-2026">
        <div class="stat-content">
            <div class="stat-icon icon-green">
                <svg width="24" height="24"><use href="#svg-check-circle"/></svg>
            </div>
            <div class="stat-value"><?php echo number_format($stats['completed']); ?></div>
            <div class="stat-label">تکمیل شده</div>
        </div>
    </div>

    <!-- Overdue -->
    <div class="stat-card-2026">
        <div class="stat-content">
            <div class="stat-icon icon-red">
                <svg width="24" height="24"><use href="#svg-alert-triangle"/></svg>
            </div>
            <div class="stat-value"><?php echo number_format($stats['overdue']); ?></div>
            <div class="stat-label">عقب‌افتاده</div>
        </div>
    </div>
</div>

<!-- ========== Page Header ========== -->
<div class="tasks-page-header">
    <div>
        <h4>مدیریت وظایف</h4>
        <p>مشاهده و مدیریت وظایف و پیگیری‌ها</p>
    </div>
    <div>
        <?php if (hasPermission('add_task')): ?>
            <button type="button" class="btn-quick-add" data-bs-toggle="modal" data-bs-target="#quickAddModal">
                <svg width="16" height="16"><use href="#svg-plus-circle"/></svg>
                افزودن سریع
            </button>
            <a href="task_form.php" class="btn-add-task">
                <svg width="16" height="16"><use href="#svg-plus"/></svg>
                افزودن وظیفه جدید
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- ========== Filter Card ========== -->
<div class="filter-card">
    <form method="GET">
        <div class="filter-row">
            <!-- Search -->
            <div class="filter-group">
                <label>جستجو</label>
                <div class="search-wrap">
                    <input type="text" class="form-control" name="search"
                           value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="عنوان، توضیحات…">
                    <span class="search-icon">
                        <svg width="16" height="16"><use href="#svg-search"/></svg>
                    </span>
                </div>
            </div>

            <!-- Status -->
            <div class="filter-group">
                <label>وضعیت</label>
                <select class="form-select" name="status">
                    <option value="">همه</option>
                    <option value="pending"     <?php echo $status === 'pending'     ? 'selected' : ''; ?>>در انتظار</option>
                    <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>در حال انجام</option>
                    <option value="completed"   <?php echo $status === 'completed'   ? 'selected' : ''; ?>>تکمیل شده</option>
                    <option value="cancelled"   <?php echo $status === 'cancelled'   ? 'selected' : ''; ?>>لغو شده</option>
                </select>
            </div>

            <!-- Priority -->
            <div class="filter-group">
                <label>اولویت</label>
                <select class="form-select" name="priority">
                    <option value="">همه</option>
                    <option value="urgent" <?php echo $priority === 'urgent' ? 'selected' : ''; ?>>فوری</option>
                    <option value="high"   <?php echo $priority === 'high'   ? 'selected' : ''; ?>>بالا</option>
                    <option value="medium" <?php echo $priority === 'medium' ? 'selected' : ''; ?>>متوسط</option>
                    <option value="low"    <?php echo $priority === 'low'    ? 'selected' : ''; ?>>کم</option>
                </select>
            </div>

            <!-- Type -->
            <div class="filter-group">
                <label>نوع</label>
                <select class="form-select" name="type">
                    <option value="">همه</option>
                    <option value="call"      <?php echo $type === 'call'      ? 'selected' : ''; ?>>تماس</option>
                    <option value="email"     <?php echo $type === 'email'     ? 'selected' : ''; ?>>ایمیل</option>
                    <option value="meeting"   <?php echo $type === 'meeting'   ? 'selected' : ''; ?>>جلسه</option>
                    <option value="follow_up" <?php echo $type === 'follow_up' ? 'selected' : ''; ?>>پیگیری</option>
                    <option value="other"     <?php echo $type === 'other'     ? 'selected' : ''; ?>>سایر</option>
                </select>
            </div>

            <!-- Due Filter -->
            <div class="filter-group">
                <label>سررسید</label>
                <select class="form-select" name="due_filter">
                    <option value="">همه</option>
                    <option value="today"     <?php echo $due_filter === 'today'     ? 'selected' : ''; ?>>امروز</option>
                    <option value="tomorrow"  <?php echo $due_filter === 'tomorrow'  ? 'selected' : ''; ?>>فردا</option>
                    <option value="this_week" <?php echo $due_filter === 'this_week' ? 'selected' : ''; ?>>این هفته</option>
                    <option value="overdue"   <?php echo $due_filter === 'overdue'   ? 'selected' : ''; ?>>عقب‌افتاده</option>
                </select>
            </div>

            <!-- Assigned To -->
            <div class="filter-group">
                <label>مسئول</label>
                <select class="form-select" name="assigned_to">
                    <option value="">همه</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo $assigned_to == $user['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Submit -->
            <div class="filter-group" style="padding-top:19px;">
                <button type="submit" class="btn-filter-submit">
                    <svg width="15" height="15"><use href="#svg-search"/></svg>
                    جستجو
                </button>
            </div>
        </div>
    </form>
</div>

<!-- ========== Tasks Table ========== -->
<div class="table-card">
    <!-- Header -->
    <div class="table-card-header">
        <h5>
            <svg width="20" height="20"><use href="#svg-check-square"/></svg>
            لیست وظایف
            <span class="badge-count"><?php echo number_format($total_records); ?></span>
        </h5>
        <div class="btn-group" role="group">
            <button class="btn-export" onclick="exportTableToCSV('tasksTable','tasks.csv')">
                <svg width="14" height="14"><use href="#svg-download"/></svg>
                خروجی CSV
            </button>
        </div>
    </div>

    <!-- Body -->
    <div style="overflow-x:auto;">
        <?php if (empty($tasks)): ?>
            <!-- Empty State -->
            <div class="empty-state-tasks">
                <div class="empty-icon-wrap">
                    <svg width="36" height="36"><use href="#svg-check-square"/></svg>
                </div>
                <h5>وظیفه‌ای یافت نشد</h5>
                <p>برای شروع، وظیفه جدیدی اضافه کنید</p>
                <?php if (hasPermission('add_task')): ?>
                    <a href="task_form.php" class="btn-add-task">
                        <svg width="16" height="16"><use href="#svg-plus"/></svg>
                        افزودن وظیفه اول
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <table class="tasks-table" id="tasksTable">
                <thead>
                    <tr>
                        <th>عنوان</th>
                        <th style="text-align:center;">نوع</th>
                        <th style="text-align:center;">اولویت</th>
                        <th style="text-align:center;">وضعیت</th>
                        <th>سررسید</th>
                        <th>مسئول</th>
                        <th>ثبت‌کننده</th>
                        <th style="text-align:center;">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                        <tr>
                            <!-- Title + Description -->
                            <td>
                                <div class="cell-task-title">
                                    <?php echo htmlspecialchars($task['title']); ?>
                                </div>
                                <?php if ($task['related_name']): ?>
                                    <div class="cell-task-desc">
                                        <span class="related-info">
                                            <svg width="11" height="11"><use href="#svg-link"/></svg>
                                            <?php echo htmlspecialchars($task['related_name']); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- Type -->
                            <td style="text-align:center;">
                                <span class="badge-2026 badge-type-<?php echo htmlspecialchars($task['type']); ?>">
                                    <?php echo getStatusTitle($task['type']); ?>
                                </span>
                            </td>

                            <!-- Priority -->
                            <td style="text-align:center;">
                                <span class="badge-2026 badge-priority-<?php echo htmlspecialchars($task['priority']); ?>">
                                    <?php echo getPriorityTitle($task['priority']); ?>
                                </span>
                            </td>

                            <!-- Status -->
                            <td style="text-align:center;">
                                <span class="badge-2026 badge-status-<?php echo htmlspecialchars($task['status']); ?>">
                                    <?php echo getStatusTitle($task['status']); ?>
                                </span>
                            </td>

                            <!-- Due Date -->
                            <td>
                                <?php if ($task['due_date']): ?>
                                    <?php
                                    $is_overdue = strtotime($task['due_date']) < time() && $task['status'] !== 'completed';
                                    $due_class = $is_overdue ? 'overdue' : '';
                                    ?>
                                    <div class="cell-due-date <?php echo $due_class; ?>">
                                        <svg width="13" height="13"><use href="#svg-calendar"/></svg>
                                        <span><?php echo formatPersianDate($task['due_date'], 'Y/m/d H:i'); ?></span>
                                    </div>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Assigned To -->
                            <td>
                                <?php if ($task['assigned_user']): ?>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div class="avatar-md" style="width:32px;height:32px;">
                                            <svg width="14" height="14"><use href="#svg-user"/></svg>
                                        </div>
                                        <small style="color:var(--text-2);">
                                            <?php echo htmlspecialchars($task['assigned_user']); ?>
                                        </small>
                                    </div>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);">بدون مسئول</span>
                                <?php endif; ?>
                            </td>

                            <!-- Created By -->
                            <td>
                                <?php if ($task['created_user']): ?>
                                    <small style="color:var(--text-2);">
                                        <?php echo htmlspecialchars($task['created_user']); ?>
                                    </small>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Actions -->
                            <td style="text-align:center;">
                                <div class="actions-group" style="justify-content:center;">
                                    <!-- View -->
                                    <a href="task_view.php?id=<?php echo $task['id']; ?>" class="btn-action btn-action--view"
                                       title="مشاهده جزئیات">
                                        <svg width="15" height="15"><use href="#svg-eye"/></svg>
                                    </a>
                                    <!-- Edit -->
                                    <?php if (hasPermission('edit_task')): ?>
                                        <a href="task_form.php?id=<?php echo $task['id']; ?>" class="btn-action btn-action--edit"
                                           title="ویرایش">
                                            <svg width="15" height="15"><use href="#svg-edit"/></svg>
                                        </a>
                                    <?php endif; ?>
                                    <!-- Delete -->
                                    <?php if (hasPermission('delete_task')): ?>
                                        <button class="btn-action btn-action--delete"
                                                onclick="deleteTask(<?php echo $task['id']; ?>, '<?php echo htmlspecialchars($task['title'], ENT_QUOTES); ?>')"
                                                title="حذف">
                                            <svg width="15" height="15"><use href="#svg-trash"/></svg>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($total_records > $per_page): ?>
                <div class="pagination-2026">
                    <div class="page-info">
                        نمایش <?php echo number_format($offset + 1); ?> تا <?php echo number_format(min($offset + $per_page, $total_records)); ?>
                        از <?php echo number_format($total_records); ?> رکورد
                    </div>
                    <div>
                        <?php
                        $base_url = 'tasks.php?' . http_build_query(array_filter([
                            'search'      => $search,
                            'status'      => $status,
                            'priority'    => $priority,
                            'type'        => $type,
                            'assigned_to' => $assigned_to,
                            'due_filter'  => $due_filter
                        ]));
                        echo createPagination($page, $total_records, $per_page, $base_url);
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ========== Quick Add Modal ========== -->
<?php if (hasPermission('add_task')): ?>
<div class="modal fade" id="quickAddModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:var(--r-lg);border:1px solid var(--border);">
            <div class="modal-header" style="border-bottom:1px solid var(--border);">
                <h5 class="modal-title" style="display:flex;align-items:center;gap:10px;color:var(--text-1);">
                    <svg width="20" height="20" style="color:var(--teal);"><use href="#svg-plus-circle"/></svg>
                    افزودن سریع وظیفه
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="quick_add">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:600;color:var(--text-2);">عنوان وظیفه *</label>
                        <input type="text" class="form-control" name="quick_title" required
                               style="border:1.5px solid var(--border);border-radius:var(--r-md);padding:10px 14px;">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:600;color:var(--text-2);">سررسید</label>
                        <input type="datetime-local" class="form-control" name="quick_due_date"
                               style="border:1.5px solid var(--border);border-radius:var(--r-md);padding:10px 14px;">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:600;color:var(--text-2);">مسئول</label>
                        <select class="form-select" name="quick_assigned_to"
                                style="border:1.5px solid var(--border);border-radius:var(--r-md);padding:10px 14px;">
                            <option value="">انتخاب کنید</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--border);">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                            style="border-radius:var(--r-md);padding:9px 18px;">لغو</button>
                    <button type="submit" class="btn-add-task">
                        <svg width="16" height="16"><use href="#svg-plus"/></svg>
                        افزودن
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ========== Page Scripts ========== -->
<script>
function deleteTask(taskId, taskTitle) {
    confirmDelete(`آیا از حذف وظیفه "${taskTitle}" مطمئن هستید؟`).then((confirmed) => {
        if (confirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action"     value="delete">
                <input type="hidden" name="task_id"    value="${taskId}">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Initialize table sorting
document.addEventListener('DOMContentLoaded', function() {
    initTableSort('tasksTable');
});
</script>

<?php include __DIR__ . '/../private/footer.php'; ?>
