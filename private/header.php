<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// دریافت اطلاعات کاربر
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();

// دریافت نام کامل کاربر
$user_full_name = trim($current_user['first_name'] . ' ' . $current_user['last_name']) ?: $current_user['username'];
$user_role = $current_user['role'];

// تبدیل نقش به فارسی
$role_names = [
    'admin' => 'مدیر سیستم',
    'manager' => 'مدیر',
    'sales' => 'فروشنده',
    'support' => 'پشتیبانی'
];
$user_role_fa = $role_names[$user_role] ?? 'کاربر';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#00b0a4">
    <title><?php echo $page_title ?? 'داشبورد'; ?> - سیستم CRM | ردی استودیو</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/favicon.png">
    <link rel="apple-touch-icon" href="../assets/favicon.png">
    
    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <style>
        /* ==================== FONT FACE ==================== */
        @font-face {
            font-family: 'YekanBakh';
            src: url('../assets/YekanBakhFaNum-VF.ttf') format('truetype-variations');
            font-weight: 100 900;
            font-display: swap;
        }

        /* ==================== CSS VARIABLES ==================== */
        :root {
            /* Brand Colors */
            --brand-primary: #00b0a4;
            --brand-primary-dark: #008c82;
            --brand-primary-light: #00d4c5;
            --brand-black: #000000;
            
            /* Gradient */
            --gradient-primary: linear-gradient(135deg, #00b0a4 0%, #00d4c5 100%);
            --gradient-dark: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
            --gradient-overlay: linear-gradient(135deg, rgba(0,176,164,0.1) 0%, rgba(0,212,197,0.05) 100%);
            
            /* Neutral Colors */
            --gray-50: #fafafa;
            --gray-100: #f5f5f5;
            --gray-200: #eeeeee;
            --gray-300: #e0e0e0;
            --gray-400: #bdbdbd;
            --gray-500: #9e9e9e;
            --gray-600: #757575;
            --gray-700: #616161;
            --gray-800: #424242;
            --gray-900: #212121;
            
            /* Semantic Colors */
            --success: #00c853;
            --success-light: #69f0ae;
            --warning: #ffc107;
            --warning-light: #ffd54f;
            --danger: #f44336;
            --danger-light: #ef5350;
            --info: #2196f3;
            --info-light: #64b5f6;
            
            /* Shadows - Modern 2026 */
            --shadow-xs: 0 1px 2px 0 rgba(0, 0, 0, 0.03);
            --shadow-sm: 0 2px 4px 0 rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 8px 0 rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 8px 16px 0 rgba(0, 0, 0, 0.08);
            --shadow-xl: 0 12px 24px 0 rgba(0, 0, 0, 0.1);
            --shadow-2xl: 0 16px 32px 0 rgba(0, 0, 0, 0.12);
            --shadow-brand: 0 8px 24px 0 rgba(0, 176, 164, 0.2);
            
            /* Border Radius - Modern Curves */
            --radius-xs: 8px;
            --radius-sm: 12px;
            --radius-md: 16px;
            --radius-lg: 20px;
            --radius-xl: 24px;
            --radius-2xl: 32px;
            --radius-full: 9999px;
            
            /* Spacing */
            --spacing-xs: 0.5rem;
            --spacing-sm: 0.75rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;
            
            /* Transitions - Smooth & Fast */
            --transition-fast: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-base: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            
            /* Layout */
            --sidebar-width: 280px;
            --header-height: 72px;
        }

        /* ==================== RESET & BASE ==================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'YekanBakh', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--gray-50);
            color: var(--gray-900);
            font-size: 15px;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            font-weight: 400;
        }

        html {
            scroll-behavior: smooth;
        }

        /* ==================== LAYOUT ==================== */
        .app-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* ==================== SIDEBAR ==================== */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--brand-black) 0%, #1a1a1a 100%);
            color: white;
            position: fixed;
            right: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            transition: var(--transition-smooth);
            z-index: 1000;
            box-shadow: var(--shadow-xl);
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: var(--brand-primary);
            border-radius: var(--radius-full);
        }

        /* Sidebar Header */
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: linear-gradient(135deg, rgba(0,176,164,0.15) 0%, rgba(0,212,197,0.05) 100%);
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
            color: white;
            transition: var(--transition-base);
        }

        .sidebar-brand:hover {
            transform: translateX(-3px);
        }

        .sidebar-logo {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            background: var(--gradient-primary);
            padding: 8px;
            box-shadow: var(--shadow-brand);
        }

        .sidebar-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .sidebar-brand-text h1 {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-brand-text p {
            font-size: 0.75rem;
            margin: 0;
            color: var(--gray-400);
            font-weight: 400;
        }

        /* Sidebar Navigation */
        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-section {
            margin-bottom: 2rem;
        }

        .nav-section-title {
            padding: 0.5rem 1.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .nav-item {
            list-style: none;
            margin-bottom: 0.25rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.875rem;
            padding: 0.875rem 1.5rem;
            color: var(--gray-300);
            text-decoration: none;
            transition: var(--transition-base);
            font-weight: 500;
            font-size: 0.9375rem;
            position: relative;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 0;
            background: var(--gradient-primary);
            border-radius: 0 var(--radius-xs) var(--radius-xs) 0;
            transition: var(--transition-base);
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.05);
            color: white;
            padding-right: 1.75rem;
        }

        .nav-link:hover::before {
            height: 70%;
        }

        .nav-link.active {
            background: linear-gradient(90deg, rgba(0,176,164,0.15) 0%, transparent 100%);
            color: var(--brand-primary-light);
            padding-right: 1.75rem;
        }

        .nav-link.active::before {
            height: 70%;
        }

        .nav-link i {
            font-size: 1.125rem;
            width: 20px;
            text-align: center;
        }

        .nav-badge {
            margin-right: auto;
            padding: 0.25rem 0.5rem;
            background: var(--danger);
            color: white;
            font-size: 0.6875rem;
            border-radius: var(--radius-full);
            font-weight: 600;
            min-width: 20px;
            text-align: center;
        }

        /* Sidebar Footer */
        .sidebar-footer {
            padding: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: auto;
            background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.3) 100%);
        }

        .sidebar-footer-brand {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            color: var(--gray-500);
            text-decoration: none;
            transition: var(--transition-base);
            padding: 0.5rem;
            border-radius: var(--radius-sm);
        }

        .sidebar-footer-brand:hover {
            background: rgba(255, 255, 255, 0.05);
            color: var(--brand-primary-light);
        }

        .sidebar-footer-brand i {
            color: var(--brand-primary);
        }

        /* ==================== MAIN CONTENT ==================== */
        .main-content {
            margin-right: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            min-height: 100vh;
            transition: var(--transition-smooth);
        }

        /* Top Header */
        .top-header {
            height: var(--header-height);
            background: white;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: var(--shadow-sm);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .page-title-wrapper h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0;
        }

        .breadcrumb-wrapper {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--gray-600);
            margin-top: 0.25rem;
        }

        .breadcrumb-wrapper a {
            color: var(--brand-primary);
            text-decoration: none;
            transition: var(--transition-fast);
        }

        .breadcrumb-wrapper a:hover {
            color: var(--brand-primary-dark);
        }

        .header-right {
            margin-right: auto;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* User Dropdown */
        .user-dropdown {
            position: relative;
        }

        .user-trigger {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            background: var(--gray-50);
            border-radius: var(--radius-full);
            cursor: pointer;
            transition: var(--transition-base);
            border: 2px solid transparent;
        }

        .user-trigger:hover {
            background: var(--gray-100);
            border-color: var(--brand-primary);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-full);
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            box-shadow: var(--shadow-md);
        }

        .user-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .user-name {
            font-weight: 600;
            color: var(--gray-900);
            font-size: 0.9375rem;
            line-height: 1.2;
        }

        .user-role {
            font-size: 0.75rem;
            color: var(--gray-600);
            line-height: 1.2;
        }

        .dropdown-menu {
            position: absolute;
            top: calc(100% + 0.5rem);
            left: 0;
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
            min-width: 240px;
            padding: 0.5rem;
            display: none;
            border: 1px solid var(--gray-200);
        }

        .user-dropdown:hover .dropdown-menu {
            display: block;
            animation: fadeInDown 0.2s ease;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--gray-700);
            text-decoration: none;
            border-radius: var(--radius-sm);
            transition: var(--transition-fast);
            font-size: 0.9375rem;
        }

        .dropdown-item:hover {
            background: var(--gray-50);
            color: var(--gray-900);
        }

        .dropdown-item i {
            width: 20px;
            text-align: center;
            font-size: 1rem;
        }

        .dropdown-divider {
            height: 1px;
            background: var(--gray-200);
            margin: 0.5rem 0;
        }

        .dropdown-item.text-danger:hover {
            background: rgba(244, 67, 54, 0.1);
            color: var(--danger);
        }

        /* Content Area */
        .content-wrapper {
            padding: 2rem;
        }

        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-right: 0;
                width: 100%;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .top-header {
                padding: 0 1rem;
            }

            .content-wrapper {
                padding: 1rem;
            }
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            background: var(--gray-100);
            border: none;
            color: var(--gray-700);
            font-size: 1.25rem;
            cursor: pointer;
            transition: var(--transition-base);
        }

        .mobile-menu-toggle:hover {
            background: var(--brand-primary);
            color: white;
        }
    </style>
