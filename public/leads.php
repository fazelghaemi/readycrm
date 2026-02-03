<?php
$page_title = 'مدیریت لیدها';
$breadcrumb = [
    ['title' => 'داشبورد', 'url' => 'dashboard.php'],
    ['title' => 'لیدها']
];

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/database.php';
require_once __DIR__ . '/../private/auth.php';
require_once __DIR__ . '/../private/functions.php';

// بررسی دسترسی
if (!hasPermission('view_leads')) {
    setMessage('شما دسترسی لازم برای مشاهده این صفحه را ندارید', 'error');
    header('Location: dashboard.php');
    exit();
}

// پردازش درخواست‌ها
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete' && hasPermission('delete_lead')) {
        $lead_id = (int)$_POST['lead_id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM leads WHERE id = ?");
            $stmt->execute([$lead_id]);

            if ($stmt->rowCount() > 0) {
                logActivity($_SESSION['user_id'], 'delete_lead', 'leads', $lead_id);
                setMessage('لید با موفقیت حذف شد', 'success');
            } else {
                setMessage('لید یافت نشد', 'error');
            }
        } catch (PDOException $e) {
            error_log("خطا در حذف لید: " . $e->getMessage());
            setMessage('خطا در حذف لید', 'error');
        }
    }

    if ($action === 'update_status' && hasPermission('edit_lead')) {
        $lead_id = (int)$_POST['lead_id'];
        $new_status = $_POST['new_status'];

        try {
            $stmt = $pdo->prepare("UPDATE leads SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $lead_id]);

            if ($stmt->rowCount() > 0) {
                logActivity($_SESSION['user_id'], 'update_lead_status', 'leads', $lead_id, ['status' => $new_status]);
                setMessage('وضعیت لید بروزرسانی شد', 'success');
            }
        } catch (PDOException $e) {
            error_log("خطا در بروزرسانی وضعیت لید: " . $e->getMessage());
            setMessage('خطا در بروزرسانی وضعیت', 'error');
        }
    }
}

// دریافت فیلترها
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$priority = $_GET['priority'] ?? '';
$assigned_to = $_GET['assigned_to'] ?? '';
$source = $_GET['source'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$per_page = RECORDS_PER_PAGE;
$offset = ($page - 1) * $per_page;

// ساخت کوئری
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(l.first_name LIKE ? OR l.last_name LIKE ? OR l.email LIKE ? OR l.phone LIKE ? OR l.company LIKE ? OR l.title LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term, $search_term, $search_term]);
}

if ($status) {
    $where_conditions[] = "l.status = ?";
    $params[] = $status;
}

if ($priority) {
    $where_conditions[] = "l.priority = ?";
    $params[] = $priority;
}

if ($assigned_to) {
    $where_conditions[] = "l.assigned_to = ?";
    $params[] = $assigned_to;
}

if ($source) {
    $where_conditions[] = "l.source = ?";
    $params[] = $source;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// دریافت تعداد کل رکوردها
$count_sql = "SELECT COUNT(*) FROM leads l $where_clause";
$total_records = $pdo->prepare($count_sql);
$total_records->execute($params);
$total_records = $total_records->fetchColumn();

// دریافت لیدها
$sql = "
    SELECT
        l.*,
        CONCAT(u.first_name, ' ', u.last_name) as assigned_user,
        CONCAT(cb.first_name, ' ', cb.last_name) as created_user
    FROM leads l
    LEFT JOIN users u ON l.assigned_to = u.id
    LEFT JOIN users cb ON l.created_by = cb.id
    $where_clause
    ORDER BY
        CASE l.priority
            WHEN 'urgent' THEN 1
            WHEN 'high' THEN 2
            WHEN 'medium' THEN 3
            WHEN 'low' THEN 4
        END,
        l.created_at DESC
    LIMIT $per_page OFFSET $offset
";

$leads = $pdo->prepare($sql);
$leads->execute($params);
$leads = $leads->fetchAll();

// دریافت کاربران برای فیلتر
$users = $pdo->query("SELECT id, first_name, last_name FROM users WHERE status = 'active' ORDER BY first_name")->fetchAll();

// دریافت منابع
$sources_result = $pdo->query("SELECT DISTINCT source FROM leads WHERE source IS NOT NULL AND source != '' ORDER BY source")->fetchAll();
$sources = array_column($sources_result, 'source');

// آمار لیدها
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn(),
    'new' => $pdo->query("SELECT COUNT(*) FROM leads WHERE status = 'new'")->fetchColumn(),
    'contacted' => $pdo->query("SELECT COUNT(*) FROM leads WHERE status = 'contacted'")->fetchColumn(),
    'qualified' => $pdo->query("SELECT COUNT(*) FROM leads WHERE status = 'qualified'")->fetchColumn(),
    'won' => $pdo->query("SELECT COUNT(*) FROM leads WHERE status = 'won'")->fetchColumn(),
    'lost' => $pdo->query("SELECT COUNT(*) FROM leads WHERE status = 'lost'")->fetchColumn(),
];

