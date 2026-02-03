<?php
$page_title = 'مدیریت کاربران';
$breadcrumb = [
    ['title' => 'داشبورد', 'url' => 'dashboard.php'],
    ['title' => 'کاربران']
];

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/database.php';
require_once __DIR__ . '/../private/auth.php';
require_once __DIR__ . '/../private/functions.php';

// بررسی دسترسی
if (!hasRole('admin')) {
    setMessage('شما دسترسی لازم برای مشاهده این صفحه را ندارید', 'error');
    header('Location: dashboard.php');
    exit();
}

$errors = [];

// پردازش درخواست‌ها
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $user_id = (int)$_POST['user_id'];

        // جلوگیری از حذف خودی
        if ($user_id == $_SESSION['user_id']) {
            setMessage('نمی‌توانید خودتان را حذف کنید', 'error');
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);

                if ($stmt->rowCount() > 0) {
                    logActivity($_SESSION['user_id'], 'delete_user', 'users', $user_id);
                    setMessage('کاربر با موفقیت حذف شد', 'success');
                } else {
                    setMessage('کاربر یافت نشد', 'error');
                }
            } catch (PDOException $e) {
                error_log("خطا در حذف کاربر: " . $e->getMessage());
                setMessage('خطا در حذف کاربر', 'error');
            }
        }
    }

    if ($action === 'toggle_status') {
        $user_id = (int)$_POST['user_id'];
        $new_status = $_POST['new_status'];

        // جلوگیری از غیرفعال کردن خودی
        if ($user_id == $_SESSION['user_id']) {
            setMessage('نمی‌توانید وضعیت خودتان را تغییر دهید', 'error');
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $user_id]);

                if ($stmt->rowCount() > 0) {
                    logActivity($_SESSION['user_id'], 'toggle_user_status', 'users', $user_id, ['status' => $new_status]);
                    setMessage('وضعیت کاربر بروزرسانی شد', 'success');
                }
            } catch (PDOException $e) {
                error_log("خطا در تغییر وضعیت کاربر: " . $e->getMessage());
                setMessage('خطا در تغییر وضعیت کاربر', 'error');
            }
        }
    }

    if ($action === 'quick_add') {
        $username = sanitizeInput($_POST['quick_username']);
        $email = sanitizeInput($_POST['quick_email']);
        $first_name = sanitizeInput($_POST['quick_first_name']);
        $last_name = sanitizeInput($_POST['quick_last_name']);
        $role = $_POST['quick_role'];
        $password = generateRandomPassword();

        // اعتبارسنجی
        if (empty($username) || empty($email) || empty($first_name) || empty($last_name)) {
            setMessage('لطفاً تمام فیلدهای الزامی را پر کنید', 'error');
        } elseif (!validateEmail($email)) {
            setMessage('فرمت ایمیل صحیح نیست', 'error');
        } else {
            try {
                // بررسی تکراری بودن
                $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
                $check->execute([$username, $email]);

                if ($check->fetchColumn() > 0) {
                    setMessage('نام کاربری یا ایمیل قبلاً ثبت شده است', 'error');
                } else {
                    $hashed_password = hashPassword($password);

                    $stmt = $pdo->prepare("
                        INSERT INTO users (username, email, password, first_name, last_name, role)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$username, $email, $hashed_password, $first_name, $last_name, $role]);

                    logActivity($_SESSION['user_id'], 'create_user', 'users', $pdo->lastInsertId());
                    setMessage("کاربر جدید اضافه شد. رمز عبور موقت: $password", 'success');
                }
            } catch (PDOException $e) {
                error_log("خطا در افزودن کاربر: " . $e->getMessage());
                setMessage('خطا در افزودن کاربر', 'error');
            }
        }
    }
}

// دریافت فیلترها
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$per_page = RECORDS_PER_PAGE;
$offset = ($page - 1) * $per_page;

// ساخت کوئری
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

if ($role) {
    $where_conditions[] = "role = ?";
    $params[] = $role;
}

