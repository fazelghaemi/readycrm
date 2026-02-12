<?php
/**
 * ══════════════════════════════════════════════════════════════════════════════
 * ReadyCRM V3.0 - ULTIMATE PROFILE PAGE (Refactored) 🚀
 * ══════════════════════════════════════════════════════════════════════════════
 * @version 3.0.0
 * @author Ready Studio
 * ══════════════════════════════════════════════════════════════════════════════
 */

session_start();

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/database.php';
require_once __DIR__ . '/../private/auth.php';
require_once __DIR__ . '/../private/functions.php';

// ─── AUTHENTICATION CHECK ───────────────────────────────────────────────────
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$page_title = 'پروفایل من';
$breadcrumb = [
    ['title' => 'داشبورد', 'url' => 'dashboard.php'],
    ['title' => 'پروفایل من']
];

$errors = [];
$success_redirect = '';

// ══════════════════════════════════════════════════════════════════════════════
// 1. BACKEND LOGIC & FORM HANDLING
// ══════════════════════════════════════════════════════════════════════════════

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($csrf_token)) {
        setMessage('نشست شما منقضی شده است، لطفا مجدد تلاش کنید.', 'error');
    } else {
        try {
            switch ($action) {
                // ─── Update Profile Info ───
                case 'update_profile':
                    $first_name = trim($_POST['first_name'] ?? '');
                    $last_name = trim($_POST['last_name'] ?? '');
                    $email = trim($_POST['email'] ?? '');
                    $mobile = trim($_POST['mobile'] ?? '');
                    $phone = trim($_POST['phone'] ?? '');
                    $address = trim($_POST['address'] ?? '');

                    if (empty($first_name) || empty($last_name) || empty($email)) {
                        throw new Exception('نام، نام خانوادگی و ایمیل الزامی هستند.');
                    }
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        throw new Exception('فرمت ایمیل معتبر نیست.');
                    }

                    // Check duplicate email
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                    $stmt->execute([$email, $user_id]);
                    if ($stmt->fetch()) {
                        throw new Exception('این ایمیل قبلاً توسط کاربر دیگری ثبت شده است.');
                    }

                    $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, email=?, mobile=?, phone=?, address=?, updated_at=NOW() WHERE id=?");
                    $stmt->execute([$first_name, $last_name, $email, $mobile, $phone, $address, $user_id]);

                    $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                    logActivity($user_id, 'update_profile', 'users', $user_id);
                    $success_redirect = 'profile.php?updated=1';
                    break;

                // ─── Change Password ───
                case 'change_password':
                    $current_password = $_POST['current_password'] ?? '';
                    $new_password = $_POST['new_password'] ?? '';
                    $confirm_password = $_POST['confirm_password'] ?? '';

                    if (empty($current_password) || empty($new_password)) {
                        throw new Exception('تمام فیلدها الزامی هستند.');
                    }
                    if (strlen($new_password) < 6) {
                        throw new Exception('رمز عبور باید حداقل ۶ کاراکتر باشد.');
                    }
                    if ($new_password !== $confirm_password) {
                        throw new Exception('تکرار رمز عبور مطابقت ندارد.');
                    }

                    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user_data = $stmt->fetch();

                    if (!$user_data || !password_verify($current_password, $user_data['password'])) {
                        throw new Exception('رمز عبور فعلی اشتباه است.');
                    }

                    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$hashed, $user_id]);

                    logActivity($user_id, 'change_password', 'users', $user_id);
                    $success_redirect = 'profile.php?password_changed=1';
                    break;

                // ─── Set Goals ───
                case 'set_goals':
                    $sales_goal = intval($_POST['monthly_sales_goal'] ?? 0);
                    $customers_goal = intval($_POST['monthly_customers_goal'] ?? 0);
                    $leads_goal = intval($_POST['monthly_leads_goal'] ?? 0);

                    $stmt = $pdo->prepare("
                        INSERT INTO user_goals (user_id, month, sales_goal, customers_goal, leads_goal, created_at)
                        VALUES (?, DATE_FORMAT(NOW(), '%Y-%m'), ?, ?, ?, NOW())
                        ON DUPLICATE KEY UPDATE
                            sales_goal = VALUES(sales_goal),
                            customers_goal = VALUES(customers_goal),
                            leads_goal = VALUES(leads_goal)
                    ");
                    $stmt->execute([$user_id, $sales_goal, $customers_goal, $leads_goal]);
                    $success_redirect = 'profile.php?goals_updated=1';
                    break;
            }

            if ($success_redirect) {
                header("Location: $success_redirect");
                exit();
            }

        } catch (Exception $e) {
            setMessage($e->getMessage(), 'error');
        }
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// 2. DATA FETCHING (Optimized Queries)
// ══════════════════════════════════════════════════════════════════════════════

