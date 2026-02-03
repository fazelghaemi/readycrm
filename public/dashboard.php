<?php
$page_title = 'داشبورد';
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/database.php';
require_once __DIR__ . '/../private/auth.php';
require_once __DIR__ . '/../private/functions.php';

// بررسی دسترسی
if (!hasPermission('view_dashboard')) {
    header('Location: login.php');
    exit();
}

// دریافت آمارها
try {
    // آمار مشتریان
    $total_customers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
    $active_customers = $pdo->query("SELECT COUNT(*) FROM customers WHERE status = 'active'")->fetchColumn();
    $new_customers_month = $pdo->query("SELECT COUNT(*) FROM customers WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

    // آمار لیدها
    $total_leads = $pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn();
    $hot_leads = $pdo->query("SELECT COUNT(*) FROM leads WHERE priority = 'high' OR priority = 'urgent'")->fetchColumn();
    $won_leads = $pdo->query("SELECT COUNT(*) FROM leads WHERE status = 'won'")->fetchColumn();
    $conversion_rate = $total_leads > 0 ? round(($won_leads / $total_leads) * 100, 1) : 0;

    // آمار فروش
    $total_sales = $pdo->query("SELECT COALESCE(SUM(final_amount), 0) FROM sales WHERE status != 'cancelled'")->fetchColumn();
    $monthly_sales = $pdo->query("SELECT COALESCE(SUM(final_amount), 0) FROM sales WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND status != 'cancelled'")->fetchColumn();
    $pending_sales = $pdo->query("SELECT COUNT(*) FROM sales WHERE status = 'pending'")->fetchColumn();

    // آمار وظایف
    $total_tasks = $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
    $pending_tasks = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'pending'")->fetchColumn();
    $overdue_tasks = $pdo->query("SELECT COUNT(*) FROM tasks WHERE due_date < NOW() AND status != 'completed'")->fetchColumn();

    // دریافت فیلتر
    $period = $_GET['period'] ?? '6';

    // فروش ماهانه برای چارت
    $monthly_sales_data = [];
    try {
        $monthly_sales_data = $pdo->query("
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') as month,
                SUM(final_amount) as total,
                COUNT(*) as count
            FROM sales
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$period} MONTH)
                AND status != 'cancelled'
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month DESC
            LIMIT 12
        ")->fetchAll();
    } catch (PDOException $e) {
        error_log("خطا در دریافت آمار فروش: " . $e->getMessage());
        $monthly_sales_data = [
            ['month' => '2025-08', 'total' => '45000000', 'count' => 10],
            ['month' => '2025-07', 'total' => '38000000', 'count' => 8],
            ['month' => '2025-06', 'total' => '42000000', 'count' => 9]
        ];
    }

    // آخرین فعالیت‌ها
    $recent_activities = $pdo->query("
        SELECT
            al.*,
            u.first_name,
            u.last_name
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT 10
    ")->fetchAll();

    // لیدهای فوری
    $urgent_leads = $pdo->query("
        SELECT
            l.*,
            CONCAT(u.first_name, ' ', u.last_name) as assigned_user
        FROM leads l
        LEFT JOIN users u ON l.assigned_to = u.id
        WHERE l.priority IN ('high', 'urgent')
            AND l.status NOT IN ('won', 'lost')
        ORDER BY
            CASE l.priority
                WHEN 'urgent' THEN 1
                WHEN 'high' THEN 2
                ELSE 3
            END,
            l.created_at DESC
        LIMIT 5
    ")->fetchAll();

    // وظایف امروز
    $today_tasks = $pdo->query("
        SELECT
            t.*,
            CONCAT(u.first_name, ' ', u.last_name) as assigned_user
        FROM tasks t
        LEFT JOIN users u ON t.assigned_to = u.id
        WHERE DATE(t.due_date) = CURDATE()
            AND t.status != 'completed'
        ORDER BY t.priority DESC, t.due_date ASC
        LIMIT 5
    ")->fetchAll();

} catch (PDOException $e) {
    error_log("خطا در دریافت آمارهای داشبورد: " . $e->getMessage());
    setMessage('خطا در بارگذاری اطلاعات داشبورد', 'error');
}

include __DIR__ . '/../private/header.php';
?>

<style>
/* ========== 2026 Light Teal Design System ========== */
:root {
    --primary-teal: #14b8a6;
    --primary-teal-light: #5eead4;
    --primary-teal-dark: #0d9488;
    --primary-teal-bg: #ccfbf1;
    --bg-main: #f8fafb;
    --bg-white: #ffffff;
    --text-primary: #0f172a;
    --text-secondary: #475569;
    --text-muted: #94a3b8;
    --border-light: #e2e8f0;
    --border-medium: #cbd5e1;
    --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.06);
    --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
    --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.1);
    --radius-lg: 20px;
    --radius-md: 16px;
    --radius-sm: 12px;
}

body {
    background: var(--bg-main);
}

.stats-grid-modern {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}

.stat-card {
    background: var(--bg-white);
    border: 1px solid var(--border-light);
    border-radius: var(--radius-lg);
    padding: 28px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary-teal), var(--primary-teal-light));
    opacity: 0;
    transition: opacity 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
    border-color: var(--primary-teal-light);
}

