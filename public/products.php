<?php
$page_title = 'مدیریت محصولات';
$breadcrumb = [
    ['title' => 'داشبورد', 'url' => 'dashboard.php'],
    ['title' => 'محصولات']
];

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/database.php';
require_once __DIR__ . '/../private/auth.php';
require_once __DIR__ . '/../private/functions.php';

// بررسی دسترسی
if (!hasPermission('view_products')) {
    setMessage('شما دسترسی لازم برای مشاهده این صفحه را ندارید', 'error');
    header('Location: dashboard.php');
    exit();
}

// پردازش درخواست‌ها
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete' && hasPermission('delete_product')) {
        $product_id = (int)$_POST['product_id'];

        try {
            // بررسی استفاده در فروش
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sale_items WHERE product_id = ?");
            $stmt->execute([$product_id]);
            $usage_count = $stmt->fetchColumn();

            if ($usage_count > 0) {
                setMessage('این محصول در فروش‌ها استفاده شده و قابل حذف نیست', 'error');
            } else {
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$product_id]);

                if ($stmt->rowCount() > 0) {
                    logActivity($_SESSION['user_id'], 'delete_product', 'products', $product_id);
                    setMessage('محصول با موفقیت حذف شد', 'success');
                } else {
                    setMessage('محصول یافت نشد', 'error');
                }
            }
        } catch (PDOException $e) {
            error_log("خطا در حذف محصول: " . $e->getMessage());
            setMessage('خطا در حذف محصول', 'error');
        }
    }

    if ($action === 'update_stock' && hasPermission('edit_product')) {
        $product_id = (int)$_POST['product_id'];
        $new_stock = (int)$_POST['new_stock'];
        $note = sanitizeInput($_POST['note'] ?? '');

        try {
            $stmt = $pdo->prepare("UPDATE products SET stock_quantity = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$new_stock, $product_id]);

            if ($stmt->rowCount() > 0) {
                logActivity($_SESSION['user_id'], 'update_product_stock', 'products', $product_id, [
                    'new_stock' => $new_stock,
                    'note' => $note
                ]);
                setMessage('موجودی محصول بروزرسانی شد', 'success');
            }
        } catch (PDOException $e) {
            error_log("خطا در بروزرسانی موجودی: " . $e->getMessage());
            setMessage('خطا در بروزرسانی موجودی', 'error');
        }
    }
}

// دریافت فیلترها
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';
$low_stock = isset($_GET['low_stock']) ? (bool)$_GET['low_stock'] : false;
$page = (int)($_GET['page'] ?? 1);
$per_page = RECORDS_PER_PAGE;
$offset = ($page - 1) * $per_page;

// ساخت کوئری
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.description LIKE ? OR p.barcode LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

if ($category) {
    $where_conditions[] = "p.category = ?";
    $params[] = $category;
}

if ($status) {
    $where_conditions[] = "p.status = ?";
    $params[] = $status;
}