if ($status) {
    $where_conditions[] = "status = ?";
    $params[] = $status;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// دریافت تعداد کل رکوردها
$count_sql = "SELECT COUNT(*) FROM users $where_clause";
$total_records = $pdo->prepare($count_sql);
$total_records->execute($params);
$total_records = $total_records->fetchColumn();

// دریافت کاربران
$sql = "
    SELECT *
    FROM users
    $where_clause
    ORDER BY created_at DESC
    LIMIT $per_page OFFSET $offset
";

$users = $pdo->prepare($sql);
$users->execute($params);
$users = $users->fetchAll();

// آمار کاربران
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'active' => $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn(),
    'admins' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn(),
    'online' => $pdo->query("SELECT COUNT(*) FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)")->fetchColumn(),
];

$csrf_token = generateCSRFToken();

include __DIR__ . '/../private/header.php';
?>

<!-- ========== SVG Sprite (hidden) ========== -->
<svg style="display:none;" width="0" height="0">
    <!-- users -->
    <symbol id="svg-users" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
    </symbol>
    <!-- user-check -->
    <symbol id="svg-user-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17 11 19 13 23 9"/>
    </symbol>
    <!-- shield -->
    <symbol id="svg-shield" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
    </symbol>
    <!-- activity -->
    <symbol id="svg-activity" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
    </symbol>
    <!-- plus -->
    <symbol id="svg-plus" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
    </symbol>
    <!-- plus-circle -->
    <symbol id="svg-plus-circle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>
    </symbol>
    <!-- search -->
    <symbol id="svg-search" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
    </symbol>
    <!-- download -->
    <symbol id="svg-download" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
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
    <!-- user -->
    <symbol id="svg-user" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
    </symbol>
    <!-- info-circle -->
    <symbol id="svg-info-circle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
    </symbol>
</svg>