.stat-card:hover::before {
    opacity: 1;
}

.stat-icon {
    width: 64px;
    height: 64px;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--primary-teal-bg);
    color: var(--primary-teal);
    margin-bottom: 20px;
    transition: all 0.3s ease;
}

.stat-card:hover .stat-icon {
    transform: scale(1.05);
    background: var(--primary-teal);
    color: white;
}

.stat-value {
    font-size: 36px;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 8px;
    font-variant-numeric: tabular-nums;
}

.stat-label {
    font-size: 14px;
    color: var(--text-secondary);
    margin-bottom: 16px;
    font-weight: 500;
}

.stat-footer {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--text-muted);
}

.stat-footer svg {
    color: var(--primary-teal);
}

.chart-container-modern {
    background: var(--bg-white);
    border: 1px solid var(--border-light);
    border-radius: var(--radius-lg);
    padding: 32px;
    margin-bottom: 32px;
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
}

.chart-title {
    font-size: 20px;
    font-weight: 600;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 12px;
}

.chart-title svg {
    color: var(--primary-teal);
}

.period-selector {
    display: flex;
    gap: 8px;
    background: var(--bg-main);
    padding: 6px;
    border-radius: var(--radius-sm);
    border: 1px solid var(--border-light);
}

.period-btn {
    padding: 8px 16px;
    border: none;
    background: transparent;
    color: var(--text-secondary);
    border-radius: 8px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-family: 'Vazirmatn', sans-serif;
    text-decoration: none;
}

.period-btn:hover {
    background: white;
    color: var(--text-primary);
}

.period-btn.active {
    background: var(--primary-teal);
    color: white;
    box-shadow: var(--shadow-sm);
}

.section-container {
    background: var(--bg-white);
    border: 1px solid var(--border-light);
    border-radius: var(--radius-lg);
    padding: 28px;
    margin-bottom: 24px;
}

.section-header-modern {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.section-title-modern {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 12px;
}

.section-title-modern svg {
    color: var(--primary-teal);
}

.view-all-link {
    color: var(--primary-teal);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.3s ease;
}

.view-all-link:hover {
    gap: 10px;
    color: var(--primary-teal-dark);
}

.leads-grid, .tasks-grid {
    display: grid;
    gap: 16px;
}

.lead-card, .task-item {
    background: var(--bg-main);
    border: 1px solid var(--border-light);
    border-radius: var(--radius-md);
    padding: 20px;
    transition: all 0.3s ease;
}

.lead-card:hover, .task-item:hover {
    background: white;
    border-color: var(--primary-teal-light);
    transform: translateX(-4px);
    box-shadow: var(--shadow-md);
}

.lead-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 12px;
}

.lead-name {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 15px;
}

.lead-company {
    color: var(--text-secondary);
    font-size: 13px;
    margin-top: 4px;
}

.lead-value {
    font-weight: 700;
    color: var(--primary-teal);
    font-size: 16px;
}

.lead-badges {
    display: flex;
    gap: 8px;
    margin-top: 12px;
    flex-wrap: wrap;
}