try {
    // 1. User Info
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if (!$user) {
        throw new Exception('کاربر یافت نشد'); // Should force logout usually
    }

    // 2. Optimized Stats (Aggregated Queries)
    // به جای چندین کوئری جداگانه، آمار کلی را تجمیع می‌کنیم
    $stats = [
        'total_customers' => 0, 'total_leads' => 0, 'total_sales' => 0, 'total_revenue' => 0,
        'total_tasks' => 0, 'completed_tasks' => 0
    ];

    // Counts from main tables
    $stats['total_customers'] = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE created_by = ?");
    $stats['total_customers']->execute([$user_id]);
    $stats['total_customers'] = $stats['total_customers']->fetchColumn();

    $stats['total_leads'] = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE created_by = ?");
    $stats['total_leads']->execute([$user_id]);
    $stats['total_leads'] = $stats['total_leads']->fetchColumn();

    // Sales & Revenue
    $saleStats = $pdo->prepare("SELECT COUNT(*) as count, COALESCE(SUM(final_amount), 0) as revenue FROM sales WHERE created_by = ? AND status != 'cancelled'");
    $saleStats->execute([$user_id]);
    $saleData = $saleStats->fetch();
    $stats['total_sales'] = $saleData['count'];
    $stats['total_revenue'] = $saleData['revenue'];

    // Task Stats (Single Query)
    $taskStats = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM tasks WHERE assigned_to = ?
    ");
    $taskStats->execute([$user_id]);
    $taskData = $taskStats->fetch();
    $stats['total_tasks'] = $taskData['total'] ?? 0;
    $stats['completed_tasks'] = $taskData['completed'] ?? 0;


    // 3. Current Month Activity (For Goals/Trends)
    $this_month_stats = $pdo->prepare("
        SELECT
            (SELECT COUNT(*) FROM sales WHERE created_by = ? AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())) as sales,
            (SELECT COUNT(*) FROM customers WHERE created_by = ? AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())) as customers,
            (SELECT COUNT(*) FROM leads WHERE created_by = ? AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())) as leads
    ");
    $this_month_stats->execute([$user_id, $user_id, $user_id]);
    $current_month = $this_month_stats->fetch();


    // 4. Activity Logs & Charts
    $recent_activities = $pdo->prepare("SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
    $recent_activities->execute([$user_id]);
    $recent_activities = $recent_activities->fetchAll();

    $monthly_stats_query = $pdo->prepare("
        SELECT
            DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(CASE WHEN table_name = 'customers' THEN 1 ELSE 0 END) as customers,
            SUM(CASE WHEN table_name = 'leads' THEN 1 ELSE 0 END) as leads,
            SUM(CASE WHEN table_name = 'sales' THEN 1 ELSE 0 END) as sales
        FROM activity_logs
        WHERE user_id = ? AND action IN ('create_customer', 'create_lead', 'create_sale') 
          AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY month ORDER BY month
    ");
    $monthly_stats_query->execute([$user_id]);
    $monthly_chart_data = $monthly_stats_query->fetchAll();

    $weekly_stats_query = $pdo->prepare("SELECT DATE(created_at) as date, COUNT(*) as count FROM activity_logs WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY) GROUP BY DATE(created_at)");
    $weekly_stats_query->execute([$user_id]);
    $weekly_heatmap_data = $weekly_stats_query->fetchAll();


    // 5. Today's Tasks
    $today_tasks = $pdo->prepare("
        SELECT t.*, c.name as customer_name 
        FROM tasks t LEFT JOIN customers c ON t.customer_id = c.id 
        WHERE t.assigned_to = ? AND DATE(t.due_date) = CURDATE() 
        ORDER BY t.due_date ASC LIMIT 10
    ");
    $today_tasks->execute([$user_id]);
    $today_tasks_list = $today_tasks->fetchAll();


    // 6. Ranking (Heavy query, kept but limited)
    // Note: In a very large DB, this should be cached.
    $ranking_query = $pdo->prepare("
        SELECT u.id, u.first_name, u.last_name, 
               COALESCE(SUM(s.final_amount), 0) as total_sales_amount,
               COUNT(s.id) as sales_count
        FROM users u
        LEFT JOIN sales s ON u.id = s.created_by AND s.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH) AND s.status != 'cancelled'
        WHERE u.status = 'active'
        GROUP BY u.id
        ORDER BY total_sales_amount DESC LIMIT 10
    ");
    $ranking_query->execute();
    $ranking = $ranking_query->fetchAll();

    $my_rank = 0;
    foreach ($ranking as $idx => $r) {
        if ($r['id'] == $user_id) { $my_rank = $idx + 1; break; }
    }


    // 7. Goals
    $goals_query = $pdo->prepare("SELECT * FROM user_goals WHERE user_id = ? AND month = DATE_FORMAT(NOW(), '%Y-%m')");
    $goals_query->execute([$user_id]);
    $goals = $goals_query->fetch();


    // 8. Profile Completion
    $profile_fields = ['first_name', 'last_name', 'email', 'mobile', 'phone', 'address', 'department', 'position'];
    $filled_count = 0;
    foreach ($profile_fields as $f) { if (!empty($user[$f])) $filled_count++; }
    $profile_completion = round(($filled_count / count($profile_fields)) * 100);

} catch (Exception $e) {
    error_log("Profile Data Error: " . $e->getMessage());
    setMessage('خطا در بارگذاری اطلاعات پروفایل', 'error');
    // Fallback or Redirect
}

$csrf_token = generateCSRFToken();
include __DIR__ . '/../private/header.php';
?>

<!-- ══════════════════════════════════════════════════════════════════════════════
     STYLES
     ══════════════════════════════════════════════════════════════════════════════ -->
<style>
    :root {
        --primary: #14b8a6; --primary-dark: #0d9488; --primary-light: #5eead4;
        --primary-soft: #ccfbf1; --primary-ultra: #f0fdfa;
        --success: #10b981; --warning: #f59e0b; --danger: #ef4444; --info: #3b82f6;
    }

    /* Hero Section */
    .profile-hero {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border-radius: 20px; padding: 40px; color: white; position: relative;
        overflow: hidden; margin-bottom: 30px; box-shadow: 0 10px 40px rgba(20, 184, 166, 0.3);
    }
    .profile-hero::before {
        content: ''; position: absolute; top: -100px; right: -100px; width: 300px; height: 300px;
        background: rgba(255, 255, 255, 0.1); border-radius: 50%; animation: float 6s ease-in-out infinite;
    }
    @keyframes float { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-20px); } }

    /* Avatar */
    .profile-avatar-wrapper { position: relative; width: 140px; height: 140px; }
    .profile-avatar {
        width: 140px; height: 140px; background: rgba(255, 255, 255, 0.2);
        border: 5px solid rgba(255, 255, 255, 0.3); border-radius: 50%;
        display: flex; align-items: center; justify-content: center; font-size: 3.5rem; color: white;
        backdrop-filter: blur(10px); box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    }
    .avatar-upload {
        position: absolute; bottom: 5px; right: 5px; width: 40px; height: 40px;
        background: var(--success); border-radius: 50%; display: flex; align-items: center;
        justify-content: center; cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }
    .avatar-upload:hover { transform: scale(1.1); background: #059669; }

    /* Stats & Cards */
    .stat-card {
        background: white; border-radius: 16px; padding: 24px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        transition: all 0.3s; border-left: 4px solid var(--primary);
    }
    .stat-card:hover { transform: translateY(-5px); box-shadow: 0 8px 24px rgba(20, 184, 166, 0.2); }
    .stat-value {
        font-size: 2.5rem; font-weight: 800;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 8px;
    }
    .stat-trend { display: inline-flex; align-items: center; gap: 4px; font-size: 0.85rem; margin-top: 8px; padding: 4px 12px; border-radius: 20px; }
    .stat-trend.up { background: #d1fae5; color: #065f46; }
    .stat-trend.down { background: #fee2e2; color: #991b1b; }

    /* Timeline */
    .timeline { position: relative; padding: 20px 0; }
    .timeline-item { position: relative; padding-right: 50px; padding-bottom: 30px; }
    .timeline-item:not(:last-child)::before {
        content: ''; position: absolute; right: 19px; top: 45px; width: 2px;
        height: calc(100% - 20px); background: linear-gradient(to bottom, var(--primary-light), transparent);
    }
    .timeline-icon {
        position: absolute; right: 0; top: 0; width: 40px; height: 40px;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 50%;
        display: flex; align-items: center; justify-content: center; color: white;
        box-shadow: 0 4px 12px rgba(20, 184, 166, 0.3);
    }

    /* Progress Ring */
    .progress-ring { width: 150px; height: 150px; position: relative; }
    .progress-ring svg { transform: rotate(-90deg); }
    .progress-ring circle { fill: none; stroke-width: 10; stroke-linecap: round; }
    .progress-ring .bg { stroke: var(--primary-soft); }
    .progress-ring .progress { stroke: var(--primary); transition: stroke-dashoffset 1s ease; }
    .progress-text { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 2rem; font-weight: 800; color: var(--primary-dark); }

    /* Heatmap */
    .heatmap { display: grid; grid-template-columns: repeat(13, 1fr); gap: 4px; }
    .heatmap-cell { aspect-ratio: 1; background: #f1f5f9; border-radius: 4px; transition: all 0.3s; cursor: pointer; }
    .heatmap-cell:hover { transform: scale(1.2); z-index: 10; }
    .heatmap-cell.level-1 { background: #ccfbf1; } .heatmap-cell.level-2 { background: #99f6e4; }
    .heatmap-cell.level-3 { background: #5eead4; } .heatmap-cell.level-4 { background: #2dd4bf; }
    .heatmap-cell.level-5 { background: #14b8a6; }

    /* Others */
    .ranking-card.me { background: linear-gradient(135deg, var(--primary-ultra), var(--primary-soft)); border-left: 4px solid var(--primary); }
    .nav-tabs .nav-link.active { color: var(--primary-dark); border-bottom: 3px solid var(--primary); }
    .task-card.urgent { border-left-color: var(--danger); }
    .task-card.completed { border-left-color: var(--success); opacity: 0.7; }

    @media (max-width: 768px) {
        .heatmap { grid-template-columns: repeat(7, 1fr); }
        .stat-value { font-size: 1.8rem; }
    }
</style>

<!-- ══════════════════════════════════════════════════════════════════════════════
     ALERTS & FEEDBACK
     ══════════════════════════════════════════════════════════════════════════════ -->
<?php echo displayMessage(); ?>

<!-- ══════════════════════════════════════════════════════════════════════════════
     PROFILE HEADER (HERO)
     ══════════════════════════════════════════════════════════════════════════════ -->
<div class="profile-hero">
    <div class="row align-items-center">
        <div class="col-auto">
            <div class="profile-avatar-wrapper">
                <div class="profile-avatar"><i class="fas fa-user"></i></div>
                <div class="avatar-upload" onclick="alert('قابلیت آپلود تصویر به زودی...')"><i class="fas fa-camera"></i></div>
            </div>
        </div>
        <div class="col">
            <h2 class="mb-2"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
            <p class="mb-3 opacity-90">
                <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($user['email']); ?>
                <?php if ($user['mobile']): ?>
                    <span class="ms-3"><i class="fas fa-mobile-alt me-2"></i><?php echo htmlspecialchars($user['mobile']); ?></span>
                <?php endif; ?>
            </p>
            <div class="d-flex flex-wrap gap-2">
                <span class="badge bg-light text-dark"><i class="fas fa-user-tag me-1"></i><?php echo getRoleTitle($user['role']); ?></span>
                <?php if ($user['department']): ?><span class="badge bg-light text-dark"><?php echo htmlspecialchars($user['department']); ?></span><?php endif; ?>
                <span class="badge bg-light text-dark">عضویت: <?php echo formatPersianDate($user['created_at'], 'Y/m/d'); ?></span>
            </div>
        </div>
        <div class="col-auto text-center">
            <div class="progress-ring">
                <svg width="150" height="150">
                    <circle class="bg" cx="75" cy="75" r="65"></circle>
                    <circle class="progress" cx="75" cy="75" r="65" stroke-dasharray="408.4" stroke-dashoffset="<?php echo 408.4 - (408.4 * $profile_completion / 100); ?>"></circle>
                </svg>
                <div class="progress-text"><?php echo $profile_completion; ?>%</div>
            </div>
            <small class="d-block mt-2">تکمیل پروفایل</small>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════════
     STATS ROW
     ══════════════════════════════════════════════════════════════════════════════ -->
<div class="row mb-4">
    <!-- Customers -->
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="text-primary fs-1"><i class="fas fa-users"></i></div>
                <div>
                    <div class="stat-value"><?php echo number_format($stats['total_customers']); ?></div>
                    <div class="stat-label">مشتریان ثبت شده</div>
                    <div class="stat-trend up"><i class="fas fa-arrow-up"></i> <?php echo $current_month['customers']; ?> ماه جاری</div>
                </div>
            </div>
        </div>
    </div>
    <!-- Leads -->
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card" style="border-color: var(--warning);">
            <div class="d-flex align-items-center gap-3">
                <div class="text-warning fs-1"><i class="fas fa-bullseye"></i></div>
                <div>
                    <div class="stat-value" style="-webkit-text-fill-color: var(--warning);"><?php echo number_format($stats['total_leads']); ?></div>
                    <div class="stat-label">لیدهای ثبت شده</div>
                    <div class="stat-trend up"><i class="fas fa-arrow-up"></i> <?php echo $current_month['leads']; ?> ماه جاری</div>
                </div>
            </div>
        </div>
    </div>
    <!-- Tasks -->
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card" style="border-color: var(--info);">
            <div class="d-flex align-items-center gap-3">
                <div class="text-info fs-1"><i class="fas fa-tasks"></i></div>
                <div>
                    <div class="stat-value" style="-webkit-text-fill-color: var(--info);"><?php echo $stats['completed_tasks'] . '/' . $stats['total_tasks']; ?></div>
                    <div class="stat-label">وظایف تکمیل شده</div>
                </div>
            </div>
        </div>
    </div>
    <!-- Revenue -->
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card" style="border-color: var(--success);">
            <div class="d-flex align-items-center gap-3">
                <div class="text-success fs-1"><i class="fas fa-chart-line"></i></div>
                <div>
                    <div class="stat-value" style="font-size: 1.8rem; -webkit-text-fill-color: var(--success);"><?php echo formatMoney($stats['total_revenue'], true); ?></div>
                    <div class="stat-label">کل درآمد</div>
                    <div class="stat-trend up"><i class="fas fa-arrow-up"></i> <?php echo $current_month['sales']; ?> فروش ماه جاری</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════════
     MAIN CONTENT TABS
     ══════════════════════════════════════════════════════════════════════════════ -->
<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom p-0">
                <ul class="nav nav-tabs card-header-tabs mb-0 mx-2" id="profileTabs" role="tablist">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#overview"><i class="fas fa-chart-pie me-2"></i>نمای کلی</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#activity"><i class="fas fa-history me-2"></i>فعالیت‌ها</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tasks"><i class="fas fa-tasks me-2"></i>وظایف امروز</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#goals"><i class="fas fa-bullseye me-2"></i>اهداف</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#settings"><i class="fas fa-cog me-2"></i>تنظیمات</button></li>
                </ul>
            </div>
            
            <div class="card-body p-4">
                <div class="tab-content">
                    
                    <!-- TAB 1: Overview -->
                    <div class="tab-pane fade show active" id="overview">
                        <h5 class="mb-3 text-muted">عملکرد ۶ ماه اخیر</h5>
                        <canvas id="monthlyChart" height="80"></canvas>
                        
                        <hr class="my-4">
                        
                        <h5 class="mb-3 text-muted">نقشه حرارتی فعالیت (۹۰ روز اخیر)</h5>
                        <div class="heatmap" id="activityHeatmap"></div>
                        <div class="d-flex justify-content-between mt-2 small text-muted">
                            <span>کمترین</span><span>بیشترین</span>
                        </div>
                    </div>

                    <!-- TAB 2: Activity Log -->
                    <div class="tab-pane fade" id="activity">
                        <div class="timeline">
                            <?php if(empty($recent_activities)): ?>
                                <p class="text-center text-muted">هنوز فعالیتی ثبت نشده است.</p>
                            <?php else: ?>
                                <?php foreach($recent_activities as $log): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-icon"><i class="fas fa-circle"></i></div>
                                        <div class="timeline-content">
                                            <div class="d-flex justify-content-between">
                                                <strong><?php echo getActionTitle($log['action']); ?></strong>
                                                <small class="text-muted"><?php echo formatPersianDate($log['created_at']); ?></small>
                                            </div>
                                            <?php if($log['details']): ?>
                                                <small class="text-muted d-block mt-1"><?php echo htmlspecialchars($log['details']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- TAB 3: Today Tasks -->
                    <div class="tab-pane fade" id="tasks">
                        <h5 class="mb-3">وظایف امروز <span class="badge bg-info"><?php echo count($today_tasks_list); ?></span></h5>
                        <?php if(empty($today_tasks_list)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                                <p>عالی! هیچ وظیفه‌ای برای امروز ندارید.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach($today_tasks_list as $task): ?>
                                <div class="task-card <?php echo $task['priority'] === 'high' ? 'urgent' : ''; ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($task['title']); ?></h6>
                                            <small class="text-muted"><i class="far fa-clock"></i> <?php echo formatPersianDate($task['due_date'], 'H:i'); ?></small>
                                        </div>
                                        <a href="task_view.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-outline-primary">مشاهده</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- TAB 4: Goals -->
                    <div class="tab-pane fade" id="goals">
                        <div class="row g-3 mb-4">
                            <?php
                                $metrics = [
                                    ['label' => 'فروش', 'target' => $goals['sales_goal'] ?? 0, 'current' => $current_month['sales'], 'color' => 'success'],
                                    ['label' => 'مشتری', 'target' => $goals['customers_goal'] ?? 0, 'current' => $current_month['customers'], 'color' => 'primary'],
                                    ['label' => 'لید', 'target' => $goals['leads_goal'] ?? 0, 'current' => $current_month['leads'], 'color' => 'warning']
                                ];
                                foreach($metrics as $m):
                                    $pct = $m['target'] > 0 ? round(($m['current'] / $m['target']) * 100) : 0;
                            ?>
                            <div class="col-md-4">
                                <div class="goal-card border p-3 rounded">
                                    <div class="d-flex justify-content-between mb-2">
                                        <strong><?php echo $m['label']; ?></strong>
                                        <span class="text-<?php echo $m['color']; ?>"><?php echo $pct; ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-<?php echo $m['color']; ?>" style="width: <?php echo min(100, $pct); ?>%"></div>
                                    </div>
                                    <small class="text-muted mt-2 d-block"><?php echo $m['current']; ?> از <?php echo $m['target']; ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <h6 class="border-top pt-3 mt-3">تنظیم اهداف ماه جاری</h6>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="action" value="set_goals">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small">هدف فروش</label>
                                    <input type="number" class="form-control" name="monthly_sales_goal" value="<?php echo $goals['sales_goal'] ?? 10; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">هدف مشتری</label>
                                    <input type="number" class="form-control" name="monthly_customers_goal" value="<?php echo $goals['customers_goal'] ?? 5; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">هدف لید</label>
                                    <input type="number" class="form-control" name="monthly_leads_goal" value="<?php echo $goals['leads_goal'] ?? 15; ?>">
                                </div>
                                <div class="col-12 text-end">
                                    <button class="btn btn-primary btn-sm">ذخیره اهداف</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- TAB 5: Settings -->
                    <div class="tab-pane fade" id="settings">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="action" value="update_profile">
                            <h6 class="mb-3 text-primary"><i class="fas fa-user-edit"></i> اطلاعات هویتی</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">نام</label>
                                    <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">نام خانوادگی</label>
                                    <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">ایمیل</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">موبایل</label>
                                    <input type="text" class="form-control" name="mobile" value="<?php echo htmlspecialchars($user['mobile'] ?? ''); ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">آدرس</label>
                                    <textarea class="form-control" name="address" rows="2"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-12 text-end">
                                    <button class="btn btn-primary">بروزرسانی اطلاعات</button>
                                </div>
                            </div>
                        </form>

                        <hr class="my-4">

                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="action" value="change_password">
                            <h6 class="mb-3 text-warning"><i class="fas fa-lock"></i> تغییر رمز عبور</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">رمز فعلی</label>
                                    <input type="password" class="form-control" name="current_password" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">رمز جدید</label>
                                    <input type="password" class="form-control" name="new_password" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">تکرار رمز جدید</label>
                                    <input type="password" class="form-control" name="confirm_password" required>
                                </div>
                                <div class="col-12 text-end">
                                    <button class="btn btn-warning text-white">تغییر رمز</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SIDEBAR -->
    <div class="col-lg-4">
        <!-- Ranking -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white"><h5 class="m-0"><i class="fas fa-trophy text-warning me-2"></i>برترین‌های فروش</h5></div>
            <div class="card-body">
                <?php foreach($ranking as $idx => $r): ?>
                    <div class="ranking-card <?php echo $r['id'] == $user_id ? 'me' : ''; ?> p-2 mb-2 rounded border d-flex align-items-center">
                        <span class="badge bg-secondary rounded-circle me-2" style="width:25px;height:25px;display:flex;align-items:center;justify-content:center;"><?php echo $idx+1; ?></span>
                        <div class="flex-grow-1">
                            <small class="fw-bold d-block"><?php echo htmlspecialchars($r['first_name'] . ' ' . $r['last_name']); ?></small>
                            <small class="text-muted"><?php echo formatMoney($r['total_sales_amount']); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Job Info -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white"><h5 class="m-0"><i class="fas fa-briefcase text-muted me-2"></i>اطلاعات شغلی</h5></div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between px-0"><span>سمت:</span> <strong><?php echo htmlspecialchars($user['position'] ?? '-'); ?></strong></li>
                    <li class="list-group-item d-flex justify-content-between px-0"><span>دپارتمان:</span> <strong><?php echo htmlspecialchars($user['department'] ?? '-'); ?></strong></li>
                    <li class="list-group-item d-flex justify-content-between px-0"><span>تاریخ استخدام:</span> <strong><?php echo $user['hire_date'] ? formatPersianDate($user['hire_date'], 'Y/m/d') : '-'; ?></strong></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════════
     SCRIPTS
     ══════════════════════════════════════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1"></script>

<script>
// Feedback Alerts
const params = new URLSearchParams(window.location.search);
if (params.has('updated')) Swal.fire('موفق!', 'اطلاعات پروفایل بروزرسانی شد.', 'success');
if (params.has('password_changed')) Swal.fire('موفق!', 'رمز عبور با موفقیت تغییر کرد.', 'success');
if (params.has('goals_updated')) Swal.fire('موفق!', 'اهداف ماهانه تنظیم شدند.', 'success');

// Monthly Chart
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
const monthlyData = <?php echo json_encode($monthly_chart_data); ?>;
new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: monthlyData.map(d => d.month),
        datasets: [
            { label: 'فروش', data: monthlyData.map(d => d.sales), borderColor: '#10b981', tension: 0.3, fill: false },
            { label: 'مشتری', data: monthlyData.map(d => d.customers), borderColor: '#14b8a6', tension: 0.3, fill: false },
            { label: 'لید', data: monthlyData.map(d => d.leads), borderColor: '#f59e0b', tension: 0.3, fill: false }
        ]
    },
    options: { plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
});

// Heatmap Generation
const weeklyData = <?php echo json_encode(array_column($weekly_heatmap_data, 'count', 'date')); ?>;
const heatmapContainer = document.getElementById('activityHeatmap');
const today = new Date();
const maxCount = Math.max(...Object.values(weeklyData), 1);

for (let i = 90; i >= 0; i--) {
    const d = new Date(); d.setDate(today.getDate() - i);
    const dateStr = d.toISOString().split('T')[0];
    const count = weeklyData[dateStr] || 0;
    const level = Math.min(5, Math.ceil((count / maxCount) * 5));
    
    const cell = document.createElement('div');
    cell.className = `heatmap-cell level-${level}`;
    cell.title = `${dateStr}: ${count} فعالیت`;
    heatmapContainer.appendChild(cell);
}
</script>

<?php include __DIR__ . '/../private/footer.php'; ?>