if ($low_stock) {
    $where_conditions[] = "p.stock_quantity <= p.min_stock_level AND p.min_stock_level > 0";
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// دریافت تعداد کل رکوردها
$count_sql = "SELECT COUNT(*) FROM products p $where_clause";
$total_records = $pdo->prepare($count_sql);
$total_records->execute($params);
$total_records = $total_records->fetchColumn();

// دریافت محصولات
$sql = "
    SELECT
        p.*,
        CONCAT(u.first_name, ' ', u.last_name) as created_user,
        COALESCE((SELECT SUM(si.quantity) FROM sale_items si WHERE si.product_id = p.id), 0) as total_sold
    FROM products p
    LEFT JOIN users u ON p.created_by = u.id
    $where_clause
    ORDER BY p.created_at DESC
    LIMIT $per_page OFFSET $offset
";

$products = $pdo->prepare($sql);
$products->execute($params);
$products = $products->fetchAll();

// دریافت دسته‌بندی‌ها
$categories = $pdo->query("
    SELECT DISTINCT category
    FROM products
    WHERE category IS NOT NULL AND category != ''
    ORDER BY category
")->fetchAll(PDO::FETCH_COLUMN);

// آمار محصولات
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'active' => $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn(),
    'low_stock' => $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= min_stock_level AND min_stock_level > 0")->fetchColumn(),
    'out_of_stock' => $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity = 0")->fetchColumn(),
    'total_value' => $pdo->query("SELECT COALESCE(SUM(stock_quantity * cost_price), 0) FROM products WHERE status = 'active'")->fetchColumn(),
];

include __DIR__ . '/../private/header.php';
?>

<!-- ========== SVG Sprite (hidden) ========== -->
<svg style="display:none;" width="0" height="0">
    <!-- box (products) -->
    <symbol id="svg-box" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>
    </symbol>
    <!-- package -->
    <symbol id="svg-package" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>
    </symbol>
    <!-- check-circle -->
    <symbol id="svg-check-circle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
    </symbol>
    <!-- alert-triangle -->
    <symbol id="svg-alert-triangle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
    </symbol>
    <!-- x-circle -->
    <symbol id="svg-x-circle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
    </symbol>
    <!-- dollar-sign -->
    <symbol id="svg-dollar-sign" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
    </symbol>
    <!-- plus -->
    <symbol id="svg-plus" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
    </symbol>
    <!-- search -->
    <symbol id="svg-search" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
    </symbol>
    <!-- filter -->
    <symbol id="svg-filter" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
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
    <!-- barcode -->
    <symbol id="svg-barcode" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 5v14"/><path d="M8 5v14"/><path d="M12 5v14"/><path d="M17 5v14"/><path d="M21 5v14"/>
    </symbol>
    <!-- layers -->
    <symbol id="svg-layers" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>
    </symbol>
    <!-- trending-up -->
    <symbol id="svg-trending-up" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>
    </symbol>
</svg>

<!-- ========== Products Page Styles ========== -->
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
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
.icon-green   { background: #10b981; }
.icon-amber   { background: #f59e0b; }
.icon-red     { background: #ef4444; }
.icon-blue    { background: #3b82f6; }

/* ---------- Page Header ---------- */
.products-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.products-page-header h4 {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-1);
    margin: 0 0 4px;
}

.products-page-header p {
    font-size: 14px;
    color: var(--text-3);
    margin: 0;
}

/* Buttons */
.btn-add-product {
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

.btn-add-product:hover {
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
    margin-bottom: 24px;
    box-shadow: var(--shadow-sm);
}

.filter-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr auto;
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

.filter-checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    padding-top: 19px;
}

.filter-checkbox input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: var(--teal);
}

.filter-checkbox label {
    margin: 0;
    cursor: pointer;
    user-select: none;
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
.products-table {
    width: 100%;
    border-collapse: collapse;
    font-family: 'Vazirmatn', sans-serif;
}

.products-table thead th {
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

.products-table tbody tr {
    border-bottom: 1px solid var(--border);
    transition: background .18s var(--ease);
}

.products-table tbody tr:last-child { border-bottom: none; }

.products-table tbody tr:hover {
    background: var(--teal-50);
}

.products-table tbody td {
    padding: 16px 18px;
    font-size: 14px;
    color: var(--text-1);
    vertical-align: middle;
}

/* Product name cell */
.cell-product-name {
    display: flex;
    align-items: center;
    gap: 12px;
}

.product-icon-wrap {
    width: 48px;
    height: 48px;
    flex-shrink: 0;
    background: var(--teal-bg);
    border-radius: var(--r-md);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--teal);
    transition: background .2s, color .2s;
}

.products-table tbody tr:hover .product-icon-wrap {
    background: var(--teal);
    color: #fff;
}

.product-name-text {
    flex: 1;
}

.product-name-text .name {
    font-weight: 600;
    font-size: 14px;
    color: var(--text-1);
    margin-bottom: 2px;
}

.product-name-text .sku {
    font-size: 12px;
    color: var(--text-3);
}

/* Stock cell */
.cell-stock {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.stock-value {
    font-weight: 600;
    font-size: 14px;
}

.stock-value.normal { color: var(--text-1); }
.stock-value.low    { color: #f59e0b; }
.stock-value.out    { color: #ef4444; }

.stock-bar-wrap {
    width: 100px;
    height: 6px;
    background: var(--border);
    border-radius: 3px;
    overflow: hidden;
}

.stock-bar {
    height: 100%;
    background: var(--teal);
    border-radius: 3px;
    transition: width .3s var(--ease);
}

.stock-bar.low { background: #f59e0b; }
.stock-bar.out { background: #ef4444; }

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

.badge-status-active       { background: #f0fdf4; color: #16a34a; }
.badge-status-inactive     { background: #f3f4f6; color: #6b7280; }
.badge-status-discontinued { background: #fef2f2; color: #dc2626; }

.badge-category {
    background: #eff6ff;
    color: #2563eb;
    font-size: 11px;
    padding: 4px 10px;
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
.empty-state-products {
    text-align: center;
    padding: 72px 24px;
}

.empty-state-products .empty-icon-wrap {
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

.empty-state-products h5 {
    color: var(--text-1);
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 6px;
}

.empty-state-products p {
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
    .products-page-header { flex-direction: column; align-items: flex-start; }
    .stats-row-2026 { grid-template-columns: 1fr; }

    .table-card {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .products-table { min-width: 1200px; }
}
</style>

<!-- ========== Stats Row ========== -->
<div class="stats-row-2026">
    <!-- Total Products -->
    <div class="stat-card-2026">
        <div class="stat-content">
            <div class="stat-icon icon-teal">
                <svg width="24" height="24"><use href="#svg-box"/></svg>
            </div>
            <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
            <div class="stat-label">کل محصولات</div>
        </div>
    </div>

    <!-- Active -->
    <div class="stat-card-2026">
        <div class="stat-content">
            <div class="stat-icon icon-green">
                <svg width="24" height="24"><use href="#svg-check-circle"/></svg>
            </div>
            <div class="stat-value"><?php echo number_format($stats['active']); ?></div>
            <div class="stat-label">محصولات فعال</div>
        </div>
    </div>

    <!-- Low Stock -->
    <div class="stat-card-2026">
        <div class="stat-content">
            <div class="stat-icon icon-amber">
                <svg width="24" height="24"><use href="#svg-alert-triangle"/></svg>
            </div>
            <div class="stat-value"><?php echo number_format($stats['low_stock']); ?></div>
            <div class="stat-label">موجودی کم</div>
        </div>
    </div>

    <!-- Out of Stock -->
    <div class="stat-card-2026">
        <div class="stat-content">
            <div class="stat-icon icon-red">
                <svg width="24" height="24"><use href="#svg-x-circle"/></svg>
            </div>
            <div class="stat-value"><?php echo number_format($stats['out_of_stock']); ?></div>
            <div class="stat-label">ناموجود</div>
        </div>
    </div>

    <!-- Total Value -->
    <div class="stat-card-2026">
        <div class="stat-content">
            <div class="stat-icon icon-blue">
                <svg width="24" height="24"><use href="#svg-dollar-sign"/></svg>
            </div>
            <div class="stat-value"><?php echo formatMoney($stats['total_value'], 0); ?></div>
            <div class="stat-label">ارزش کل انبار</div>
        </div>
    </div>
</div>

<!-- ========== Page Header ========== -->
<div class="products-page-header">
    <div>
        <h4>مدیریت محصولات</h4>
        <p>مشاهده و مدیریت محصولات انبار</p>
    </div>
    <div>
        <?php if (hasPermission('add_product')): ?>
            <a href="product_form.php" class="btn-add-product">
                <svg width="16" height="16"><use href="#svg-plus"/></svg>
                افزودن محصول جدید
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
                           placeholder="نام، کد، بارکد…">
                    <span class="search-icon">
                        <svg width="16" height="16"><use href="#svg-search"/></svg>
                    </span>
                </div>
            </div>

            <!-- Category -->
            <div class="filter-group">
                <label>دسته‌بندی</label>
                <select class="form-select" name="category">
                    <option value="">همه</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>"
                                <?php echo $category === $cat ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status -->
            <div class="filter-group">
                <label>وضعیت</label>
                <select class="form-select" name="status">
                    <option value="">همه</option>
                    <option value="active"       <?php echo $status === 'active'       ? 'selected' : ''; ?>>فعال</option>
                    <option value="inactive"     <?php echo $status === 'inactive'     ? 'selected' : ''; ?>>غیرفعال</option>
                    <option value="discontinued" <?php echo $status === 'discontinued' ? 'selected' : ''; ?>>متوقف شده</option>
                </select>
            </div>

            <!-- Low Stock Filter -->
            <div class="filter-group">
                <div class="filter-checkbox">
                    <input type="checkbox" id="low_stock" name="low_stock" value="1"
                           <?php echo $low_stock ? 'checked' : ''; ?>>
                    <label for="low_stock">فقط موجودی کم</label>
                </div>
            </div>

            <!-- Submit -->
            <div class="filter-group" style="padding-top:19px;">
                <button type="submit" class="btn-filter-submit">
                    <svg width="15" height="15"><use href="#svg-filter"/></svg>
                    فیلتر
                </button>
            </div>
        </div>
    </form>
</div>

<!-- ========== Products Table ========== -->
<div class="table-card">
    <!-- Header -->
    <div class="table-card-header">
        <h5>
            <svg width="20" height="20"><use href="#svg-package"/></svg>
            لیست محصولات
            <span class="badge-count"><?php echo number_format($total_records); ?></span>
        </h5>
        <div class="btn-group" role="group">
            <button class="btn-export" onclick="exportTableToCSV('productsTable','products.csv')">
                <svg width="14" height="14"><use href="#svg-download"/></svg>
                خروجی CSV
            </button>
        </div>
    </div>

    <!-- Body -->
    <div style="overflow-x:auto;">
        <?php if (empty($products)): ?>
            <!-- Empty State -->
            <div class="empty-state-products">
                <div class="empty-icon-wrap">
                    <svg width="36" height="36"><use href="#svg-box"/></svg>
                </div>
                <h5>محصولی یافت نشد</h5>
                <p>برای شروع، محصول جدیدی به انبار اضافه کنید</p>
                <?php if (hasPermission('add_product')): ?>
                    <a href="product_form.php" class="btn-add-product">
                        <svg width="16" height="16"><use href="#svg-plus"/></svg>
                        افزودن محصول اول
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <table class="products-table" id="productsTable">
                <thead>
                    <tr>
                        <th>محصول</th>
                        <th>دسته‌بندی</th>
                        <th style="text-align:center;">قیمت فروش</th>
                        <th style="text-align:center;">قیمت خرید</th>
                        <th>موجودی</th>
                        <th style="text-align:center;">فروش رفته</th>
                        <th style="text-align:center;">وضعیت</th>
                        <th style="text-align:center;">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <?php
                        $low_stock = $product['stock_quantity'] <= $product['min_stock_level'] && $product['min_stock_level'] > 0;
                        $out_of_stock = $product['stock_quantity'] == 0;

                        $stock_class = 'normal';
                        if ($out_of_stock) $stock_class = 'out';
                        elseif ($low_stock) $stock_class = 'low';

                        $stock_percent = 100;
                        if ($product['min_stock_level'] > 0) {
                            $stock_percent = min(100, ($product['stock_quantity'] / $product['min_stock_level']) * 100);
                        }
                        ?>
                        <tr>
                            <!-- Product Name + SKU -->
                            <td>
                                <div class="cell-product-name">
                                    <div class="product-icon-wrap">
                                        <svg width="20" height="20"><use href="#svg-box"/></svg>
                                    </div>
                                    <div class="product-name-text">
                                        <div class="name"><?php echo htmlspecialchars($product['name']); ?></div>
                                        <div class="sku">
                                            <svg width="10" height="10" style="display:inline;vertical-align:middle;margin-left:4px;"><use href="#svg-barcode"/></svg>
                                            <?php echo htmlspecialchars($product['sku']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Category -->
                            <td>
                                <?php if ($product['category']): ?>
                                    <span class="badge-category">
                                        <svg width="10" height="10" style="display:inline;vertical-align:middle;margin-left:3px;"><use href="#svg-layers"/></svg>
                                        <?php echo htmlspecialchars($product['category']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Price -->
                            <td style="text-align:center;">
                                <span style="font-weight:600;color:#10b981;">
                                    <?php echo formatMoney($product['price'], 0); ?>
                                </span>
                            </td>

                            <!-- Cost Price -->
                            <td style="text-align:center;">
                                <?php if ($product['cost_price'] > 0): ?>
                                    <span style="font-weight:500;color:var(--text-3);">
                                        <?php echo formatMoney($product['cost_price'], 0); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Stock -->
                            <td>
                                <div class="cell-stock">
                                    <div class="stock-value <?php echo $stock_class; ?>">
                                        <?php echo number_format($product['stock_quantity']); ?>
                                        <?php if ($product['unit']): ?>
                                            <span style="font-size:11px;color:var(--text-muted);">
                                                <?php echo htmlspecialchars($product['unit']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($product['min_stock_level'] > 0): ?>
                                        <div class="stock-bar-wrap">
                                            <div class="stock-bar <?php echo $stock_class; ?>"
                                                 style="width:<?php echo $stock_percent; ?>%;"></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- Total Sold -->
                            <td style="text-align:center;">
                                <?php if ($product['total_sold'] > 0): ?>
                                    <span style="display:inline-flex;align-items:center;gap:4px;font-weight:500;color:var(--text-2);">
                                        <svg width="12" height="12" style="color:#10b981;"><use href="#svg-trending-up"/></svg>
                                        <?php echo number_format($product['total_sold']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Status -->
                            <td style="text-align:center;">
                                <span class="badge-2026 badge-status-<?php echo htmlspecialchars($product['status']); ?>">
                                    <?php
                                    $status_titles = [
                                        'active' => 'فعال',
                                        'inactive' => 'غیرفعال',
                                        'discontinued' => 'متوقف شده'
                                    ];
                                    echo $status_titles[$product['status']] ?? $product['status'];
                                    ?>
                                </span>
                            </td>

                            <!-- Actions -->
                            <td style="text-align:center;">
                                <div class="actions-group" style="justify-content:center;">
                                    <!-- View -->
                                    <a href="product_view.php?id=<?php echo $product['id']; ?>" class="btn-action btn-action--view"
                                       title="مشاهده جزئیات">
                                        <svg width="15" height="15"><use href="#svg-eye"/></svg>
                                    </a>
                                    <!-- Edit -->
                                    <?php if (hasPermission('edit_product')): ?>
                                        <a href="product_form.php?id=<?php echo $product['id']; ?>" class="btn-action btn-action--edit"
                                           title="ویرایش">
                                            <svg width="15" height="15"><use href="#svg-edit"/></svg>
                                        </a>
                                    <?php endif; ?>
                                    <!-- Delete -->
                                    <?php if (hasPermission('delete_product')): ?>
                                        <button class="btn-action btn-action--delete"
                                                onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>')"
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
                        $base_url = 'products.php?' . http_build_query(array_filter([
                            'search'    => $search,
                            'category'  => $category,
                            'status'    => $status,
                            'low_stock' => $low_stock ? '1' : ''
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
function deleteProduct(productId, productName) {
    confirmDelete(`آیا از حذف محصول "${productName}" مطمئن هستید؟\n\nتوجه: اگر این محصول در فروش‌ها استفاده شده، قابل حذف نیست.`).then((confirmed) => {
        if (confirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action"      value="delete">
                <input type="hidden" name="product_id"  value="${productId}">
                <input type="hidden" name="csrf_token"  value="<?php echo generateCSRFToken(); ?>">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Initialize table sorting
document.addEventListener('DOMContentLoaded', function() {
    initTableSort('productsTable');
});
</script>

<?php include __DIR__ . '/../private/footer.php'; ?>
