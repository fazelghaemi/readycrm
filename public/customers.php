<?php
$page_title = 'مدیریت مشتریان';
$breadcrumb = [
    ['title' => 'داشبورد', 'url' => 'dashboard.php'],
    ['title' => 'مشتریان']
];

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/database.php';
require_once __DIR__ . '/../private/auth.php';
require_once __DIR__ . '/../private/functions.php';

// بررسی دسترسی
if (!hasPermission('view_customers')) {
    setMessage('شما دسترسی لازم برای مشاهده این صفحه را ندارید', 'error');
    header('Location: dashboard.php');
    exit();
}

// پردازش درخواست‌ها
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete' && hasPermission('delete_customer')) {
        $customer_id = (int)$_POST['customer_id'];

        try {
            $pdo->beginTransaction();

            // حذف فعالیت‌های مشتری
            $pdo->prepare("DELETE FROM customer_activities WHERE customer_id = ?")->execute([$customer_id]);

            // حذف مشتری
            $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
            $stmt->execute([$customer_id]);

            if ($stmt->rowCount() > 0) {
                logActivity($_SESSION['user_id'], 'delete_customer', 'customers', $customer_id);
                setMessage('مشتری با موفقیت حذف شد', 'success');
            } else {
                setMessage('مشتری یافت نشد', 'error');
            }

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollback();
            error_log("خطا در حذف مشتری: " . $e->getMessage());
            setMessage('خطا در حذف مشتری', 'error');
        }
    }
}

// دریافت فیلترها
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? '';
$assigned_to = $_GET['assigned_to'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$per_page = RECORDS_PER_PAGE;
$offset = ($page - 1) * $per_page;

// ساخت کوئری
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR c.company_name LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term, $search_term]);
}

if ($status) {
    $where_conditions[] = "c.status = ?";
    $params[] = $status;
}

if ($type) {
    $where_conditions[] = "c.customer_type = ?";
    $params[] = $type;
}

if ($assigned_to) {
    $where_conditions[] = "c.assigned_to = ?";
    $params[] = $assigned_to;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// دریافت تعداد کل رکوردها
$count_sql = "SELECT COUNT(*) FROM customers c $where_clause";
$total_records = $pdo->prepare($count_sql);
$total_records->execute($params);
$total_records = $total_records->fetchColumn();

// دریافت مشتریان
$sql = "
    SELECT
        c.*,
        CONCAT(u.first_name, ' ', u.last_name) as assigned_user,
        (SELECT COUNT(*) FROM customer_activities WHERE customer_id = c.id) as activities_count
    FROM customers c
    LEFT JOIN users u ON c.assigned_to = u.id
    $where_clause
    ORDER BY c.created_at DESC
    LIMIT $per_page OFFSET $offset
";

$customers = $pdo->prepare($sql);
$customers->execute($params);
$customers = $customers->fetchAll();

// دریافت کاربران برای فیلتر
$users = $pdo->query("SELECT id, first_name, last_name FROM users WHERE status = 'active' ORDER BY first_name")->fetchAll();

include __DIR__ . '/../private/header.php';
?>

<!-- ========== SVG Sprite (hidden) ========== -->
<svg style="display:none;" width="0" height="0">
    <!-- users (group) -->
    <symbol id="svg-users" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
    </symbol>
    <!-- user (single) -->
    <symbol id="svg-user" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="8" r="4"/>
    </symbol>
    <!-- search -->
    <symbol id="svg-search" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
    </symbol>
    <!-- plus -->
    <symbol id="svg-plus" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
    </symbol>
    <!-- mail -->
    <symbol id="svg-mail" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
    </symbol>
    <!-- phone (mobile) -->
    <symbol id="svg-phone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
    </symbol>
    <!-- smartphone (mobile-alt) -->
    <symbol id="svg-smartphone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/>
    </symbol>
    <!-- user-tie -->
    <symbol id="svg-user-tie" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="8" r="4"/><polyline points="12 14 14 17 12 21 10 17"/>
    </symbol>
    <!-- eye -->
    <symbol id="svg-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
    </symbol>
    <!-- edit / pencil -->
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
    <!-- printer -->
    <symbol id="svg-printer" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>
    </symbol>
    <!-- calendar -->
    <symbol id="svg-calendar" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
    </symbol>
    <!-- activity (pulse) -->
    <symbol id="svg-activity" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
    </symbol>
    <!-- building / company -->
    <symbol id="svg-building" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
    </symbol>
    <!-- tag -->
    <symbol id="svg-tag" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/>
    </symbol>
</svg>

<!-- ========== Customers Page Styles ========== -->
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

/* ---------- Page Header ---------- */
.customers-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.customers-page-header h4 {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-1);
    margin: 0 0 4px;
}

