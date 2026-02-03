<?php
$page_title = 'مدیریت فروش';
$breadcrumb = [
    ['title' => 'داشبورد', 'url' => 'dashboard.php'],
    ['title' => 'فروش']
];

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/database.php';
require_once __DIR__ . '/../private/auth.php';
require_once __DIR__ . '/../private/functions.php';

// بررسی دسترسی
if (!hasPermission('view_sales')) {
    setMessage('شما دسترسی لازم برای مشاهده این صفحه را ندارید', 'error');
    header('Location: dashboard.php');
    exit();
}

// پردازش درخواست‌ها
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete' && hasPermission('delete_sale')) {
        $sale_id = (int)$_POST['sale_id'];

        try {
            $pdo->beginTransaction();

            // حذف آیتم‌های فروش
            $pdo->prepare("DELETE FROM sale_items WHERE sale_id = ?")->execute([$sale_id]);

            // حذف فروش
            $stmt = $pdo->prepare("DELETE FROM sales WHERE id = ?");
            $stmt->execute([$sale_id]);

            if ($stmt->rowCount() > 0) {
                logActivity($_SESSION['user_id'], 'delete_sale', 'sales', $sale_id);
                setMessage('فروش با موفقیت حذف شد', 'success');
            } else {
                setMessage('فروش یافت نشد', 'error');
            }

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollback();
            error_log("خطا در حذف فروش: " . $e->getMessage());
            setMessage('خطا در حذف فروش', 'error');
        }
    }

    if ($action === 'update_status' && hasPermission('edit_sale')) {
        $sale_id = (int)$_POST['sale_id'];
        $new_status = $_POST['new_status'];

        try {
            $stmt = $pdo->prepare("UPDATE sales SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $sale_id]);

            if ($stmt->rowCount() > 0) {
                logActivity($_SESSION['user_id'], 'update_sale_status', 'sales', $sale_id, ['status' => $new_status]);
                setMessage('وضعیت فروش بروزرسانی شد', 'success');
            }
        } catch (PDOException $e) {
            error_log("خطا در بروزرسانی وضعیت فروش: " . $e->getMessage());
            setMessage('خطا در بروزرسانی وضعیت', 'error');
        }
    }
}

