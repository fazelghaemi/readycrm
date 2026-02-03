<?php
$page_title = 'گزارش‌ها و آمار';
$breadcrumb = [
    ['title' => 'داشبورد', 'url' => 'dashboard.php'],
    ['title' => 'گزارش‌ها']
];

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/database.php';
require_once __DIR__ . '/../private/auth.php';
require_once __DIR__ . '/../private/functions.php';

// بررسی دسترسی
if (!hasPermission('view_reports')) {
    setMessage('شما دسترسی لازم برای مشاهده این صفحه را ندارید', 'error');
    header('Location: dashboard.php');
    exit();
}

// دریافت فیلترها
$period = $_GET['period'] ?? 'this_month';
$start_date_custom = $_GET['start_date'] ?? '';
$end_date_custom = $_GET['end_date'] ?? '';

// ✅ محاسبه صحیح بازه زمانی
if ($period === 'custom' && $start_date_custom && $end_date_custom) {
    $start_date = $start_date_custom;
    $end_date = $end_date_custom;
} else {
    switch ($period) {
        case 'today':
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d');
            break;
        case 'yesterday':
            $start_date = date('Y-m-d', strtotime('-1 day'));
            $end_date = date('Y-m-d', strtotime('-1 day'));
            break;
        case 'this_week':
            $start_date = date('Y-m-d', strtotime('monday this week'));
            $end_date = date('Y-m-d');
            break;
        case 'last_week':
            $start_date = date('Y-m-d', strtotime('monday last week'));
            $end_date = date('Y-m-d', strtotime('sunday last week'));
            break;
        case 'this_month':
            $start_date = date('Y-m-01');
            $end_date = date('Y-m-d');
            break;
        case 'last_month':
            $start_date = date('Y-m-01', strtotime('first day of last month'));
            $end_date = date('Y-m-t', strtotime('last day of last month'));
            break;
        case 'this_year':
            $start_date = date('Y-01-01');
            $end_date = date('Y-m-d');
            break;
        case 'last_year':
            $start_date = date('Y-01-01', strtotime('first day of january last year'));
            $end_date = date('Y-12-31', strtotime('last day of december last year'));
            break;
        default:
            $start_date = date('Y-m-01');
            $end_date = date('Y-m-d');
    }
}