.customers-page-header p {
    font-size: 14px;
    color: var(--text-3);
    margin: 0;
}

/* Add Customer Button */
.btn-add-customer {
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

.btn-add-customer:hover {
    background: var(--teal-dark);
    color: #fff;
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.btn-add-customer svg { color: #fff; }

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
    grid-template-columns: 2.5fr 1.5fr 1.5fr 2fr auto;
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

/* Input / Select override */
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

/* Search icon wrapper */
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

/* Filter submit button */
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

/* Table Header Bar */
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

/* Export buttons */
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

.btn-group .btn-export + .btn-export {
    margin-right: 6px;
}

/* ---------- Data Table ---------- */
.customers-table {
    width: 100%;
    border-collapse: collapse;
    font-family: 'Vazirmatn', sans-serif;
}

.customers-table thead th {
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

.customers-table tbody tr {
    border-bottom: 1px solid var(--border);
    transition: background .18s var(--ease);
}

.customers-table tbody tr:last-child { border-bottom: none; }

.customers-table tbody tr:hover {
    background: var(--teal-50);
}

.customers-table tbody td {
    padding: 16px 18px;
    font-size: 14px;
    color: var(--text-1);
    vertical-align: middle;
}

/* ---------- Cell Helpers ---------- */
/* Customer code badge */
.badge-code {
    background: var(--border-mid);
    color: var(--text-2);
    font-size: 11px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: var(--r-pill);
    letter-spacing: .5px;
}

/* Name cell */
.cell-name {
    display: flex;
    align-items: center;
    gap: 10px;
}

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

.customers-table tbody tr:hover .avatar-md {
    background: var(--teal);
    color: #fff;
}

.cell-name .name-main  { font-weight: 600; font-size: 14px; color: var(--text-1); }
.cell-name .name-email { font-size: 12px; color: var(--text-3); margin-top: 2px; display: flex; align-items: center; gap: 5px; }
.cell-name .name-email svg { color: var(--teal); }

/* Company cell */
.cell-company .company-name { font-weight: 600; color: var(--text-1); font-size: 14px; }
.cell-company .company-industry { font-size: 12px; color: var(--text-3); margin-top: 2px; }

/* Contact cell */
.cell-contact { display: flex; flex-direction: column; gap: 4px; }
.cell-contact .contact-row {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: var(--text-2);
}
.cell-contact .contact-row svg { flex-shrink: 0; }
.contact-icon-mobile { color: var(--teal); }
.contact-icon-phone  { color: #16a34a; }

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

/* Customer type */
.badge-type-company     { background: var(--teal-50);  color: var(--teal-dark); }
.badge-type-individual  { background: #f1f5f9;         color: #475569; }

/* Status */
.badge-status-active   { background: #f0fdf4; color: #16a34a; }
.badge-status-inactive { background: #f3f4f6; color: #6b7280; }
.badge-status-prospect { background: #fffbeb; color: #ca8a04; }

/* Activity badge */
.badge-activities {
    background: #eff6ff;
    color: #2563eb;
    font-size: 11px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: var(--r-pill);
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
.empty-state-customers {
    text-align: center;
    padding: 72px 24px;
}

.empty-state-customers .empty-icon-wrap {
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

.empty-state-customers h5 {
    color: var(--text-1);
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 6px;
}

.empty-state-customers p {
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
@media (max-width: 1024px) {
    .filter-row { grid-template-columns: 1fr 1fr 1fr; }
}

@media (max-width: 768px) {
    .filter-row { grid-template-columns: 1fr; }
    .customers-page-header { flex-direction: column; align-items: flex-start; }

    .table-card {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .customers-table { min-width: 1100px; }
}
</style>

<!-- ========== Page Header ========== -->
<div class="customers-page-header">
    <div>
        <h4>مدیریت مشتریان</h4>
        <p>مشاهده و مدیریت اطلاعات مشتریان</p>
    </div>
    <?php if (hasPermission('add_customer')): ?>
        <a href="customer_form.php" class="btn-add-customer">
            <svg width="16" height="16"><use href="#svg-plus"/></svg>
            افزودن مشتری جدید
        </a>
    <?php endif; ?>
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
                           placeholder="نام، ایمیل، تلفن…">
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
                    <option value="active"   <?php echo $status === 'active'   ? 'selected' : ''; ?>>فعال</option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>غیرفعال</option>
                    <option value="prospect" <?php echo $status === 'prospect' ? 'selected' : ''; ?>>مشتری بالقوه</option>
                </select>
            </div>

            <!-- Type -->
            <div class="filter-group">
                <label>نوع</label>
                <select class="form-select" name="type">
                    <option value="">همه</option>
                    <option value="individual" <?php echo $type === 'individual' ? 'selected' : ''; ?>>حقیقی</option>
                    <option value="company"    <?php echo $type === 'company'    ? 'selected' : ''; ?>>حقوقی</option>
                </select>
            </div>

            <!-- Assigned -->
            <div class="filter-group">
                <label>مسئول</label>
                <select class="form-select" name="assigned_to">
                    <option value="">همه</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>"
                                <?php echo $assigned_to == $user['id'] ? 'selected' : ''; ?>>
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

<!-- ========== Customers Table ========== -->
<div class="table-card">
    <!-- Header -->
    <div class="table-card-header">
        <h5>
            <svg width="20" height="20"><use href="#svg-users"/></svg>
            لیست مشتریان
            <span class="badge-count"><?php echo number_format($total_records); ?></span>
        </h5>
        <div class="btn-group" role="group">
            <button class="btn-export" onclick="exportTableToCSV('customersTable','customers.csv')">
                <svg width="14" height="14"><use href="#svg-download"/></svg>
                خروجی CSV
            </button>
            <button class="btn-export" onclick="window.print()">
                <svg width="14" height="14"><use href="#svg-printer"/></svg>
                چاپ
            </button>
        </div>
    </div>

    <!-- Body -->
    <div style="overflow-x:auto;">
        <?php if (empty($customers)): ?>
            <!-- Empty State -->
            <div class="empty-state-customers">
                <div class="empty-icon-wrap">
                    <svg width="36" height="36"><use href="#svg-users"/></svg>
                </div>
                <h5>مشتری‌ای یافت نشد</h5>
                <p>برای شروع، مشتری جدیدی اضافه کنید</p>
                <?php if (hasPermission('add_customer')): ?>
                    <a href="customer_form.php" class="btn-add-customer">
                        <svg width="16" height="16"><use href="#svg-plus"/></svg>
                        افزودن مشتری اول
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <table class="customers-table" id="customersTable">
                <thead>
                    <tr>
                        <th>کد مشتری</th>
                        <th>نام و نام خانوادگی</th>
                        <th>شرکت</th>
                        <th>تماس</th>
                        <th style="text-align:center;">نوع</th>
                        <th style="text-align:center;">وضعیت</th>
                        <th>مسئول</th>
                        <th style="text-align:center;">فعالیت‌ها</th>
                        <th>تاریخ ایجاد</th>
                        <th style="text-align:center;">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <!-- Customer Code -->
                            <td>
                                <span class="badge-code"><?php echo htmlspecialchars($customer['customer_code']); ?></span>
                            </td>

                            <!-- Name -->
                            <td>
                                <div class="cell-name">
                                    <div class="avatar-md">
                                        <svg width="18" height="18"><use href="#svg-user"/></svg>
                                    </div>
                                    <div>
                                        <div class="name-main"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></div>
                                        <?php if ($customer['email']): ?>
                                            <div class="name-email">
                                                <svg width="11" height="11"><use href="#svg-mail"/></svg>
                                                <?php echo htmlspecialchars($customer['email']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>

                            <!-- Company -->
                            <td>
                                <?php if ($customer['company_name']): ?>
                                    <div class="cell-company">
                                        <div class="company-name"><?php echo htmlspecialchars($customer['company_name']); ?></div>
                                        <div class="company-industry"><?php echo htmlspecialchars($customer['industry'] ?: 'صنعت نامشخص'); ?></div>
                                    </div>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Contact -->
                            <td>
                                <?php if ($customer['phone'] || $customer['mobile']): ?>
                                    <div class="cell-contact">
                                        <?php if ($customer['mobile']): ?>
                                            <div class="contact-row">
                                                <svg class="contact-icon-mobile" width="13" height="13"><use href="#svg-smartphone"/></svg>
                                                <span><?php echo formatPhone($customer['mobile']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($customer['phone']): ?>
                                            <div class="contact-row">
                                                <svg class="contact-icon-phone" width="13" height="13"><use href="#svg-phone"/></svg>
                                                <span><?php echo formatPhone($customer['phone']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Type -->
                            <td style="text-align:center;">
                                <span class="badge-2026 badge-type-<?php echo htmlspecialchars($customer['customer_type']); ?>">
                                    <?php echo $customer['customer_type'] === 'company' ? 'حقوقی' : 'حقیقی'; ?>
                                </span>
                            </td>

                            <!-- Status -->
                            <td style="text-align:center;">
                                <span class="badge-2026 badge-status-<?php echo htmlspecialchars($customer['status']); ?>">
                                    <?php echo getStatusTitle($customer['status']); ?>
                                </span>
                            </td>

                            <!-- Assigned User -->
                            <td>
                                <?php if ($customer['assigned_user']): ?>
                                    <div style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text-2);">
                                        <svg width="14" height="14" style="color:var(--text-muted);"><use href="#svg-user-tie"/></svg>
                                        <?php echo htmlspecialchars($customer['assigned_user']); ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);font-size:13px;">بدون مسئول</span>
                                <?php endif; ?>
                            </td>

                            <!-- Activities Count -->
                            <td style="text-align:center;">
                                <span class="badge-activities">
                                    <?php echo number_format($customer['activities_count']); ?> فعالیت
                                </span>
                            </td>

                            <!-- Created At -->
                            <td>
                                <small style="color:var(--text-3);">
                                    <?php echo formatPersianDate($customer['created_at']); ?>
                                </small>
                            </td>

                            <!-- Actions -->
                            <td style="text-align:center;">
                                <div class="actions-group" style="justify-content:center;">
                                    <!-- View -->
                                    <a href="customer_view.php?id=<?php echo $customer['id']; ?>" class="btn-action btn-action--view"
                                       title="مشاهده جزئیات">
                                        <svg width="15" height="15"><use href="#svg-eye"/></svg>
                                    </a>
                                    <!-- Edit -->
                                    <?php if (hasPermission('edit_customer')): ?>
                                        <a href="customer_form.php?id=<?php echo $customer['id']; ?>" class="btn-action btn-action--edit"
                                           title="ویرایش">
                                            <svg width="15" height="15"><use href="#svg-edit"/></svg>
                                        </a>
                                    <?php endif; ?>
                                    <!-- Delete -->
                                    <?php if (hasPermission('delete_customer')): ?>
                                        <button class="btn-action btn-action--delete"
                                                onclick="deleteCustomer(<?php echo $customer['id']; ?>, '<?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name'], ENT_QUOTES); ?>')"
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
                        $base_url = 'customers.php?' . http_build_query(array_filter([
                            'search'      => $search,
                            'status'      => $status,
                            'type'        => $type,
                            'assigned_to' => $assigned_to
                        ]));
                        echo createPagination($page, $total_records, $per_page, $base_url);
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ========== Page Scripts ========== -->
<script>
function deleteCustomer(customerId, customerName) {
    confirmDelete(`آیا از حذف مشتری "${customerName}" مطمئن هستید؟`).then((confirmed) => {
        if (confirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action"       value="delete">
                <input type="hidden" name="customer_id"  value="${customerId}">
                <input type="hidden" name="csrf_token"   value="<?php echo generateCSRFToken(); ?>">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Initialize table sorting
document.addEventListener('DOMContentLoaded', function() {
    initTableSort('customersTable');
});
</script>

<?php include __DIR__ . '/../private/footer.php'; ?>