include __DIR__ . '/../private/header.php';
?>

<!-- ========== SVG Sprite (hidden) ========== -->
<svg style="display:none;" width="0" height="0">
    <!-- target / bullseye -->
    <symbol id="svg-target" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>
    </symbol>
    <!-- user -->
    <symbol id="svg-user" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="8" r="4"/>
    </symbol>
    <!-- users -->
    <symbol id="svg-users" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
    </symbol>
    <!-- search -->
    <symbol id="svg-search" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
    </symbol>
    <!-- plus -->
    <symbol id="svg-plus" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
    </symbol>
    <!-- download -->
    <symbol id="svg-download" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
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
    <!-- tag -->
    <symbol id="svg-tag" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20.59 6.69a4.83 4.83 0 0 1 0 6.77l-6.77 6.77a4.83 4.83 0 0 1-6.77 0l-6.77-6.77a4.83 4.83 0 0 1 0-6.77l6.77-6.77a4.83 4.83 0 0 1 6.77 0z"/><circle cx="9.5" cy="9.5" r="1.5"/>
    </symbol>
    <!-- mail -->
    <symbol id="svg-mail" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
    </symbol>
    <!-- phone -->
    <symbol id="svg-phone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
    </symbol>
    <!-- building -->
    <symbol id="svg-building" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
    </symbol>
    <!-- user-tie -->
    <symbol id="svg-user-tie" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="8" r="4"/><polyline points="12 14 14 17 12 21 10 17"/>
    </symbol>
    <!-- calendar -->
    <symbol id="svg-calendar" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
    </symbol>
    <!-- arrow-left (for RTL "view all") -->
    <symbol id="svg-arrow-left" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
    </symbol>
    <!-- check-circle (empty state) -->
    <symbol id="svg-check-circle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
    </symbol>
    <!-- sort (default) -->
    <symbol id="svg-sort" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="6 9 12 3 18 9"/><polyline points="18 15 12 21 6 15"/>
    </symbol>
    <!-- chevron-down -->
    <symbol id="svg-chevron-down" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="6 9 12 15 18 9"/>
    </symbol>
    <!-- filter -->
    <symbol id="svg-filter" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
    </symbol>
</svg>

<!-- ========== Leads Page Styles ========== -->
<style>
/* ---------- Design Tokens (match dashboard 2026) ---------- */
:root {
    --teal:        #14b8a6;
    --teal-light:  #5eead4;
    --teal-dark:   #0d9488;
    --teal-bg:     #ccfbf1;         /* icon bg */
    --teal-50:     #f0fdfa;         /* very light tint */

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

/* ---------- Stat Cards Row ---------- */
.leads-stats-row {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 16px;
    margin-bottom: 28px;
}

.leads-stat-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: var(--r-lg);
    padding: 20px 16px;
    text-align: center;
    transition: transform .25s var(--ease), box-shadow .25s var(--ease), border-color .25s var(--ease);
    position: relative;
    overflow: hidden;
}

.leads-stat-card::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 3px;
    background: var(--teal);
    transform: scaleX(0);
    transition: transform .3s var(--ease);
}

.leads-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
    border-color: var(--teal-light);
}