<!-- ========== Users Page Styles ========== -->
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
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
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
.icon-teal   { background: var(--teal); }
.icon-green  { background: #10b981; }
.icon-amber  { background: #f59e0b; }
.icon-blue   { background: #3b82f6; }

/* ---------- Page Header ---------- */
.users-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.users-page-header h4 {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-1);
    margin: 0 0 4px;
}

.users-page-header p {
    font-size: 14px;
    color: var(--text-3);
    margin: 0;
}

/* Buttons */
.btn-add-user {
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

.btn-add-user:hover {
    background: var(--teal-dark);
    color: #fff;
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.btn-outline-teal {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: transparent;
    color: var(--teal);
    border: 1.5px solid var(--teal);
    border-radius: var(--r-md);
    padding: 9px 18px;
    font-size: 14px;
    font-weight: 600;
    font-family: 'Vazirmatn', sans-serif;
    cursor: pointer;
    text-decoration: none;
    transition: all .2s var(--ease);
}

.btn-outline-teal:hover {
    background: var(--teal);
    color: #fff;
    box-shadow: var(--shadow-sm);
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
    grid-template-columns: 2fr 1fr 1fr auto;
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
.users-table {
    width: 100%;
    border-collapse: collapse;
    font-family: 'Vazirmatn', sans-serif;
}

.users-table thead th {
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

.users-table tbody tr {
    border-bottom: 1px solid var(--border);
    transition: background .18s var(--ease);
}

.users-table tbody tr:last-child { border-bottom: none; }

.users-table tbody tr:hover {
    background: var(--teal-50);
}

.users-table tbody tr.current-user-row {
    background: #eff6ff;
}

.users-table tbody tr.current-user-row:hover {
    background: #dbeafe;
}

.users-table tbody td {
    padding: 16px 18px;
    font-size: 14px;
    color: var(--text-1);
    vertical-align: middle;
}

/* User cell */
.cell-user-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar-wrap {
    width: 42px;
    height: 42px;
    flex-shrink: 0;
    background: var(--teal-bg);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--teal);
    position: relative;
    transition: background .2s, color .2s;
}

.users-table tbody tr:hover .user-avatar-wrap {
    background: var(--teal);
    color: #fff;
}

.online-indicator {
    position: absolute;
    top: -2px;
    left: -2px;
    width: 12px;
    height: 12px;
    background: #10b981;
    border: 2px solid #fff;
    border-radius: 50%;
    box-shadow: 0 0 0 2px rgba(16,185,129,.3);
}

.user-name-text {
    flex: 1;
}

.user-name-text .name {
    font-weight: 600;
    font-size: 14px;
    color: var(--text-1);
    margin-bottom: 2px;
}

.user-name-text .username {
    font-size: 12px;
    color: var(--text-3);
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

.badge-role-admin   { background: #fef2f2; color: #dc2626; }
.badge-role-manager { background: #fffbeb; color: #f59e0b; }
.badge-role-sales   { background: #eff6ff; color: #3b82f6; }
.badge-role-user    { background: #f0fdfa; color: #14b8a6; }

.badge-status-active    { background: #f0fdf4; color: #16a34a; }
.badge-status-inactive  { background: #f3f4f6; color: #6b7280; }
.badge-status-suspended { background: #fef2f2; color: #dc2626; }

.badge-you {
    background: #eff6ff;
    color: #3b82f6;
    margin-right: 6px;
}

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
.empty-state-users {
    text-align: center;
    padding: 72px 24px;
}

.empty-state-users .empty-icon-wrap {
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

.empty-state-users h5 {
    color: var(--text-1);
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 6px;
}

.empty-state-users p {
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

/* ---------- Modal Override ---------- */
.modal-content {
    border: none;
    border-radius: var(--r-lg);
    box-shadow: var(--shadow-lg);
}

.modal-header {
    border-bottom: 1px solid var(--border);
    padding: 20px 24px;
}

.modal-title {
    font-weight: 700;
    color: var(--text-1);
}

.modal-body {
    padding: 24px;
}

.modal-footer {
    border-top: 1px solid var(--border);
    padding: 16px 24px;
}

.alert-info {
    background: var(--teal-50);
    border: 1px solid var(--teal-bg);
    color: var(--text-2);
    border-radius: var(--r-md);
    padding: 12px 16px;
}

/* ---------- Responsive ---------- */
@media (max-width: 1200px) {
    .filter-row { grid-template-columns: 1fr 1fr 1fr; }
}

@media (max-width: 768px) {
    .filter-row { grid-template-columns: 1fr; }
    .users-page-header { flex-direction: column; align-items: flex-start; }
    .stats-row-2026 { grid-template-columns: 1fr; }

    .table-card {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .users-table { min-width: 1000px; }
}
</style>

<!-- ========== Stats Row ========== -->
<div class="stats-row-2026">
    <!-- Total Users -->
    <div class="stat-card-2026">
        <div class="stat-content">
            <div class="stat-icon icon-teal">
                <svg width="24" height="24"><use href="#svg-users"/></svg>
            </div>
            <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
            <div class="stat-label">کل کاربران</div>
        </div>
    </div>

    <!-- Active -->
    <div class="stat-card-2026">
        <div class="stat-content">
            <div class="stat-icon icon-green">
                <svg width="24" height="24"><use href="#svg-user-check"/></svg>
            </div>
            <div class="stat-value"><?php echo number_format($stats['active']); ?></div>
            <div class="stat-label">فعال</div>
        </div>
    </div>

    <!-- Admins -->
    <div class="stat-card-2026">
        <div class="stat-content">
            <div class="stat-icon icon-amber">
                <svg width="24" height="24"><use href="#svg-shield"/></svg>
            </div>
            <div class="stat-value"><?php echo number_format($stats['admins']); ?></div>
            <div class="stat-label">مدیران</div>
        </div>
    </div>

    <!-- Online -->
    <div class="stat-card-2026">
        <div class="stat-content">
            <div class="stat-icon icon-blue">
                <svg width="24" height="24"><use href="#svg-activity"/></svg>
            </div>
            <div class="stat-value"><?php echo number_format($stats['online']); ?></div>
            <div class="stat-label">آنلاین</div>
        </div>
    </div>
</div>

<!-- ========== Page Header ========== -->
<div class="users-page-header">
    <div>
        <h4>مدیریت کاربران</h4>
        <p>مشاهده و مدیریت کاربران سیستم</p>
    </div>
    <div style="display:flex;gap:10px;">
        <button type="button" class="btn-outline-teal" data-bs-toggle="modal" data-bs-target="#quickAddModal">
            <svg width="16" height="16"><use href="#svg-plus-circle"/></svg>
            افزودن سریع
        </button>
        <a href="user_form.php" class="btn-add-user">
            <svg width="16" height="16"><use href="#svg-plus"/></svg>
            افزودن کاربر جدید
        </a>
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
                           placeholder="نام کاربری، ایمیل، نام…">
                    <span class="search-icon">
                        <svg width="16" height="16"><use href="#svg-search"/></svg>
                    </span>
                </div>
            </div>

            <!-- Role -->
            <div class="filter-group">
                <label>نقش</label>
                <select class="form-select" name="role">
                    <option value="">همه نقش‌ها</option>
                    <option value="admin"   <?php echo $role === 'admin'   ? 'selected' : ''; ?>>مدیر کل</option>
                    <option value="manager" <?php echo $role === 'manager' ? 'selected' : ''; ?>>مدیر</option>
                    <option value="sales"   <?php echo $role === 'sales'   ? 'selected' : ''; ?>>فروشنده</option>
                    <option value="user"    <?php echo $role === 'user'    ? 'selected' : ''; ?>>کاربر</option>
                </select>
            </div>

            <!-- Status -->
            <div class="filter-group">
                <label>وضعیت</label>
                <select class="form-select" name="status">
                    <option value="">همه</option>
                    <option value="active"    <?php echo $status === 'active'    ? 'selected' : ''; ?>>فعال</option>
                    <option value="inactive"  <?php echo $status === 'inactive'  ? 'selected' : ''; ?>>غیرفعال</option>
                    <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>تعلیق شده</option>
                </select>
            </div>

            <!-- Submit -->
            <div class="filter-group">
                <button type="submit" class="btn-filter-submit">
                    <svg width="15" height="15"><use href="#svg-search"/></svg>
                    جستجو
                </button>
            </div>
        </div>
    </form>
</div>

<!-- ========== Users Table ========== -->
<div class="table-card">
    <!-- Header -->
    <div class="table-card-header">
        <h5>
            <svg width="20" height="20"><use href="#svg-users"/></svg>
            لیست کاربران
            <span class="badge-count"><?php echo number_format($total_records); ?></span>
        </h5>
        <div class="btn-group" role="group">
            <button class="btn-export" onclick="exportTableToCSV('usersTable','users.csv')">
                <svg width="14" height="14"><use href="#svg-download"/></svg>
                خروجی CSV
            </button>
        </div>
    </div>

    <!-- Body -->
    <div style="overflow-x:auto;">
        <?php if (empty($users)): ?>
            <!-- Empty State -->
            <div class="empty-state-users">
                <div class="empty-icon-wrap">
                    <svg width="36" height="36"><use href="#svg-users"/></svg>
                </div>
                <h5>کاربری یافت نشد</h5>
                <p>برای شروع، کاربر جدیدی اضافه کنید</p>
                <a href="user_form.php" class="btn-add-user">
                    <svg width="16" height="16"><use href="#svg-plus"/></svg>
                    افزودن کاربر اول
                </a>
            </div>
        <?php else: ?>
            <table class="users-table" id="usersTable">
                <thead>
                    <tr>
                        <th>کاربر</th>
                        <th>ایمیل</th>
                        <th style="text-align:center;">نقش</th>
                        <th style="text-align:center;">وضعیت</th>
                        <th style="text-align:center;">آخرین ورود</th>
                        <th style="text-align:center;">تاریخ ثبت</th>
                        <th style="text-align:center;">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <?php
                        $is_online = $user['last_login'] && strtotime($user['last_login']) > strtotime('-30 minutes');
                        $is_current_user = $user['id'] == $_SESSION['user_id'];

                        $role_badge_class = 'badge-role-user';
                        if ($user['role'] === 'admin')   $role_badge_class = 'badge-role-admin';
                        if ($user['role'] === 'manager') $role_badge_class = 'badge-role-manager';
                        if ($user['role'] === 'sales')   $role_badge_class = 'badge-role-sales';

                        $status_badge_class = 'badge-status-inactive';
                        if ($user['status'] === 'active')    $status_badge_class = 'badge-status-active';
                        if ($user['status'] === 'suspended') $status_badge_class = 'badge-status-suspended';
                        ?>
                        <tr class="<?php echo $is_current_user ? 'current-user-row' : ''; ?>">
                            <!-- User Info -->
                            <td>
                                <div class="cell-user-info">
                                    <div class="user-avatar-wrap">
                                        <svg width="20" height="20"><use href="#svg-user"/></svg>
                                        <?php if ($is_online): ?>
                                            <span class="online-indicator"></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="user-name-text">
                                        <div class="name">
                                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                            <?php if ($is_current_user): ?>
                                                <span class="badge-2026 badge-you">شما</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="username">@<?php echo htmlspecialchars($user['username']); ?></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Email -->
                            <td>
                                <div style="font-weight:500;"><?php echo htmlspecialchars($user['email']); ?></div>
                                <?php if ($user['phone']): ?>
                                    <div style="font-size:12px;color:var(--text-3);margin-top:2px;">
                                        <?php echo formatPhone($user['phone']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- Role -->
                            <td style="text-align:center;">
                                <span class="badge-2026 <?php echo $role_badge_class; ?>">
                                    <?php echo getRoleTitle($user['role']); ?>
                                </span>
                            </td>

                            <!-- Status -->
                            <td style="text-align:center;">
                                <span class="badge-2026 <?php echo $status_badge_class; ?>">
                                    <?php echo getStatusTitle($user['status']); ?>
                                </span>
                            </td>

                            <!-- Last Login -->
                            <td style="text-align:center;">
                                <?php if ($user['last_login']): ?>
                                    <div style="font-size:13px;color:var(--text-2);">
                                        <?php echo formatPersianDate($user['last_login'], 'Y/m/d H:i'); ?>
                                    </div>
                                    <?php if ($is_online): ?>
                                        <div style="font-size:11px;color:#10b981;margin-top:2px;font-weight:600;">
                                            آنلاین
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);">هرگز</span>
                                <?php endif; ?>
                            </td>

                            <!-- Created At -->
                            <td style="text-align:center;">
                                <span style="font-size:13px;color:var(--text-3);">
                                    <?php echo formatPersianDate($user['created_at'], 'Y/m/d'); ?>
                                </span>
                            </td>

                            <!-- Actions -->
                            <td style="text-align:center;">
                                <div class="actions-group" style="justify-content:center;">
                                    <!-- View -->
                                    <a href="user_view.php?id=<?php echo $user['id']; ?>" class="btn-action btn-action--view"
                                       title="مشاهده جزئیات">
                                        <svg width="15" height="15"><use href="#svg-eye"/></svg>
                                    </a>
                                    <!-- Edit -->
                                    <a href="user_form.php?id=<?php echo $user['id']; ?>" class="btn-action btn-action--edit"
                                       title="ویرایش">
                                        <svg width="15" height="15"><use href="#svg-edit"/></svg>
                                    </a>
                                    <!-- Delete -->
                                    <?php if (!$is_current_user): ?>
                                        <button class="btn-action btn-action--delete"
                                                onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username'], ENT_QUOTES); ?>')"
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
                        $base_url = 'users.php?' . http_build_query(array_filter([
                            'search' => $search,
                            'role'   => $role,
                            'status' => $status
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
<div class="modal fade" id="quickAddModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">افزودن سریع کاربر</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="quick_add">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="quick_first_name" class="form-label">نام <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="quick_first_name" name="quick_first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="quick_last_name" class="form-label">نام خانوادگی <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="quick_last_name" name="quick_last_name" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="quick_username" class="form-label">نام کاربری <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="quick_username" name="quick_username" required>
                    </div>

                    <div class="mb-3">
                        <label for="quick_email" class="form-label">ایمیل <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="quick_email" name="quick_email" required>
                    </div>

                    <div class="mb-3">
                        <label for="quick_role" class="form-label">نقش <span class="text-danger">*</span></label>
                        <select class="form-select" id="quick_role" name="quick_role" required>
                            <option value="user">کاربر</option>
                            <option value="sales">فروشنده</option>
                            <option value="manager">مدیر</option>
                            <option value="admin">مدیر کل</option>
                        </select>
                    </div>

                    <div class="alert-info">
                        <svg width="16" height="16" style="display:inline;vertical-align:middle;margin-left:8px;"><use href="#svg-info-circle"/></svg>
                        رمز عبور موقت به صورت خودکار تولید و نمایش داده می‌شود.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn-add-user">افزودن</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========== Page Scripts ========== -->
<script>
function deleteUser(userId, username) {
    confirmDelete(`آیا از حذف کاربر "${username}" مطمئن هستید؟`).then((confirmed) => {
        if (confirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action"     value="delete">
                <input type="hidden" name="user_id"    value="${userId}">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function toggleUserStatus(userId, newStatus) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action"     value="toggle_status">
        <input type="hidden" name="user_id"    value="${userId}">
        <input type="hidden" name="new_status" value="${newStatus}">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Initialize table sorting
document.addEventListener('DOMContentLoaded', function() {
    initTableSort('usersTable');
});
</script>

<?php include __DIR__ . '/../private/footer.php'; ?>