</head>
<body>
    <div class="app-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <!-- Sidebar Header -->
            <div class="sidebar-header">
                <a href="dashboard.php" class="sidebar-brand">
                    <div class="sidebar-logo">
                        <img src="../assets/favicon.png" alt="Logo">
                    </div>
                    <div class="sidebar-brand-text">
                        <h1>CRM سیستم</h1>
                        <p>ردی استودیو</p>
                    </div>
                </a>
            </div>

            <!-- Sidebar Navigation -->
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">منوی اصلی</div>
                    <ul style="padding: 0; margin: 0;">
                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                                <i class="fas fa-th-large"></i>
                                <span>داشبورد</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="leads.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'leads.php' ? 'active' : ''; ?>">
                                <i class="fas fa-bullseye"></i>
                                <span>لیدها</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="customers.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>">
                                <i class="fas fa-users"></i>
                                <span>مشتریان</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="sales.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'sales.php' ? 'active' : ''; ?>">
                                <i class="fas fa-shopping-cart"></i>
                                <span>فروش</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="tasks.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'tasks.php' ? 'active' : ''; ?>">
                                <i class="fas fa-tasks"></i>
                                <span>وظایف</span>
                                <?php
                                $pending = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'pending'")->fetchColumn();
                                if ($pending > 0):
                                ?>
                                <span class="nav-badge"><?php echo $pending; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">مدیریت</div>
                    <ul style="padding: 0; margin: 0;">
                        <li class="nav-item">
                            <a href="products.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
                                <i class="fas fa-box"></i>
                                <span>محصولات</span>
                            </a>
                        </li>
                        <?php if (hasRole('admin') || hasRole('manager')): ?>
                        <li class="nav-item">
                            <a href="users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                                <i class="fas fa-user-tie"></i>
                                <span>کاربران</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="reports.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                                <i class="fas fa-chart-line"></i>
                                <span>گزارشات</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="activity_logs.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'activity_logs.php' ? 'active' : ''; ?>">
                                <i class="fas fa-history"></i>
                                <span>لاگ فعالیت‌ها</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <?php if (hasRole('admin')): ?>
                <div class="nav-section">
                    <div class="nav-section-title">سیستم</div>
                    <ul style="padding: 0; margin: 0;">
                        <li class="nav-item">
                            <a href="settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                                <i class="fas fa-cog"></i>
                                <span>تنظیمات</span>
                            </a>
                        </li>
                    </ul>
                </div>
                <?php endif; ?>
            </nav>

            <!-- Sidebar Footer -->
            <div class="sidebar-footer">
                <a href="https://readystudio.ir/" target="_blank" class="sidebar-footer-brand">
                    <i class="fas fa-code"></i>
                    <span>توسعه توسط ردی استودیو</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Header -->
            <header class="top-header">
                <div class="header-left">
                    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <div class="page-title-wrapper">
                        <h2><?php echo $page_title ?? 'داشبورد'; ?></h2>
                        <?php if (isset($breadcrumb) && !empty($breadcrumb)): ?>
                        <div class="breadcrumb-wrapper">
                            <?php foreach ($breadcrumb as $index => $item): ?>
                                <?php if ($index > 0): ?>
                                    <i class="fas fa-chevron-left" style="font-size: 0.625rem;"></i>
                                <?php endif; ?>
                                <?php if (isset($item['url'])): ?>
                                    <a href="<?php echo $item['url']; ?>"><?php echo $item['title']; ?></a>
                                <?php else: ?>
                                    <span><?php echo $item['title']; ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="header-right">
                    <!-- User Dropdown -->
                    <div class="user-dropdown">
                        <div class="user-trigger">
                            <div class="user-avatar">
                                <?php echo mb_substr($user_full_name, 0, 1, 'UTF-8'); ?>
                            </div>
                            <div class="user-info">
                                <span class="user-name"><?php echo htmlspecialchars($user_full_name); ?></span>
                                <span class="user-role"><?php echo $user_role_fa; ?></span>
                            </div>
                            <i class="fas fa-chevron-down" style="color: var(--gray-500); font-size: 0.75rem;"></i>
                        </div>
                        
                        <div class="dropdown-menu">
                            <a href="profile.php" class="dropdown-item">
                                <i class="fas fa-user"></i>
                                <span>پروفایل من</span>
                            </a>
                            <a href="settings.php" class="dropdown-item">
                                <i class="fas fa-cog"></i>
                                <span>تنظیمات</span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="dropdown-item text-danger">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>خروج</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Wrapper -->
            <div class="content-wrapper">
                <?php
                // نمایش پیام‌ها
                if (isset($_SESSION['message'])):
                    $message = $_SESSION['message'];
                    $message_type = $_SESSION['message_type'] ?? 'info';
                    unset($_SESSION['message'], $_SESSION['message_type']);
                    
                    $alert_class = [
                        'success' => 'alert-success',
                        'error' => 'alert-danger',
                        'warning' => 'alert-warning',
                        'info' => 'alert-info'
                    ][$message_type] ?? 'alert-info';
                ?>
                <div class="alert <?php echo $alert_class; ?> alert-dismissible fade show" role="alert" style="border-radius: var(--radius-lg); border: none; box-shadow: var(--shadow-sm);">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