try {
    // آمار مشتریان جدید
    $new_customers = $pdo->prepare("
        SELECT COUNT(*) 
        FROM customers 
        WHERE created_at BETWEEN ? AND ?
    ");
    $new_customers->execute([$start_date, $end_date]);
    $new_customers = $new_customers->fetchColumn();

    // آمار لیدهای جدید
    $new_leads = $pdo->prepare("
        SELECT COUNT(*) 
        FROM leads 
        WHERE created_at BETWEEN ? AND ?
    ");
    $new_leads->execute([$start_date, $end_date]);
    $new_leads = $new_leads->fetchColumn();

    // آمار فروش دوره
    $period_sales = $pdo->prepare("
        SELECT COALESCE(SUM(total_amount), 0) 
        FROM sales 
        WHERE sale_date BETWEEN ? AND ? AND status != 'cancelled'
    ");
    $period_sales->execute([$start_date, $end_date]);
    $period_sales = $period_sales->fetchColumn();

    // آمار وظایف تکمیل شده
    $completed_tasks = $pdo->prepare("
        SELECT COUNT(*) 
        FROM tasks 
        WHERE status = 'completed' AND updated_at BETWEEN ? AND ?
    ");
    $completed_tasks->execute([$start_date, $end_date]);
    $completed_tasks = $completed_tasks->fetchColumn();

    // نرخ تبدیل لید به مشتری
    $total_leads = $pdo->prepare("
        SELECT COUNT(*) 
        FROM leads 
        WHERE created_at BETWEEN ? AND ?
    ");
    $total_leads->execute([$start_date, $end_date]);
    $total_leads = $total_leads->fetchColumn();

    $converted_leads = $pdo->prepare("
        SELECT COUNT(*) 
        FROM leads 
        WHERE status = 'converted' AND created_at BETWEEN ? AND ?
    ");
    $converted_leads->execute([$start_date, $end_date]);
    $converted_leads = $converted_leads->fetchColumn();

    $conversion_rate = $total_leads > 0 ? round(($converted_leads / $total_leads) * 100, 1) : 0;

    // فروش برتر بر اساس محصول
    $top_products = $pdo->prepare("
        SELECT 
            p.name,
            p.sku,
            SUM(si.quantity) as total_quantity,
            SUM(si.total_price) as total_revenue
        FROM sale_items si
        JOIN products p ON si.product_id = p.id
        JOIN sales s ON si.sale_id = s.id
        WHERE s.sale_date BETWEEN ? AND ? AND s.status != 'cancelled'
        GROUP BY p.id
        ORDER BY total_revenue DESC
        LIMIT 5
    ");
    $top_products->execute([$start_date, $end_date]);
    $top_products = $top_products->fetchAll();

    // فروشندگان برتر
    $top_sellers = $pdo->prepare("
        SELECT 
            CONCAT(u.first_name, ' ', u.last_name) as seller_name,
            COUNT(s.id) as total_sales,
            COALESCE(SUM(s.total_amount), 0) as total_revenue
        FROM sales s
        JOIN users u ON s.user_id = u.id
        WHERE s.sale_date BETWEEN ? AND ? AND s.status != 'cancelled'
        GROUP BY u.id
        ORDER BY total_revenue DESC
        LIMIT 5
    ");
    $top_sellers->execute([$start_date, $end_date]);
    $top_sellers = $top_sellers->fetchAll();

    // مشتریان برتر
    $top_customers = $pdo->prepare("
        SELECT 
            CONCAT(c.first_name, ' ', c.last_name) as customer_name,
            c.customer_code,
            COUNT(s.id) as total_purchases,
            COALESCE(SUM(s.total_amount), 0) as total_spent
        FROM sales s
        JOIN customers c ON s.customer_id = c.id
        WHERE s.sale_date BETWEEN ? AND ? AND s.status != 'cancelled'
        GROUP BY c.id
        ORDER BY total_spent DESC
        LIMIT 5
    ");
    $top_customers->execute([$start_date, $end_date]);
    $top_customers = $top_customers->fetchAll();

    // روند فروش
    if ($period === 'this_year' || $period === 'last_year') {
        // روند ماهانه
        $trend_query = $pdo->prepare("
            SELECT 
                DATE_FORMAT(sale_date, '%Y-%m') as period,
                COALESCE(SUM(total_amount), 0) as amount
            FROM sales
            WHERE sale_date BETWEEN ? AND ? AND status != 'cancelled'
            GROUP BY period
            ORDER BY period
        ");
    } else {
        // روند روزانه
        $trend_query = $pdo->prepare("
            SELECT 
                DATE(sale_date) as period,
                COALESCE(SUM(total_amount), 0) as amount
            FROM sales
            WHERE sale_date BETWEEN ? AND ? AND status != 'cancelled'
            GROUP BY period
            ORDER BY period
        ");
    }
    $trend_query->execute([$start_date, $end_date]);
    $sales_trend = $trend_query->fetchAll();

    // آمار وضعیت لیدها
    $leads_by_status = $pdo->prepare("
        SELECT 
            status,
            COUNT(*) as count
        FROM leads
        WHERE created_at BETWEEN ? AND ?
        GROUP BY status
    ");
    $leads_by_status->execute([$start_date, $end_date]);
    $leads_by_status = $leads_by_status->fetchAll();

} catch (PDOException $e) {
    error_log("خطا در دریافت گزارش‌ها: " . $e->getMessage());
    setMessage('خطا در بارگذاری گزارش‌ها', 'error');
    
    // مقادیر پیش‌فرض
    $new_customers = 0;
    $new_leads = 0;
    $period_sales = 0;
    $completed_tasks = 0;
    $conversion_rate = 0;
    $top_products = [];
    $top_sellers = [];
    $top_customers = [];
    $sales_trend = [];
    $leads_by_status = [];
}

include __DIR__ . '/../private/header.php';
?>

<!-- ========== SVG Sprite (hidden) ========== -->
<svg style="display:none;" width="0" height="0">
    <symbol id="svg-chart-bar" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
    </symbol>
    <symbol id="svg-users" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
    </symbol>
    <symbol id="svg-user-plus" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/>
    </symbol>
    <symbol id="svg-dollar-sign" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
    </symbol>
    <symbol id="svg-check-circle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
    </symbol>
    <symbol id="svg-trending-up" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>
    </symbol>
    <symbol id="svg-download" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
    </symbol>
    <symbol id="svg-calendar" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
    </symbol>
    <symbol id="svg-package" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>
    </symbol>
    <symbol id="svg-award" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/>
    </symbol>
    <symbol id="svg-star" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
    </symbol>
    <symbol id="svg-filter" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
    </symbol>
</svg>

<!-- ========== Reports Page Styles ========== -->
<style>
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

.reports-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 28px;
    flex-wrap: wrap;
    gap: 16px;
}

.reports-page-header h4 {
    font-size: 22px;
    font-weight: 700;
    color: var(--text-1);
    margin: 0 0 4px;
}

.reports-page-header p {
    font-size: 14px;
    color: var(--text-3);
    margin: 0;
}

.btn-export-report {
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
    transition: background .2s var(--ease), transform .15s var(--ease), box-shadow .2s var(--ease);
    box-shadow: var(--shadow-sm);
}

.btn-export-report:hover {
    background: var(--teal-dark);
    color: #fff;
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.filter-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: var(--r-lg);
    padding: 22px 24px;
    margin-bottom: 28px;
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

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 28px;
}

.kpi-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: var(--r-lg);
    padding: 22px 24px;
    box-shadow: var(--shadow-sm);
    transition: box-shadow .2s var(--ease), transform .2s var(--ease);
    position: relative;
    overflow: hidden;
}

.kpi-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.kpi-card::before {
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

.kpi-card .kpi-content {
    position: relative;
    z-index: 1;
}

.kpi-card .kpi-icon {
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

.kpi-card .kpi-value {
    font-size: 26px;
    font-weight: 700;
    color: var(--text-1);
    margin-bottom: 6px;
    line-height: 1;
}

.kpi-card .kpi-label {
    font-size: 13px;
    font-weight: 500;
    color: var(--text-3);
}

.icon-teal   { background: var(--teal); }
.icon-blue   { background: #3b82f6; }
.icon-green  { background: #10b981; }
.icon-amber  { background: #f59e0b; }
.icon-purple { background: #8b5cf6; }

.chart-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: var(--r-xl);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    margin-bottom: 24px;
}

.chart-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
}

.chart-card-header h5 {
    font-size: 16px;
    font-weight: 700;
    color: var(--text-1);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.chart-card-header h5 svg { color: var(--teal); }

.chart-card-body {
    padding: 24px;
}

.ranking-table {
    width: 100%;
    border-collapse: collapse;
    font-family: 'Vazirmatn', sans-serif;
}

.ranking-table thead th {
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
}

.ranking-table tbody tr {
    border-bottom: 1px solid var(--border);
    transition: background .18s var(--ease);
}

.ranking-table tbody tr:last-child { border-bottom: none; }

.ranking-table tbody tr:hover {
    background: var(--teal-50);
}

.ranking-table tbody td {
    padding: 16px 18px;
    font-size: 14px;
    color: var(--text-1);
    vertical-align: middle;
}

.rank-badge {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 13px;
}

.rank-1 { background: #fef2f2; color: #dc2626; }
.rank-2 { background: #fffbeb; color: #f59e0b; }
.rank-3 { background: #eff6ff; color: #3b82f6; }
.rank-default { background: var(--page-bg); color: var(--text-3); }

.progress-bar-2026 {
    width: 100%;
    height: 8px;
    background: var(--page-bg);
    border-radius: var(--r-pill);
    overflow: hidden;
    position: relative;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--teal), var(--teal-light));
    border-radius: var(--r-pill);
    transition: width .6s var(--ease);
}

.empty-state-reports {
    text-align: center;
    padding: 72px 24px;
}

.empty-state-reports .empty-icon-wrap {
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

.empty-state-reports h5 {
    color: var(--text-1);
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 6px;
}

.empty-state-reports p {
    color: var(--text-3);
    font-size: 14px;
    margin-bottom: 20px;
}

@media (max-width: 1200px) {
    .filter-row { grid-template-columns: 1fr 1fr 1fr; }
}

@media (max-width: 768px) {
    .filter-row { grid-template-columns: 1fr; }
    .reports-page-header { flex-direction: column; align-items: flex-start; }
    .kpi-grid { grid-template-columns: 1fr; }
}
</style>

<!-- ========== Page Header ========== -->
<div class="reports-page-header">
    <div>
        <h4>گزارش‌ها و آمار</h4>
        <p>تحلیل عملکرد و روند فعالیت‌های سیستم</p>
    </div>
    <button type="button" class="btn-export-report" onclick="exportReport()">
        <svg width="16" height="16"><use href="#svg-download"/></svg>
        خروجی CSV
    </button>
</div>

<!-- ========== Filter Card ========== -->
<div class="filter-card">
    <form method="GET">
        <div class="filter-row">
            <div class="filter-group">
                <label>بازه زمانی</label>
                <select class="form-select" name="period" id="periodSelect">
                    <option value="today" <?php echo $period === 'today' ? 'selected' : ''; ?>>امروز</option>
                    <option value="yesterday" <?php echo $period === 'yesterday' ? 'selected' : ''; ?>>دیروز</option>
                    <option value="this_week" <?php echo $period === 'this_week' ? 'selected' : ''; ?>>این هفته</option>
                    <option value="last_week" <?php echo $period === 'last_week' ? 'selected' : ''; ?>>هفته گذشته</option>
                    <option value="this_month" <?php echo $period === 'this_month' ? 'selected' : ''; ?>>این ماه</option>
                    <option value="last_month" <?php echo $period === 'last_month' ? 'selected' : ''; ?>>ماه گذشته</option>
                    <option value="this_year" <?php echo $period === 'this_year' ? 'selected' : ''; ?>>امسال</option>
                    <option value="last_year" <?php echo $period === 'last_year' ? 'selected' : ''; ?>>سال گذشته</option>
                    <option value="custom" <?php echo $period === 'custom' ? 'selected' : ''; ?>>سفارشی</option>
                </select>
            </div>

            <div class="filter-group" id="startDateGroup" style="display:<?php echo $period === 'custom' ? 'block' : 'none'; ?>;">
                <label>از تاریخ</label>
                <input type="date" class="form-control" name="start_date" value="<?php echo htmlspecialchars($start_date_custom); ?>">
            </div>

            <div class="filter-group" id="endDateGroup" style="display:<?php echo $period === 'custom' ? 'block' : 'none'; ?>;">
                <label>تا تاریخ</label>
                <input type="date" class="form-control" name="end_date" value="<?php echo htmlspecialchars($end_date_custom); ?>">
            </div>

            <div class="filter-group">
                <button type="submit" class="btn-filter-submit">
                    <svg width="15" height="15"><use href="#svg-filter"/></svg>
                    اعمال فیلتر
                </button>
            </div>
        </div>
    </form>
</div>

<!-- ========== KPI Cards ========== -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-content">
            <div class="kpi-icon icon-teal">
                <svg width="24" height="24"><use href="#svg-user-plus"/></svg>
            </div>
            <div class="kpi-value"><?php echo number_format($new_customers); ?></div>
            <div class="kpi-label">مشتریان جدید</div>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-content">
            <div class="kpi-icon icon-blue">
                <svg width="24" height="24"><use href="#svg-users"/></svg>
            </div>
            <div class="kpi-value"><?php echo number_format($new_leads); ?></div>
            <div class="kpi-label">لیدهای جدید</div>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-content">
            <div class="kpi-icon icon-green">
                <svg width="24" height="24"><use href="#svg-dollar-sign"/></svg>
            </div>
            <div class="kpi-value"><?php echo formatMoney($period_sales); ?></div>
            <div class="kpi-label">فروش دوره</div>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-content">
            <div class="kpi-icon icon-amber">
                <svg width="24" height="24"><use href="#svg-check-circle"/></svg>
            </div>
            <div class="kpi-value"><?php echo number_format($completed_tasks); ?></div>
            <div class="kpi-label">وظایف تکمیل شده</div>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-content">
            <div class="kpi-icon icon-purple">
                <svg width="24" height="24"><use href="#svg-trending-up"/></svg>
            </div>
            <div class="kpi-value"><?php echo $conversion_rate; ?>%</div>
            <div class="kpi-label">نرخ تبدیل لید</div>
        </div>
    </div>
</div>

<!-- ========== Charts Row ========== -->
<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="chart-card">
            <div class="chart-card-header">
                <h5>
                    <svg width="20" height="20"><use href="#svg-chart-bar"/></svg>
                    روند فروش
                </h5>
            </div>
            <div class="chart-card-body">
                <?php if (empty($sales_trend)): ?>
                    <div class="empty-state-reports">
                        <div class="empty-icon-wrap">
                            <svg width="36" height="36"><use href="#svg-chart-bar"/></svg>
                        </div>
                        <h5>داده‌ای یافت نشد</h5>
                        <p>برای این بازه زمانی فروشی ثبت نشده است</p>
                    </div>
                <?php else: ?>
                    <canvas id="salesTrendChart" style="max-height:300px;"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4 mb-4">
        <div class="chart-card">
            <div class="chart-card-header">
                <h5>
                    <svg width="20" height="20"><use href="#svg-users"/></svg>
                    وضعیت لیدها
                </h5>
            </div>
            <div class="chart-card-body">
                <?php if (empty($leads_by_status)): ?>
                    <div class="empty-state-reports">
                        <div class="empty-icon-wrap">
                            <svg width="36" height="36"><use href="#svg-users"/></svg>
                        </div>
                        <h5>لیدی یافت نشد</h5>
                    </div>
                <?php else: ?>
                    <canvas id="leadsStatusChart" style="max-height:300px;"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ========== Ranking Tables ========== -->
<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="chart-card">
            <div class="chart-card-header">
                <h5>
                    <svg width="20" height="20"><use href="#svg-package"/></svg>
                    محصولات برتر
                </h5>
            </div>
            <div class="chart-card-body" style="padding:0;">
                <?php if (empty($top_products)): ?>
                    <div class="empty-state-reports">
                        <div class="empty-icon-wrap">
                            <svg width="36" height="36"><use href="#svg-package"/></svg>
                        </div>
                        <h5>داده‌ای یافت نشد</h5>
                    </div>
                <?php else: ?>
                    <table class="ranking-table">
                        <thead>
                            <tr>
                                <th style="width:50px;">رتبه</th>
                                <th>محصول</th>
                                <th style="text-align:center;">تعداد</th>
                                <th style="text-align:left;">درآمد</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rank = 1; foreach ($top_products as $product): ?>
                                <tr>
                                    <td>
                                        <div class="rank-badge rank-<?php echo $rank <= 3 ? $rank : 'default'; ?>">
                                            <?php echo $rank; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight:600;margin-bottom:2px;">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </div>
                                        <small style="color:var(--text-3);">
                                            <?php echo htmlspecialchars($product['sku']); ?>
                                        </small>
                                    </td>
                                    <td style="text-align:center;">
                                        <span style="font-weight:600;color:var(--text-2);">
                                            <?php echo number_format($product['total_quantity']); ?>
                                        </span>
                                    </td>
                                    <td style="text-align:left;">
                                        <span style="font-weight:700;color:#10b981;">
                                            <?php echo formatMoney($product['total_revenue']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php $rank++; endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4 mb-4">
        <div class="chart-card">
            <div class="chart-card-header">
                <h5>
                    <svg width="20" height="20"><use href="#svg-award"/></svg>
                    فروشندگان برتر
                </h5>
            </div>
            <div class="chart-card-body" style="padding:0;">
                <?php if (empty($top_sellers)): ?>
                    <div class="empty-state-reports">
                        <div class="empty-icon-wrap">
                            <svg width="36" height="36"><use href="#svg-award"/></svg>
                        </div>
                        <h5>داده‌ای یافت نشد</h5>
                    </div>
                <?php else: ?>
                    <table class="ranking-table">
                        <thead>
                            <tr>
                                <th style="width:50px;">رتبه</th>
                                <th>فروشنده</th>
                                <th style="text-align:center;">فروش</th>
                                <th style="text-align:left;">درآمد</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rank = 1; foreach ($top_sellers as $seller): ?>
                                <tr>
                                    <td>
                                        <div class="rank-badge rank-<?php echo $rank <= 3 ? $rank : 'default'; ?>">
                                            <?php echo $rank; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight:600;">
                                            <?php echo htmlspecialchars($seller['seller_name']); ?>
                                        </div>
                                    </td>
                                    <td style="text-align:center;">
                                        <span style="font-weight:600;color:var(--text-2);">
                                            <?php echo number_format($seller['total_sales']); ?>
                                        </span>
                                    </td>
                                    <td style="text-align:left;">
                                        <span style="font-weight:700;color:#10b981;">
                                            <?php echo formatMoney($seller['total_revenue']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php $rank++; endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4 mb-4">
        <div class="chart-card">
            <div class="chart-card-header">
                <h5>
                    <svg width="20" height="20"><use href="#svg-star"/></svg>
                    مشتریان برتر
                </h5>
            </div>
            <div class="chart-card-body" style="padding:0;">
                <?php if (empty($top_customers)): ?>
                    <div class="empty-state-reports">
                        <div class="empty-icon-wrap">
                            <svg width="36" height="36"><use href="#svg-star"/></svg>
                        </div>
                        <h5>داده‌ای یافت نشد</h5>
                    </div>
                <?php else: ?>
                    <table class="ranking-table">
                        <thead>
                            <tr>
                                <th style="width:50px;">رتبه</th>
                                <th>مشتری</th>
                                <th style="text-align:center;">خرید</th>
                                <th style="text-align:left;">مبلغ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rank = 1; foreach ($top_customers as $customer): ?>
                                <tr>
                                    <td>
                                        <div class="rank-badge rank-<?php echo $rank <= 3 ? $rank : 'default'; ?>">
                                            <?php echo $rank; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight:600;margin-bottom:2px;">
                                            <?php echo htmlspecialchars($customer['customer_name']); ?>
                                        </div>
                                        <small style="color:var(--text-3);">
                                            <?php echo htmlspecialchars($customer['customer_code']); ?>
                                        </small>
                                    </td>
                                    <td style="text-align:center;">
                                        <span style="font-weight:600;color:var(--text-2);">
                                            <?php echo number_format($customer['total_purchases']); ?>
                                        </span>
                                    </td>
                                    <td style="text-align:left;">
                                        <span style="font-weight:700;color:#10b981;">
                                            <?php echo formatMoney($customer['total_spent']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php $rank++; endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ========== Page Scripts ========== -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
document.getElementById('periodSelect').addEventListener('change', function() {
    const isCustom = this.value === 'custom';
    document.getElementById('startDateGroup').style.display = isCustom ? 'block' : 'none';
    document.getElementById('endDateGroup').style.display = isCustom ? 'block' : 'none';
});

<?php if (!empty($sales_trend)): ?>
const salesTrendCtx = document.getElementById('salesTrendChart');
if (salesTrendCtx) {
    new Chart(salesTrendCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($sales_trend, 'period')); ?>,
            datasets: [{
                label: 'فروش',
                data: <?php echo json_encode(array_column($sales_trend, 'amount')); ?>,
                borderColor: '#14b8a6',
                backgroundColor: 'rgba(20, 184, 166, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('fa-IR');
                        }
                    }
                },
                x: {
                    ticks: {
                        font: { family: 'Vazirmatn' }
                    }
                }
            }
        }
    });
}
<?php endif; ?>

<?php if (!empty($leads_by_status)): ?>
const leadsStatusCtx = document.getElementById('leadsStatusChart');
if (leadsStatusCtx) {
    new Chart(leadsStatusCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_map(function($l) { return getStatusTitle($l['status'], 'lead'); }, $leads_by_status)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($leads_by_status, 'count')); ?>,
                backgroundColor: [
                    '#14b8a6',
                    '#3b82f6',
                    '#10b981',
                    '#f59e0b',
                    '#ef4444'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: { family: 'Vazirmatn' },
                        padding: 15
                    }
                }
            }
        }
    });
}
<?php endif; ?>

function exportReport() {
    const csv = [];
    csv.push(['نوع گزارش', 'مقدار']);
    csv.push(['مشتریان جدید', '<?php echo $new_customers ?? 0; ?>']);
    csv.push(['لیدهای جدید', '<?php echo $new_leads ?? 0; ?>']);
    csv.push(['فروش دوره', '<?php echo $period_sales; ?>']);
    csv.push(['وظایف تکمیل شده', '<?php echo $completed_tasks; ?>']);
    csv.push(['نرخ تبدیل لید', '<?php echo $conversion_rate; ?>%']);

    const csvString = csv.map(row => row.join(',')).join('\n');
    const blob = new Blob(['\ufeff' + csvString], { type: 'text/csv;charset=utf-8;' });

    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'گزارش_' + new Date().toISOString().slice(0, 10) + '.csv';
    link.click();
}
</script>

<?php include __DIR__ . '/../private/footer.php'; ?>