// دریافت فیلترها
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$payment_status = $_GET['payment_status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$per_page = RECORDS_PER_PAGE;
$offset = ($page - 1) * $per_page;

// ساخت کوئری
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(s.invoice_number LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ? OR c.company_name LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

if ($status) {
    $where_conditions[] = "s.status = ?";
    $params[] = $status;
}

if ($payment_status) {
    $where_conditions[] = "s.payment_status = ?";
    $params[] = $payment_status;
}

if ($date_from) {
    $where_conditions[] = "DATE(s.sale_date) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "DATE(s.sale_date) <= ?";
    $params[] = $date_to;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// دریافت تعداد کل رکوردها
$count_sql = "
    SELECT COUNT(*)
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    $where_clause
";
$total_records = $pdo->prepare($count_sql);
$total_records->execute($params);
$total_records = $total_records->fetchColumn();

// دریافت فروش‌ها
$sql = "
    SELECT
        s.*,
        CONCAT(c.first_name, ' ', c.last_name) as customer_name,
        c.company_name,
        CONCAT(u.first_name, ' ', u.last_name) as created_user
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN users u ON s.created_by = u.id
    $where_clause
    ORDER BY s.sale_date DESC, s.created_at DESC
    LIMIT $per_page OFFSET $offset
";

$sales = $pdo->prepare($sql);
$sales->execute($params);
$sales = $sales->fetchAll();

// آمار فروش
$stats = [
    'total_count' => $pdo->query("SELECT COUNT(*) FROM sales")->fetchColumn(),
    'total_amount' => $pdo->query("SELECT COALESCE(SUM(final_amount), 0) FROM sales WHERE status != 'cancelled'")->fetchColumn(),
    'pending' => $pdo->query("SELECT COUNT(*) FROM sales WHERE status = 'pending'")->fetchColumn(),
    'completed' => $pdo->query("SELECT COUNT(*) FROM sales WHERE status = 'completed'")->fetchColumn(),
    'monthly_amount' => $pdo->query("SELECT COALESCE(SUM(final_amount), 0) FROM sales WHERE DATE(sale_date) >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND status != 'cancelled'")->fetchColumn(),
];

include __DIR__ . '/../private/header.php';
?>

<!-- ========== SVG Sprite (hidden) ========== -->
<svg style="display:none;" width="0" height="0">
    <!-- chart-line -->
    <symbol id="svg-chart-line" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
    </symbol>
    <!-- dollar -->
    <symbol id="svg-dollar" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
    </symbol>
    <!-- clock -->
    <symbol id="svg-clock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
    </symbol>
    <!-- check-circle -->
    <symbol id="svg-check-circle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
    </symbol>
    <!-- trending-up -->
    <symbol id="svg-trending-up" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>
    </symbol>
    <!-- file-text (invoice) -->
    <symbol id="svg-file-text" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
    </symbol>
    <!-- search -->
    <symbol id="svg-search" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
    </symbol>
    <!-- plus -->
    <symbol id="svg-plus" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
    </symbol>
    <!-- user -->
    <symbol id="svg-user" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="8" r="4"/>
    </symbol>
    <!-- building -->
    <symbol id="svg-building" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
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
    <!-- printer -->
    <symbol id="svg-printer" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>
    </symbol>
    <!-- download -->
    <symbol id="svg-download" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
    </symbol>
    <!-- credit-card -->
    <symbol id="svg-credit-card" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>
    </symbol>
    <!-- package (box) -->
    <symbol id="svg-package" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>
    </symbol>
</svg>

<!-- ========== Sales Page Styles ========== -->
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
    margin-bottom: 10px;
}

.stat-card-2026 .stat-footer {
    font-size: 12px;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 5px;
}

.stat-card-2026 .stat-footer svg {
    flex-shrink: 0;
}

/* Icon colors */
.icon-teal    { background: var(--teal); }
.icon-green   { background: #10b981; }
.icon-amber   { background: #f59e0b; }
.icon-blue    { background: #3b82f6; }
.icon-emerald { background: #059669; }

/* ---------- Page Header ---------- */
.sales-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.sales-page-header h4 {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-1);
    margin: 0 0 4px;
}

.sales-page-header p {
    font-size: 14px;
    color: var(--text-3);
    margin: 0;
}

/* Add Button */
.btn-add-sale {
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

.btn-add-sale:hover {
    background: var(--teal-dark);
    color: #fff;
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

/* ---------- Filter Card ---------- */
.filter-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: var(--r-lg);
    padding: 22px 24px;
    marginfr 1.2fr -shadow: var(--shadow-sm);
}

.filter-row {
    display: grid;
    grid-template-columns: 2fr 1.2fr 1.2fr 1.2fr 1.2fr auto;
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

.btn-group .btn-export + .btn-export {
    margin-right: 6px;
}

/* ---------- Data Table ---------- */
.sales-table {
    width: 100%;
    border-collapse: collapse;
    font-family: 'Vazirmatn', sans-serif;
}

.sales-table thead th {
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

.sales-table tbody tr {
    border-bottom: 1px solid var(--border);
    transition: background .18s var(--ease);
}

.sales-table tbody tr:last-child { border-bottom: none; }

.sales-table tbody tr:hover {
    background: var(--teal-50);
}

.sales-table tbody td {
    padding: 16px 18px;
    font-size: 14px;
    color: var(--text-1);
    vertical-align: middle;
}

/* Invoice badge */
.badge-invoice {
    background: var(--teal-bg);
    color: var(--teal-dark);
    font-size: 11px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: var(--r-pill);
    letter-spacing: .5px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.badge-invoice svg {
    color: var(--teal);
}

/* Customer cell */
.cell-customer {
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

.sales-table tbody tr:hover .avatar-md {
    background: var(--teal);
    color: #fff;
}

.cell-customer .customer-name  { font-weight: 600; font-size: 14px; color: var(--text-1); }
.cell-customer .customer-company { font-size: 12px; color: var(--text-3); margin-top: 2px; display: flex; align-items: center; gap: 5px; }
.cell-customer .customer-company svg { color: var(--teal); }

/* Amount cell */
.cell-amount {
    font-weight: 700;
    font-size: 16px;
    color: var(--teal-dark);
}

/* Date cell */
.cell-date {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: var(--text-2);
}

.cell-date svg {
    color: var(--text-muted);
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
.badge-status-pending    { background: #fffbeb; color: #f59e0b; }
.badge-status-processing { background: #eff6ff; color: #3b82f6; }
.badge-status-completed  { background: #f0fdf4; color: #16a34a; }
.badge-status-cancelled  { background: #fef2f2; color: #ef4444; }

/* Payment Status */
.badge-payment-unpaid   { background: #fef2f2; color: #dc2626; }
.badge-payment-partial  { background: #fef3c7; color: #d97706; }
.badge-payment-paid     { background: #d1fae5; color: #059669; }

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
.empty-state-sales {
    text-align: center;
    padding: 72px 24px;
}

.empty-state-sales .empty-icon-wrap {
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

.empty-state-sales h5 {
    color: var(--text-1);
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 6px;
}

.empty-state-sales p {
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
    .sales-page-header { flex-direction: column; align-items: flex-start; }
    .stats-row-2026 { grid-template-columns: 1fr; }

    .table-card {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .sales-table { min-width: 1100px; }
}
</style>

<!-- ========== Stats Row ========== -->
<div class="stats-row-2026">
    <!-- Total Amount -->
    <div class="stat-card-2026">
        <div class="stat-content">
            <div class="stat-icon icon-teal">
                <svg width="24" height="24"><use href="#svg-chart-line"/></svg>
            </div>
            <div class="stat-value"><?php echo formatMoney($stats['total_amount']); ?></div>
            <div class="stat-label">کل فروش</div>
            <div class="stat-footer">
                <svg width="12" height="12"><use href="#svg-trending-up"/></svg>
                از <?php echo number_format($stats['total_count']); ?> فاکتور
            </div>
        </div>
    </div>

    <!-- Monthly Sales -->
    <div class="stat-card-2026">
        <div class="stat-content">
            <div class="stat-icon icon-emerald">
                <svg width="24" height="24"><use href="#svg-dollar"/></svg>
            </div>
            <div class="stat-value"><?php echo formatMoney($stats['monthly_amount']); ?></div>
            <div class="stat-label">فروش این ماه</div>
            <div class="stat-footer">
                <svg width="12" height="12"><use href="#svg-calendar"/></svg>
                ۳۰ روز گذشته
            </div>
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
            <div class="stat-footer">
                نیاز به پردازش
            </div>
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
            <div class="stat-footer">
                <svg width="12" height="12"><use href="#svg-check-circle"/></svg>
                فاکتورهای نهایی
            </div>
        </div>
    </div>
</div>

<!-- ========== Page Header ========== -->
<div class="sales-page-header">
    <div>
        <h4>مدیریت فروش</h4>
        <p>مشاهده و مدیریت فاکتورها و فروش</p>
    </div>
    <?php if (hasPermission('add_sale')): ?>
        <a href="sale_form.php" class="btn-add-sale">
            <svg width="16" height="16"><use href="#svg-plus"/></svg>
            افزودن فاکتور جدید
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
                           placeholder="شماره فاکتور، مشتری…">
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
                    <option value="pending"    <?php echo $status === 'pending'    ? 'selected' : ''; ?>>در انتظار</option>
                    <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>در حال پردازش</option>
                    <option value="completed"  <?php echo $status === 'completed'  ? 'selected' : ''; ?>>تکمیل شده</option>
                    <option value="cancelled"  <?php echo $status === 'cancelled'  ? 'selected' : ''; ?>>لغو شده</option>
                </select>
            </div>

            <!-- Payment Status -->
            <div class="filter-group">
                <label>وضعیت پرداخت</label>
                <select class="form-select" name="payment_status">
                    <option value="">همه</option>
                    <option value="unpaid"  <?php echo $payment_status === 'unpaid'  ? 'selected' : ''; ?>>پرداخت نشده</option>
                    <option value="partial" <?php echo $payment_status === 'partial' ? 'selected' : ''; ?>>پرداخت جزئی</option>
                    <option value="paid"    <?php echo $payment_status === 'paid'    ? 'selected' : ''; ?>>پرداخت شده</option>
                </select>
            </div>

            <!-- Date From -->
            <div class="filter-group">
                <label>از تاریخ</label>
                <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
            </div>

            <!-- Date To -->
            <div class="filter-group">
                <label>تا تاریخ</label>
                <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
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

<!-- ========== Sales Table ========== -->
<div class="table-card">
    <!-- Header -->
    <div class="table-card-header">
        <h5>
            <svg width="20" height="20"><use href="#svg-file-text"/></svg>
            لیست فاکتورها
            <span class="badge-count"><?php echo number_format($total_records); ?></span>
        </h5>
        <div class="btn-group" role="group">
            <button class="btn-export" onclick="exportTableToCSV('salesTable','sales.csv')">
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
        <?php if (empty($sales)): ?>
            <!-- Empty State -->
            <div class="empty-state-sales">
                <div class="empty-icon-wrap">
                    <svg width="36" height="36"><use href="#svg-file-text"/></svg>
                </div>
                <h5>فاکتوری یافت نشد</h5>
                <p>برای شروع، فاکتور جدیدی اضافه کنید</p>
                <?php if (hasPermission('add_sale')): ?>
                    <a href="sale_form.php" class="btn-add-sale">
                        <svg width="16" height="16"><use href="#svg-plus"/></svg>
                        افزودن فاکتور اول
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <table class="sales-table" id="salesTable">
                <thead>
                    <tr>
                        <th>شماره فاکتور</th>
                        <th>مشتری</th>
                        <th style="text-align:center;">مبلغ نهایی</th>
                        <th>تاریخ فروش</th>
                        <th style="text-align:center;">وضعیت</th>
                        <th style="text-align:center;">وضعیت پرداخت</th>
                        <th>ثبت‌کننده</th>
                        <th style="text-align:center;">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $sale): ?>
                        <tr>
                            <!-- Invoice Number -->
                            <td>
                                <span class="badge-invoice">
                                    <svg width="12" height="12"><use href="#svg-file-text"/></svg>
                                    <?php echo htmlspecialchars($sale['invoice_number']); ?>
                                </span>
                            </td>

                            <!-- Customer -->
                            <td>
                                <div class="cell-customer">
                                    <div class="avatar-md">
                                        <svg width="18" height="18"><use href="#svg-user"/></svg>
                                    </div>
                                    <div>
                                        <div class="customer-name"><?php echo htmlspecialchars($sale['customer_name'] ?: 'نامشخص'); ?></div>
                                        <?php if ($sale['company_name']): ?>
                                            <div class="customer-company">
                                                <svg width="11" height="11"><use href="#svg-building"/></svg>
                                                <?php echo htmlspecialchars($sale['company_name']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>

                            <!-- Final Amount -->
                            <td style="text-align:center;">
                                <div class="cell-amount">
                                    <?php echo formatMoney($sale['final_amount']); ?>
                                </div>
                            </td>

                            <!-- Sale Date -->
                            <td>
                                <div class="cell-date">
                                    <svg width="13" height="13"><use href="#svg-calendar"/></svg>
                                    <span><?php echo formatPersianDate($sale['sale_date'], 'Y/m/d'); ?></span>
                                </div>
                            </td>

                            <!-- Status -->
                            <td style="text-align:center;">
                                <span class="badge-2026 badge-status-<?php echo htmlspecialchars($sale['status']); ?>">
                                    <?php echo getStatusTitle($sale['status']); ?>
                                </span>
                            </td>

                            <!-- Payment Status -->
                            <td style="text-align:center;">
                                <span class="badge-2026 badge-payment-<?php echo htmlspecialchars($sale['payment_status']); ?>">
                                    <?php echo getStatusTitle($sale['payment_status']); ?>
                                </span>
                            </td>

                            <!-- Created By -->
                            <td>
                                <?php if ($sale['created_user']): ?>
                                    <small style="color:var(--text-2);">
                                        <?php echo htmlspecialchars($sale['created_user']); ?>
                                    </small>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Actions -->
                            <td style="text-align:center;">
                                <div class="actions-group" style="justify-content:center;">
                                    <!-- View -->
                                    <a href="invoice.php?id=<?php echo $sale['id']; ?>" class="btn-action btn-action--view"
                                       title="مشاهده فاکتور">
                                        <svg width="15" height="15"><use href="#svg-eye"/></svg>
                                    </a>
                                    <!-- Edit -->
                                    <?php if (hasPermission('edit_sale') && $sale['status'] !== 'completed'): ?>
                                        <a href="sale_form.php?id=<?php echo $sale['id']; ?>" class="btn-action btn-action--edit"
                                           title="ویرایش">
                                            <svg width="15" height="15"><use href="#svg-edit"/></svg>
                                        </a>
                                    <?php endif; ?>
                                    <!-- Delete -->
                                    <?php if (hasPermission('delete_sale')): ?>
                                        <button class="btn-action btn-action--delete"
                                                onclick="deleteSale(<?php echo $sale['id']; ?>, '<?php echo htmlspecialchars($sale['invoice_number'], ENT_QUOTES); ?>')"
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
                        $base_url = 'sales.php?' . http_build_query(array_filter([
                            'search'         => $search,
                            'status'         => $status,
                            'payment_status' => $payment_status,
                            'date_from'      => $date_from,
                            'date_to'        => $date_to
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
function deleteSale(saleId, invoiceNumber) {
    confirmDelete(`آیا از حذف فاکتور "${invoiceNumber}" مطمئن هستید؟`).then((confirmed) => {
        if (confirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action"     value="delete">
                <input type="hidden" name="sale_id"    value="${saleId}">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Initialize table sorting
document.addEventListener('DOMContentLoaded', function() {
    initTableSort('salesTable');
});
</script>

<?php include __DIR__ . '/../private/footer.php'; ?>