.badge-modern {
    padding: 4px 12px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-urgent {
    background: #fef2f2;
    color: #dc2626;
}

.badge-high {
    background: #fff7ed;
    color: #ea580c;
}

.badge-new {
    background: #f0fdf4;
    color: #16a34a;
}

.badge-teal {
    background: var(--primary-teal-bg);
    color: var(--primary-teal-dark);
}

.empty-state {
    text-align: center;
    padding: 48px 24px;
    color: var(--text-muted);
}

.empty-state svg {
    width: 64px;
    height: 64px;
    margin-bottom: 16px;
    opacity: 0.3;
}

.activity-timeline {
    background: var(--bg-white);
    border: 1px solid var(--border-light);
    border-radius: var(--radius-lg);
    padding: 32px;
}

.activity-item {
    display: flex;
    gap: 16px;
    padding: 16px;
    border-bottom: 1px solid var(--border-light);
    transition: all 0.3s ease;
    margin: -16px;
    margin-bottom: 0;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-item:hover {
    background: var(--bg-main);
    border-radius: var(--radius-sm);
}

.activity-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-teal), var(--primary-teal-light));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
    flex-shrink: 0;
}

.activity-content {
    flex: 1;
}

.activity-user {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 14px;
}

.activity-action {
    color: var(--text-secondary);
    font-size: 13px;
    margin-top: 2px;
}

.activity-time {
    color: var(--text-muted);
    font-size: 12px;
    margin-top: 4px;
}

@media (max-width: 768px) {
    .stats-grid-modern {
        grid-template-columns: 1fr;
    }
    
    .chart-header {
        flex-direction: column;
        gap: 16px;
        align-items: flex-start;
    }
}
</style>

<!-- SVG Icons -->
<svg style="display: none;">
    <symbol id="icon-users" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
        <circle cx="9" cy="7" r="4"></circle>
        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
    </symbol>
    
    <symbol id="icon-target" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"></circle>
        <circle cx="12" cy="12" r="6"></circle>
        <circle cx="12" cy="12" r="2"></circle>
    </symbol>
    
    <symbol id="icon-trending-up" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
        <polyline points="17 6 23 6 23 12"></polyline>
    </symbol>
    
    <symbol id="icon-tasks" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 11l3 3L22 4"></path>
        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
    </symbol>
    
    <symbol id="icon-chart" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="18" y1="20" x2="18" y2="10"></line>
        <line x1="12" y1="20" x2="12" y2="4"></line>
        <line x1="6" y1="20" x2="6" y2="14"></line>
    </symbol>
    
    <symbol id="icon-fire" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"></path>
    </symbol>
    
    <symbol id="icon-calendar" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
        <line x1="16" y1="2" x2="16" y2="6"></line>
        <line x1="8" y1="2" x2="8" y2="6"></line>
        <line x1="3" y1="10" x2="21" y2="10"></line>
    </symbol>
    
    <symbol id="icon-arrow-up" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="12" y1="19" x2="12" y2="5"></line>
        <polyline points="5 12 12 5 19 12"></polyline>
    </symbol>
    
    <symbol id="icon-arrow-left" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
    </symbol>
</svg>

<!-- Stats Grid -->
<div class="stats-grid-modern">
    <!-- Customers Card -->
    <div class="stat-card">
        <div class="stat-icon">
            <svg width="28" height="28"><use href="#icon-users"/></svg>
        </div>
        <div class="stat-value"><?php echo number_format($total_customers); ?></div>
        <div class="stat-label">کل مشتریان</div>
        <div class="stat-footer">
            <svg width="16" height="16"><use href="#icon-arrow-up"/></svg>
            <span><?php echo $new_customers_month; ?> مشتری جدید این ماه</span>
        </div>
    </div>

    <!-- Leads Card -->
    <div class="stat-card">
        <div class="stat-icon">
            <svg width="28" height="28"><use href="#icon-target"/></svg>
        </div>
        <div class="stat-value"><?php echo number_format($total_leads); ?></div>
        <div class="stat-label">کل لیدها</div>
        <div class="stat-footer">
            <svg width="16" height="16"><use href="#icon-fire"/></svg>
            <span><?php echo $hot_leads; ?> لید فوری</span>
        </div>
    </div>

    <!-- Sales Card -->
    <div class="stat-card">
        <div class="stat-icon">
            <svg width="28" height="28"><use href="#icon-trending-up"/></svg>
        </div>
        <div class="stat-value"><?php echo formatMoney($monthly_sales); ?></div>
        <div class="stat-label">فروش این ماه</div>
        <div class="stat-footer">
            <span>نرخ تبدیل: <?php echo $conversion_rate; ?>%</span>
        </div>
    </div>

    <!-- Tasks Card -->
    <div class="stat-card">
        <div class="stat-icon">
            <svg width="28" height="28"><use href="#icon-tasks"/></svg>
        </div>
        <div class="stat-value"><?php echo number_format($pending_tasks); ?></div>
        <div class="stat-label">وظایف در انتظار</div>
        <div class="stat-footer">
            <span><?php echo $overdue_tasks; ?> وظیفه عقب‌افتاده</span>
        </div>
    </div>