.leads-stat-card:hover::after {
    transform: scaleX(1);
}

.leads-stat-card .stat-val {
    font-size: 24px;
    font-weight: 700;
    line-height: 1.2;
    margin-bottom: 4px;
}

.leads-stat-card .stat-lbl {
    font-size: 12px;
    color: var(--text-3);
    font-weight: 500;
}

/* colour helpers for stat values */
.c-teal   { color: var(--teal); }
.c-info   { color: #0ea5e9; }
.c-warn   { color: #f59e0b; }
.c-muted  { color: var(--text-3); }
.c-green  { color: #16a34a; }
.c-red    { color: #dc2626; }

/* ---------- Page Header ---------- */
.leads-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.leads-page-header h4 {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-1);
    margin: 0 0 4px;
}

.leads-page-header p {
    font-size: 14px;
    color: var(--text-3);
    margin: 0;
}

/* Add Lead Button */
.btn-add-lead {
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

.btn-add-lead:hover {
    background: var(--teal-dark);
    color: #fff;
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.btn-add-lead svg { color: #fff; }

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
    grid-template-columns: 2fr 1.4fr 1.4fr 1.4fr 1.4fr auto;
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

/* CSV export button */
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
.leads-table {
    width: 100%;
    border-collapse: collapse;
    font-family: 'Vazirmatn', sans-serif;
}

.leads-table thead th {
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

.leads-table thead th[data-sort] {
    cursor: pointer;
    transition: color .2s;
}

.leads-table thead th[data-sort]:hover {
    color: var(--teal);
}

.leads-table thead th .sort-icon {
    display: inline-block;
    margin-left: 6px;
    vertical-align: middle;
    opacity: .4;
    transition: opacity .2s, transform .2s;
}

.leads-table thead th[data-sort]:hover .sort-icon { opacity: 1; color: var(--teal); }
.leads-table thead th.sort-asc .sort-icon,
.leads-table thead th.sort-desc .sort-icon { opacity: 1; color: var(--teal); }

.leads-table tbody tr {
    border-bottom: 1px solid var(--border);
    transition: background .18s var(--ease);
}

.leads-table tbody tr:last-child { border-bottom: none; }

.leads-table tbody tr:hover {
    background: var(--teal-50);
}

.leads-table tbody td {
    padding: 16px 18px;
    font-size: 14px;
    color: var(--text-1);
    vertical-align: middle;
}

/* ---------- Cell Helpers ---------- */
.cell-title .title-main {
    font-weight: 700;
    color: var(--text-1);
    font-size: 14px;
}

.cell-title .title-source {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    color: var(--text-3);
    margin-top: 3px;
}

.cell-title .title-source svg { color: var(--teal); }

/* Name cell */
.cell-name {
    display: flex;
    align-items: center;
    gap: 10px;
}

.avatar-sm {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: var(--teal-bg);
    color: var(--teal);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: background .2s, color .2s;
}

.leads-table tbody tr:hover .avatar-sm {
    background: var(--teal);
    color: #fff;
}

.cell-name .name-main  { font-weight: 600; font-size: 14px; color: var(--text-1); }
.cell-name .name-pos   { font-size: 12px; color: var(--text-3); margin-top: 1px; }

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
.contact-icon-email { color: var(--teal); }
.contact-icon-phone { color: #16a34a; }

/* Value cell */
.cell-value { font-weight: 700; color: var(--teal); font-size: 15px; }

/* Progress bar */
.progress-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
}

.progress-bar-2026 {
    flex: 1;
    height: 7px;
    background: var(--border);
    border-radius: 4px;
    overflow: hidden;
}

.progress-bar-2026 .fill {
    height: 100%;
    background: linear-gradient(90deg, var(--teal), var(--teal-light));
    border-radius: 4px;
    transition: width .4s var(--ease);
}

.progress-wrap .pct { font-size: 13px; color: var(--text-3); font-weight: 600; min-width: 34px; }

/* ---------- Badges 2026 ---------- */
.badge-2026 {
    display: inline-flex;
    align-items: center;
    padding: 4px 11px;
    border-radius: var(--r-pill);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .3px;
    white-space: nowrap;
}

/* Priority colours */
.badge-urgent { background: #fef2f2; color: #dc2626; }
.badge-high   { background: #fff7ed; color: #ea580c; }
.badge-medium { background: #fefce8; color: #ca8a04; }
.badge-low    { background: #f0fdf4; color: #16a34a; }

/* Status colours */
.badge-status-new          { background: var(--teal-50);  color: var(--teal-dark); }
.badge-status-contacted    { background: #eff6ff;         color: #2563eb; }
.badge-status-qualified    { background: #fefce8;         color: #ca8a04; }
.badge-status-proposal     { background: #f3e8ff;         color: #9333ea; }
.badge-status-negotiation  { background: #fff7ed;         color: #ea580c; }
.badge-status-won          { background: #f0fdf4;         color: #16a34a; }
.badge-status-lost         { background: #fef2f2;         color: #dc2626; }

/* ---------- Status Dropdown ---------- */
.status-dropdown {
    position: relative;
    display: inline-block;
}

.status-dropdown .badge-2026 {
    cursor: pointer;
    user-select: none;
    transition: box-shadow .2s, transform .15s;
}

.status-dropdown .badge-2026:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,.12);
    transform: translateY(-1px);
}

.status-dropdown-menu {
    display: none;
    position: absolute;
    /* RTL: left side */
    left: 0;
    top: calc(100% + 6px);
    background: #fff;
    border: 1px solid var(--border);
    border-radius: var(--r-lg);
    box-shadow: var(--shadow-lg);
    min-width: 180px;
    z-index: 50;
    padding: 8px 0;
    animation: fadeDown .15s var(--ease);
}

@keyframes fadeDown {
    from { opacity:0; transform:translateY(-6px); }
    to   { opacity:1; transform:translateY(0); }
}

.status-dropdown.open .status-dropdown-menu { display: block; }

.status-dropdown-menu a {
    display: block;
    padding: 8px 16px;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-2);
    text-decoration: none;
    transition: background .15s, color .15s;
}

.status-dropdown-menu a:hover {
    background: var(--teal-50);
    color: var(--teal-dark);
}

.status-dropdown-menu .divider {
    height: 1px;
    background: var(--border);
    margin: 4px 0;
}

.status-dropdown-menu a.won-link:hover { background: #f0fdf4; color: #16a34a; }
.status-dropdown-menu a.lost-link:hover { background: #fef2f2; color: #dc2626; }

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
.empty-state-leads {
    text-align: center;
    padding: 72px 24px;
}

.empty-state-leads .empty-icon-wrap {
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

.empty-state-leads h5 {
    color: var(--text-1);
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 6px;
}

.empty-state-leads p {
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
    .leads-stats-row { grid-template-columns: repeat(3, 1fr); }
    .filter-row      { grid-template-columns: 1fr 1fr 1fr; }
}

@media (max-width: 768px) {
    .leads-stats-row { grid-template-columns: repeat(2, 1fr); }
    .filter-row      { grid-template-columns: 1fr; }
    .leads-page-header { flex-direction: column; align-items: flex-start; }

    .table-card {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .leads-table { min-width: 900px; }
}

@media (max-width: 480px) {
    .leads-stats-row { grid-template-columns: 1fr 1fr; }
}
</style>

<!-- ========== Stats Row ========== -->
<div class="leads-stats-row">
    <div class="leads-stat-card">
        <div class="stat-val c-teal"><?php echo number_format($stats['total']); ?></div>
        <div class="stat-lbl">کل لیدها</div>
    </div>
    <div class="leads-stat-card">
        <div class="stat-val c-info"><?php echo number_format($stats['new']); ?></div>
        <div class="stat-lbl">جدید</div>
    </div>
    <div class="leads-stat-card">
        <div class="stat-val c-warn"><?php echo number_format($stats['contacted']); ?></div>
        <div class="stat-lbl">تماس گرفته شده</div>
    </div>
    <div class="leads-stat-card">
        <div class="stat-val c-muted"><?php echo number_format($stats['qualified']); ?></div>
        <div class="stat-lbl">واجد شرایط</div>
    </div>
    <div class="leads-stat-card">
        <div class="stat-val c-green"><?php echo number_format($stats['won']); ?></div>
        <div class="stat-lbl">موفق</div>
    </div>
    <div class="leads-stat-card">
        <div class="stat-val c-red"><?php echo number_format($stats['lost']); ?></div>
        <div class="stat-lbl">از دست رفته</div>
    </div>
</div>

<!-- ========== Page Header ========== -->
<div class="leads-page-header">
    <div>
        <h4>مدیریت لیدها</h4>
        <p>مشاهده و مدیریت فرصت‌های فروش</p>
    </div>
    <?php if (hasPermission('add_lead')): ?>
        <a href="lead_form.php" class="btn-add-lead">
            <svg width="16" height="16"><use href="#svg-plus"/></svg>
            افزودن لید جدید
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
                           placeholder="نام، ایمیل، شرکت…">
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
                    <option value="new"         <?php echo $status === 'new'         ? 'selected' : ''; ?>>جدید</option>
                    <option value="contacted"   <?php echo $status === 'contacted'   ? 'selected' : ''; ?>>تماس گرفته شده</option>
                    <option value="qualified"   <?php echo $status === 'qualified'   ? 'selected' : ''; ?>>واجد شرایط</option>
                    <option value="proposal"    <?php echo $status === 'proposal'    ? 'selected' : ''; ?>>پیشنهاد ارسال شده</option>
                    <option value="negotiation" <?php echo $status === 'negotiation' ? 'selected' : ''; ?>>در حال مذاکره</option>
                    <option value="won"         <?php echo $status === 'won'         ? 'selected' : ''; ?>>موفق</option>
                    <option value="lost"        <?php echo $status === 'lost'        ? 'selected' : ''; ?>>از دست رفته</option>
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

            <!-- Source -->
            <div class="filter-group">
                <label>منبع</label>
                <select class="form-select" name="source">
                    <option value="">همه</option>
                    <?php foreach ($sources as $src): ?>
                        <option value="<?php echo htmlspecialchars($src); ?>"
                                <?php echo $source === $src ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($src); ?>
                        </option>
                    <?php endforeach; ?>
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

<!-- ========== Leads Table ========== -->
<div class="table-card">
    <!-- Header -->
    <div class="table-card-header">
        <h5>
            <svg width="20" height="20"><use href="#svg-target"/></svg>
            لیست لیدها
            <span class="badge-count"><?php echo number_format($total_records); ?></span>
        </h5>
        <button class="btn-export" onclick="exportTableToCSV('leadsTable','leads.csv')">
            <svg width="14" height="14"><use href="#svg-download"/></svg>
            خروجی CSV
        </button>
    </div>

    <!-- Body -->
    <div style="overflow-x:auto;">
        <?php if (empty($leads)): ?>
            <!-- Empty State -->
            <div class="empty-state-leads">
                <div class="empty-icon-wrap">
                    <svg width="36" height="36"><use href="#svg-target"/></svg>
                </div>
                <h5>لیدی یافت نشد</h5>
                <p>برای شروع، لید جدیدی اضافه کنید</p>
                <?php if (hasPermission('add_lead')): ?>
                    <a href="lead_form.php" class="btn-add-lead">
                        <svg width="16" height="16"><use href="#svg-plus"/></svg>
                        افزودن لید اول
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <table class="leads-table" id="leadsTable">
                <thead>
                    <tr>
                        <th data-sort="0">
                            عنوان
                            <svg class="sort-icon" width="12" height="12"><use href="#svg-sort"/></svg>
                        </th>
                        <th data-sort="1">
                            نام و نام خانوادگی
                            <svg class="sort-icon" width="12" height="12"><use href="#svg-sort"/></svg>
                        </th>
                        <th>شرکت</th>
                        <th>تماس</th>
                        <th data-sort="4">
                            ارزش
                            <svg class="sort-icon" width="12" height="12"><use href="#svg-sort"/></svg>
                        </th>
                        <th>احتمال</th>
                        <th>اولویت</th>
                        <th>وضعیت</th>
                        <th>مسئول</th>
                        <th>تاریخ بسته شدن</th>
                        <th style="text-align:center;">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leads as $lead): ?>
                        <tr>
                            <!-- Title -->
                            <td>
                                <div class="cell-title">
                                    <div class="title-main"><?php echo htmlspecialchars($lead['title']); ?></div>
                                    <?php if ($lead['source']): ?>
                                        <div class="title-source">
                                            <svg width="12" height="12"><use href="#svg-tag"/></svg>
                                            <?php echo htmlspecialchars($lead['source']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- Name -->
                            <td>
                                <div class="cell-name">
                                    <div class="avatar-sm">
                                        <svg width="16" height="16"><use href="#svg-user"/></svg>
                                    </div>
                                    <div>
                                        <div class="name-main"><?php echo htmlspecialchars($lead['first_name'] . ' ' . $lead['last_name']); ?></div>
                                        <?php if ($lead['position']): ?>
                                            <div class="name-pos"><?php echo htmlspecialchars($lead['position']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>

                            <!-- Company -->
                            <td>
                                <?php if ($lead['company']): ?>
                                    <span style="font-weight:600; color:var(--text-1);"><?php echo htmlspecialchars($lead['company']); ?></span>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Contact -->
                            <td>
                                <?php if ($lead['email'] || $lead['phone']): ?>
                                    <div class="cell-contact">
                                        <?php if ($lead['email']): ?>
                                            <div class="contact-row">
                                                <svg class="contact-icon-email" width="13" height="13"><use href="#svg-mail"/></svg>
                                                <span style="font-size:13px;"><?php echo htmlspecialchars($lead['email']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($lead['phone']): ?>
                                            <div class="contact-row">
                                                <svg class="contact-icon-phone" width="13" height="13"><use href="#svg-phone"/></svg>
                                                <span style="font-size:13px;"><?php echo formatPhone($lead['phone']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Value -->
                            <td>
                                <?php if ($lead['value'] > 0): ?>
                                    <div class="cell-value"><?php echo formatMoney($lead['value']); ?></div>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Probability -->
                            <td style="min-width:130px;">
                                <div class="progress-wrap">
                                    <div class="progress-bar-2026">
                                        <div class="fill" style="width:<?php echo (int)$lead['probability']; ?>%;"></div>
                                    </div>
                                    <span class="pct"><?php echo (int)$lead['probability']; ?>%</span>
                                </div>
                            </td>

                            <!-- Priority -->
                            <td>
                                <span class="badge-2026 badge-<?php echo htmlspecialchars($lead['priority']); ?>">
                                    <?php echo getPriorityTitle($lead['priority']); ?>
                                </span>
                            </td>

                            <!-- Status (dropdown) -->
                            <td>
                                <div class="status-dropdown" data-lead-id="<?php echo $lead['id']; ?>">
                                    <span class="badge-2026 badge-status-<?php echo htmlspecialchars($lead['status']); ?>"
                                          onclick="toggleStatusDropdown(this)">
                                        <?php echo getStatusTitle($lead['status'], 'lead'); ?>
                                    </span>
                                    <?php if (hasPermission('edit_lead')): ?>
                                        <div class="status-dropdown-menu">
                                            <a href="#" onclick="updateStatus(<?php echo $lead['id']; ?>, 'new')">جدید</a>
                                            <a href="#" onclick="updateStatus(<?php echo $lead['id']; ?>, 'contacted')">تماس گرفته شده</a>
                                            <a href="#" onclick="updateStatus(<?php echo $lead['id']; ?>, 'qualified')">واجد شرایط</a>
                                            <a href="#" onclick="updateStatus(<?php echo $lead['id']; ?>, 'proposal')">پیشنهاد ارسال شده</a>
                                            <a href="#" onclick="updateStatus(<?php echo $lead['id']; ?>, 'negotiation')">در حال مذاکره</a>
                                            <div class="divider"></div>
                                            <a href="#" class="won-link"  onclick="updateStatus(<?php echo $lead['id']; ?>, 'won')">✓ موفق</a>
                                            <a href="#" class="lost-link" onclick="updateStatus(<?php echo $lead['id']; ?>, 'lost')">✕ از دست رفته</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- Assigned User -->
                            <td>
                                <?php if ($lead['assigned_user']): ?>
                                    <div style="display:flex;align-items:center;gap:7px;font-size:13px;color:var(--text-2);">
                                        <svg width="14" height="14" style="color:var(--text-muted);"><use href="#svg-user-tie"/></svg>
                                        <?php echo htmlspecialchars($lead['assigned_user']); ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color:var(--text-muted); font-size:13px;">بدون مسئول</span>
                                <?php endif; ?>
                            </td>

                            <!-- Expected Close Date -->
                            <td>
                                <?php if ($lead['expected_close_date']): ?>
                                    <div style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text-3);">
                                        <svg width="14" height="14" style="color:var(--text-muted);"><use href="#svg-calendar"/></svg>
                                        <?php echo formatPersianDate($lead['expected_close_date'], 'Y/m/d'); ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Actions -->
                            <td style="text-align:center;">
                                <div class="actions-group" style="justify-content:center;">
                                    <!-- View -->
                                    <a href="lead_view.php?id=<?php echo $lead['id']; ?>" class="btn-action btn-action--view"
                                       title="مشاهده جزئیات">
                                        <svg width="15" height="15"><use href="#svg-eye"/></svg>
                                    </a>
                                    <!-- Edit -->
                                    <?php if (hasPermission('edit_lead')): ?>
                                        <a href="lead_form.php?id=<?php echo $lead['id']; ?>" class="btn-action btn-action--edit"
                                           title="ویرایش">
                                            <svg width="15" height="15"><use href="#svg-edit"/></svg>
                                        </a>
                                    <?php endif; ?>
                                    <!-- Delete -->
                                    <?php if (hasPermission('delete_lead')): ?>
                                        <button class="btn-action btn-action--delete"
                                                onclick="deleteLead(<?php echo $lead['id']; ?>, '<?php echo htmlspecialchars($lead['title'], ENT_QUOTES); ?>')"
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
                        $base_url = 'leads.php?' . http_build_query(array_filter([
                            'search'      => $search,
                            'status'      => $status,
                            'priority'    => $priority,
                            'assigned_to' => $assigned_to,
                            'source'      => $source
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
/* ---- Status Dropdown Toggle ---- */
function toggleStatusDropdown(badge) {
    const parent = badge.closest('.status-dropdown');
    // Close all others
    document.querySelectorAll('.status-dropdown.open').forEach(el => {
        if (el !== parent) el.classList.remove('open');
    });
    parent.classList.toggle('open');
}

// Close dropdowns on outside click
document.addEventListener('click', function (e) {
    if (!e.target.closest('.status-dropdown')) {
        document.querySelectorAll('.status-dropdown.open').forEach(el => el.classList.remove('open'));
    }
});

/* ---- Delete Lead (SweetAlert2) ---- */
function deleteLead(leadId, leadTitle) {
    confirmDelete(`آیا از حذف لید "${leadTitle}" مطمئن هستید؟`).then((confirmed) => {
        if (confirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action"      value="delete">
                <input type="hidden" name="lead_id"     value="${leadId}">
                <input type="hidden" name="csrf_token"  value="<?php echo generateCSRFToken(); ?>">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

/* ---- Update Status (form POST) ---- */
function updateStatus(leadId, newStatus) {
    // Close dropdown first
    document.querySelectorAll('.status-dropdown.open').forEach(el => el.classList.remove('open'));

    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action"      value="update_status">
        <input type="hidden" name="lead_id"     value="${leadId}">
        <input type="hidden" name="new_status"  value="${newStatus}">
        <input type="hidden" name="csrf_token"  value="<?php echo generateCSRFToken(); ?>">
    `;
    document.body.appendChild(form);
    form.submit();
}

/* ---- Table Sort (reuses footer helper) ---- */
document.addEventListener('DOMContentLoaded', function () {
    initTableSort('leadsTable');
});
</script>

<?php include __DIR__ . '/../private/footer.php'; ?>