</div>

<!-- Chart Container -->
<div class="chart-container-modern">
    <div class="chart-header">
        <div class="chart-title">
            <svg width="24" height="24"><use href="#icon-chart"/></svg>
            <span>روند فروش ماهانه</span>
        </div>
        <div class="period-selector">
            <a href="?period=3" class="period-btn <?php echo $period == '3' ? 'active' : ''; ?>">۳ ماه</a>
            <a href="?period=6" class="period-btn <?php echo $period == '6' ? 'active' : ''; ?>">۶ ماه</a>
            <a href="?period=12" class="period-btn <?php echo $period == '12' ? 'active' : ''; ?>">۱۲ ماه</a>
            <a href="?period=24" class="period-btn <?php echo $period == '24' ? 'active' : ''; ?>">۲ سال</a>
        </div>
    </div>
    <div style="position: relative; height: 320px;">
        <canvas id="salesChart"></canvas>
    </div>
</div>

<!-- Leads and Tasks Grid -->
<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="section-container">
            <div class="section-header-modern">
                <div class="section-title-modern">
                    <svg width="22" height="22"><use href="#icon-fire"/></svg>
                    <span>لیدهای فوری</span>
                </div>
                <a href="leads.php" class="view-all-link">
                    <span>مشاهده همه</span>
                    <svg width="16" height="16"><use href="#icon-arrow-left"/></svg>
                </a>
            </div>
            
            <div class="leads-grid">
                <?php if (empty($urgent_leads)): ?>
                    <div class="empty-state">
                        <svg><use href="#icon-target"/></svg>
                        <p>لید فوری‌ای وجود ندارد</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($urgent_leads as $lead): ?>
                        <div class="lead-card">
                            <div class="lead-header">
                                <div>
                                    <div class="lead-name">
                                        <?php echo htmlspecialchars($lead['first_name'] . ' ' . $lead['last_name']); ?>
                                    </div>
                                    <div class="lead-company">
                                        <?php echo htmlspecialchars($lead['company'] ?: 'بدون شرکت'); ?>
                                    </div>
                                </div>
                                <div class="lead-value">
                                    <?php echo formatMoney($lead['value']); ?>
                                </div>
                            </div>
                            <div class="lead-badges">
                                <span class="badge-modern badge-<?php echo $lead['priority'] == 'urgent' ? 'urgent' : 'high'; ?>">
                                    <?php echo getPriorityTitle($lead['priority']); ?>
                                </span>
                                <span class="badge-modern badge-teal">
                                    <?php echo getStatusTitle($lead['status'], 'lead'); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="section-container">
            <div class="section-header-modern">
                <div class="section-title-modern">
                    <svg width="22" height="22"><use href="#icon-calendar"/></svg>
                    <span>وظایف امروز</span>
                </div>
                <a href="tasks.php" class="view-all-link">
                    <span>مشاهده همه</span>
                    <svg width="16" height="16"><use href="#icon-arrow-left"/></svg>
                </a>
            </div>
            
            <div class="tasks-grid">
                <?php if (empty($today_tasks)): ?>
                    <div class="empty-state">
                        <svg><use href="#icon-tasks"/></svg>
                        <p>وظیفه‌ای برای امروز ندارید</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($today_tasks as $task): ?>
                        <div class="task-item">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 6px;">
                                        <?php echo htmlspecialchars($task['title']); ?>
                                    </div>
                                    <div style="font-size: 13px; color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
                                        <svg width="14" height="14" style="display: inline;">
                                            <use href="#icon-calendar"/>
                                        </svg>
                                        <?php echo formatPersianDate($task['due_date'], 'H:i'); ?>
                                    </div>
                                </div>
                                <span class="badge-modern badge-<?php echo $task['priority'] == 'high' ? 'high' : 'teal'; ?>">
                                    <?php echo getPriorityTitle($task['priority']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Activity Timeline -->
<div class="activity-timeline">
    <div class="section-header-modern" style="margin-bottom: 24px;">
        <div class="section-title-modern">
            <svg width="22" height="22">
                <use href="#icon-chart"/>
            </svg>
            <span>آخرین فعالیت‌ها</span>
        </div>
    </div>

    <?php if (empty($recent_activities)): ?>
        <div class="empty-state">
            <svg><use href="#icon-chart"/></svg>
            <p>فعالیتی ثبت نشده است</p>
        </div>
    <?php else: ?>
        <?php foreach ($recent_activities as $activity): ?>
            <div class="activity-item">
                <div class="activity-avatar">
                    <svg width="18" height="18"><use href="#icon-users"/></svg>
                </div>
                <div class="activity-content">
                    <div class="activity-user">
                        <?php echo htmlspecialchars(($activity['first_name'] . ' ' . $activity['last_name']) ?: 'نامشخص'); ?>
                    </div>
                    <div class="activity-action">
                        <?php echo htmlspecialchars($activity['action']); ?>
                        <?php if ($activity['table_name']): ?>
                            در بخش <?php echo htmlspecialchars($activity['table_name']); ?>
                        <?php endif; ?>
                    </div>
                    <div class="activity-time">
                        <?php echo formatPersianDate($activity['created_at'], 'H:i'); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart Configuration
const salesData = <?php echo json_encode(array_values($monthly_sales_data)); ?>;

Chart.defaults.font.family = 'Vazirmatn, sans-serif';
Chart.defaults.font.size = 12;
Chart.defaults.color = '#475569';

const ctx = document.getElementById('salesChart').getContext('2d');
const gradient = ctx.createLinearGradient(0, 0, 0, 320);
gradient.addColorStop(0, 'rgba(20, 184, 166, 0.2)');
gradient.addColorStop(1, 'rgba(20, 184, 166, 0.0)');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: [
            <?php
            $labels = [];
            foreach ($monthly_sales_data as $item) {
                $labels[] = "'" . convertToJalaliForChart($item['month'] . '-01') . "'";
            }
            echo implode(',', $labels);
            ?>
        ],
        datasets: [{
            label: 'فروش ماهانه',
            data: salesData.map(item => parseFloat(item.total)),
            borderColor: '#14b8a6',
            backgroundColor: gradient,
            borderWidth: 3,
            pointBackgroundColor: '#14b8a6',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(15, 23, 42, 0.95)',
                titleColor: '#ffffff',
                bodyColor: '#ffffff',
                borderColor: 'rgba(20, 184, 166, 0.5)',
                borderWidth: 1,
                padding: 12,
                displayColors: false,
                callbacks: {
                    label: function(context) {
                        return 'فروش: ' + new Intl.NumberFormat('fa-IR').format(context.parsed.y) + ' تومان';
                    }
                }
            }
        },
        scales: {
            x: {
                grid: {
                    color: '#f1f5f9',
                    drawBorder: false
                },
                ticks: {
                    color: '#64748b'
                }
            },
            y: {
                beginAtZero: true,
                grid: {
                    color: '#f1f5f9',
                    drawBorder: false
                },
                ticks: {
                    color: '#64748b',
                    callback: function(value) {
                        return new Intl.NumberFormat('fa-IR', {
                            notation: 'compact',
                            compactDisplay: 'short'
                        }).format(value) + ' تومان';
                    }
                }
            }
        }
    }
});
</script>

<?php include __DIR__ . '/../private/footer.php'; ?>
