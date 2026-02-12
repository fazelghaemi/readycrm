<?php
/**
 * ══════════════════════════════════════════════════════════════════════════════
 * ReadyCRM V3.5 - PROJECT FORM
 * ══════════════════════════════════════════════════════════════════════════════
 * فرم پیشرفته ثبت و ویرایش پروژه با قابلیت تگ‌گذاری، فرمت پول و محاسبه تاریخ
 * @version 3.5.0
 * @author Ready Studio
 * ══════════════════════════════════════════════════════════════════════════════
 */

$project_id = (int)($_GET['id'] ?? 0);
$page_title = $project_id ? 'ویرایش پروژه' : 'تعریف پروژه جدید';
$breadcrumb = [
    ['title' => 'داشبورد', 'url' => 'dashboard.php'],
    ['title' => 'مدیریت پروژه‌ها', 'url' => 'projects.php'],
    ['title' => $page_title]
];

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/database.php';
require_once __DIR__ . '/../private/auth.php';
require_once __DIR__ . '/../private/functions.php';

// ─── AUTH CHECK ─────────────────────────────────────────────────────────────
$required_permission = $project_id ? 'edit_project' : 'add_project';
// if (!hasPermission($required_permission)) { ... } // Uncomment if permission system is strict

// ─── SVG ICONS ──────────────────────────────────────────────────────────────
$icons = [
    'save' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>',
    'arrow-right' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>',
    'briefcase' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>',
    'align-left' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="17" y1="10" x2="3" y2="10"></line><line x1="21" y1="6" x2="3" y2="6"></line><line x1="21" y1="14" x2="3" y2="14"></line><line x1="17" y1="18" x2="3" y2="18"></line></svg>',
    'user' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>',
    'building' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="9" y1="22" x2="9" y2="22.01"></line><line x1="15" y1="22" x2="15" y2="22.01"></line><line x1="12" y1="22" x2="12" y2="22.01"></line><line x1="12" y1="2" x2="12" y2="22"></line></svg>',
    'calendar' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
    'dollar' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>',
    'tag' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>',
    'percent' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="5" x2="5" y2="19"></line><circle cx="6.5" cy="6.5" r="2.5"></circle><circle cx="17.5" cy="17.5" r="2.5"></circle></svg>',
    'undo' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"></polyline><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path></svg>',
    'hash' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="9" x2="20" y2="9"></line><line x1="4" y1="15" x2="20" y2="15"></line><line x1="10" y1="3" x2="8" y2="21"></line><line x1="16" y1="3" x2="14" y2="21"></line></svg>'
];

// ─── INITIALIZE VARIABLES ───────────────────────────────────────────────────
$project = [
    'project_code' => 'PRJ-' . rand(10000, 99999), // Preview for new
    'title' => '',
    'description' => '',
    'customer_id' => '',
    'manager_id' => $_SESSION['user_id'] ?? '', // Default to current user
    'status' => 'not_started',
    'priority' => 'medium',
    'budget' => '',
    'start_date' => date('Y-m-d'),
    'deadline' => '',
    'progress' => 0,
    'tags' => ''
];

$errors = [];

// Load Existing Project
if ($project_id) {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $existing = $stmt->fetch();
    
    if (!$existing) {
        setMessage('پروژه مورد نظر یافت نشد', 'error');
        header('Location: projects.php');
        exit();
    }
    $project = array_merge($project, $existing);
}

// ─── HANDLE FORM SUBMISSION ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (verifyCSRFToken($csrf_token)) {
        // Collect & Sanitize
        $project['title'] = sanitizeInput($_POST['title']);
        $project['description'] = sanitizeInput($_POST['description']);
        $project['customer_id'] = (int)$_POST['customer_id'] ?: null;
        $project['manager_id'] = (int)$_POST['manager_id'] ?: null;
        $project['status'] = $_POST['status'];
        $project['priority'] = $_POST['priority'];
        // Remove commas from budget for storage
        $project['budget'] = str_replace(',', '', $_POST['budget']);
        $project['start_date'] = $_POST['start_date'] ?: null;
        $project['deadline'] = $_POST['deadline'] ?: null;
        $project['progress'] = min(100, max(0, (int)$_POST['progress']));
        $project['tags'] = sanitizeInput($_POST['tags_input']); // Hidden input from JS

        // Validations
        if (empty($project['title'])) $errors[] = 'عنوان پروژه الزامی است';
        if (empty($project['manager_id'])) $errors[] = 'مدیر پروژه باید مشخص شود';

        if (empty($errors)) {
            try {
                if ($project_id) {
                    // Update
                    $sql = "UPDATE projects SET title=?, description=?, customer_id=?, manager_id=?, status=?, priority=?, budget=?, start_date=?, deadline=?, progress=?, tags=?, updated_at=NOW() WHERE id=?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $project['title'], $project['description'], $project['customer_id'], 
                        $project['manager_id'], $project['status'], $project['priority'], 
                        $project['budget'], $project['start_date'], $project['deadline'], 
                        $project['progress'], $project['tags'], $project_id
                    ]);
                    
                    logActivity($_SESSION['user_id'], 'update_project', 'projects', $project_id);
                    setMessage('پروژه با موفقیت بروزرسانی شد', 'success');
                } else {
                    // Insert
                    // Double check code uniqueness if needed, here we just trust random or user input if field was editable
                    $project_code = 'PRJ-' . rand(10000, 99999); 
                    
                    $sql = "INSERT INTO projects (project_code, title, description, customer_id, manager_id, status, priority, budget, start_date, deadline, progress, tags, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $project_code, $project['title'], $project['description'], $project['customer_id'], 
                        $project['manager_id'], $project['status'], $project['priority'], 
                        $project['budget'], $project['start_date'], $project['deadline'], 
                        $project['progress'], $project['tags'], $_SESSION['user_id']
                    ]);
                    $new_id = $pdo->lastInsertId();

                    // Add Manager to Project Members automatically
                    $pdo->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'manager')")
                        ->execute([$new_id, $project['manager_id']]);

                    logActivity($_SESSION['user_id'], 'create_project', 'projects', $new_id);
                    setMessage('پروژه جدید با موفقیت ایجاد شد', 'success');
                }
                header('Location: projects.php');
                exit();
            } catch (PDOException $e) {
                error_log("Project Error: " . $e->getMessage());
                $errors[] = 'خطا در برقراری ارتباط با پایگاه داده';
            }
        }
    } else {
        $errors[] = 'نشست شما نامعتبر است، لطفا مجدد تلاش کنید';
    }
}

// ─── FETCH DATA ─────────────────────────────────────────────────────────────
$users = $pdo->query("SELECT id, first_name, last_name FROM users WHERE status = 'active'")->fetchAll();
$customers = $pdo->query("SELECT id, name FROM customers ORDER BY name ASC")->fetchAll();

$csrf_token = generateCSRFToken();
include __DIR__ . '/../private/header.php';
?>

<!-- ─── STYLES ────────────────────────────────────────────────────────────── -->
<style>
    :root {
        --brand: #00b0a4;
        --brand-hover: #00968c;
        --dark: #1e293b;
        --gray-border: #e2e8f0;
        --bg-light: #f8fafc;
        --radius-card: 16px;
        --radius-input: 12px;
    }

    body { background-color: var(--bg-light); color: var(--dark); }

    /* Form Layout */
    .form-layout {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 24px;
        margin-top: 24px;
    }

    .app-card {
        background: white;
        border: 1px solid var(--gray-border);
        border-radius: var(--radius-card);
        padding: 30px;
        margin-bottom: 24px;
        box-shadow: none; /* Flat Design */
    }

    .section-title {
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 24px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--gray-border);
        display: flex; align-items: center; gap: 8px;
        color: var(--dark);
    }
    
    .section-title svg { color: var(--brand); }

    /* Inputs */
    .form-group { margin-bottom: 20px; position: relative; }
    .form-label {
        font-weight: 600; font-size: 0.9rem; margin-bottom: 8px; display: block; color: #334155;
    }
    .form-control-custom {
        width: 100%; padding: 12px 16px;
        border: 1px solid var(--gray-border);
        border-radius: var(--radius-input);
        font-size: 0.95rem; outline: none; transition: 0.2s;
        background: #fff;
    }
    .form-control-custom:focus {
        border-color: var(--brand);
    }
    .form-control-custom:disabled {
        background: #f1f5f9; cursor: not-allowed;
    }
    
    textarea.form-control-custom { min-height: 120px; resize: vertical; }

    /* Custom Select */
    select.form-control-custom {
        appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: left 1rem center;
        background-size: 16px 12px;
        padding-left: 2.5rem;
    }

    /* Tag Input System */
    .tag-container {
        border: 1px solid var(--gray-border);
        border-radius: var(--radius-input);
        padding: 8px;
        display: flex; flex-wrap: wrap; gap: 8px;
        background: white;
        min-height: 48px;
    }
    .tag-container:focus-within { border-color: var(--brand); }
    
    .tag-pill {
        background: #e0f2f1; color: #00695c;
        padding: 4px 10px; border-radius: 6px;
        font-size: 0.85rem; display: flex; align-items: center; gap: 6px;
    }
    .tag-remove { cursor: pointer; opacity: 0.6; }
    .tag-remove:hover { opacity: 1; color: #d32f2f; }
    
    .tag-input {
        border: none; outline: none; flex-grow: 1; padding: 4px; font-size: 0.95rem;
    }

    /* Progress Range */
    .range-wrap { position: relative; margin-top: 10px; }
    .range-val {
        position: absolute; top: -30px; left: 50%; transform: translateX(-50%);
        background: var(--brand); color: white; padding: 2px 8px; border-radius: 4px;
        font-size: 0.8rem; font-weight: bold;
    }
    input[type=range] {
        width: 100%; cursor: pointer; accent-color: var(--brand);
    }

    /* Buttons */
    .btn-action {
        width: 100%; padding: 14px; border: none; border-radius: var(--radius-input);
        font-weight: 700; font-size: 1rem; cursor: pointer; transition: 0.2s;
        display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-save { background: var(--brand); color: white; }
    .btn-save:hover { background: var(--brand-hover); }
    
    .btn-cancel { background: white; border: 1px solid var(--gray-border); color: #64748b; margin-top: 12px; }
    .btn-cancel:hover { border-color: var(--dark); color: var(--dark); }

    .quick-date-btn {
        padding: 6px 12px; background: #f1f5f9; border: 1px solid var(--gray-border);
        border-radius: 8px; font-size: 0.8rem; color: #64748b; cursor: pointer; transition: 0.2s;
    }
    .quick-date-btn:hover { border-color: var(--brand); color: var(--brand); background: white; }

    /* Alert */
    .alert-box {
        background: #fee2e2; border: 1px solid #fecaca; color: #991b1b;
        padding: 16px; border-radius: var(--radius-card); margin-bottom: 24px;
    }

    @media (max-width: 900px) {
        .form-layout { grid-template-columns: 1fr; }
    }
</style>

<!-- ─── HEADER ────────────────────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="projects.php" class="btn btn-light border" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 12px;">
            <?php echo $icons['arrow-right']; ?>
        </a>
        <div>
            <h4 class="mb-0 fw-bold"><?php echo $page_title; ?></h4>
            <div class="text-muted small">مدیریت پروژه‌ها / فرم اطلاعات</div>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert-box">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" id="projectForm">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <!-- Tags Hidden Input (Populated by JS) -->
    <input type="hidden" name="tags_input" id="hiddenTagsInput" value="<?php echo htmlspecialchars($project['tags']); ?>">

    <div class="form-layout">
        
        <!-- ─── MAIN COLUMN ────────────────────────────────────────────────── -->
        <div class="main-column">
            
            <div class="app-card">
                <div class="section-title"><?php echo $icons['briefcase']; ?> اطلاعات پایه پروژه</div>
                
                <div class="row">
                    <div class="col-md-9">
                        <div class="form-group">
                            <label class="form-label">عنوان پروژه <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control-custom" placeholder="مثال: طراحی وب‌سایت فروشگاهی..." value="<?php echo htmlspecialchars($project['title']); ?>" required autofocus>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label"><?php echo $icons['hash']; ?> کد پروژه</label>
                            <input type="text" class="form-control-custom text-center fw-bold text-muted" value="<?php echo htmlspecialchars($project['project_code']); ?>" disabled title="کد پروژه سیستمی است">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label"><?php echo $icons['align-left']; ?> توضیحات و اهداف</label>
                    <textarea name="description" class="form-control-custom" placeholder="شرح کاملی از اهداف، نیازمندی‌ها و دامنه پروژه بنویسید..."><?php echo htmlspecialchars($project['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label"><?php echo $icons['building']; ?> مشتری (کارفرما)</label>
                    <select name="customer_id" class="form-control-custom">
                        <option value="">-- انتخاب مشتری --</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $project['customer_id'] == $c['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label"><?php echo $icons['tag']; ?> برچسب‌ها (Tags)</label>
                    <div class="tag-container" id="tagWrapper">
                        <!-- Tags will be injected here -->
                        <input type="text" class="tag-input" id="tagInput" placeholder="تایپ کنید و Enter بزنید...">
                    </div>
                    <small class="text-muted">برای دسته‌بندی بهتر از تگ‌ها استفاده کنید (مثلاً: طراحی، فوری، داخلی)</small>
                </div>
            </div>

            <!-- Budget & Progress -->
            <div class="app-card">
                <div class="section-title"><?php echo $icons['dollar']; ?> مالی و پیشرفت</div>
                
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">بودجه تخصیص یافته (تومان)</label>
                            <input type="text" name="budget" id="budgetInput" class="form-control-custom fw-bold" 
                                   placeholder="0" value="<?php echo number_format((float)$project['budget']); ?>" 
                                   oninput="formatMoney(this)">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label d-flex justify-content-between">
                                <span><?php echo $icons['percent']; ?> درصد پیشرفت اولیه</span>
                                <span id="progressLabel" class="text-primary fw-bold"><?php echo $project['progress']; ?>%</span>
                            </label>
                            <input type="range" name="progress" class="w-100" min="0" max="100" step="5" 
                                   value="<?php echo $project['progress']; ?>" 
                                   oninput="document.getElementById('progressLabel').innerText = this.value + '%'">
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ─── SIDEBAR COLUMN ─────────────────────────────────────────────── -->
        <div class="sidebar-column">
            
            <div class="app-card">
                <div class="section-title">وضعیت و تیم</div>
                
                <div class="form-group">
                    <label class="form-label">وضعیت فعلی</label>
                    <select name="status" class="form-control-custom">
                        <option value="not_started" <?php echo $project['status'] == 'not_started' ? 'selected' : ''; ?>>شروع نشده</option>
                        <option value="in_progress" <?php echo $project['status'] == 'in_progress' ? 'selected' : ''; ?>>در حال انجام</option>
                        <option value="on_hold" <?php echo $project['status'] == 'on_hold' ? 'selected' : ''; ?>>متوقف شده</option>
                        <option value="completed" <?php echo $project['status'] == 'completed' ? 'selected' : ''; ?>>تکمیل شده</option>
                        <option value="cancelled" <?php echo $project['status'] == 'cancelled' ? 'selected' : ''; ?>>لغو شده</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">اولویت</label>
                    <select name="priority" class="form-control-custom">
                        <option value="medium" <?php echo $project['priority'] == 'medium' ? 'selected' : ''; ?>>متوسط</option>
                        <option value="high" <?php echo $project['priority'] == 'high' ? 'selected' : ''; ?>>بالا</option>
                        <option value="urgent" <?php echo $project['priority'] == 'urgent' ? 'selected' : ''; ?>>فوری</option>
                        <option value="low" <?php echo $project['priority'] == 'low' ? 'selected' : ''; ?>>کم</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label"><?php echo $icons['user']; ?> مدیر پروژه</label>
                    <select name="manager_id" class="form-control-custom">
                        <?php foreach ($users as $u): ?>
                            <option value="<?php echo $u['id']; ?>" <?php echo $project['manager_id'] == $u['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted d-block mt-1">مدیر دسترسی کامل به ویرایش پروژه دارد.</small>
                </div>
            </div>

            <div class="app-card">
                <div class="section-title"><?php echo $icons['calendar']; ?> زمان‌بندی</div>
                
                <div class="form-group">
                    <label class="form-label">تاریخ شروع</label>
                    <input type="date" name="start_date" id="start_date" class="form-control-custom" value="<?php echo $project['start_date']; ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">موعد تحویل (ددلاین)</label>
                    <input type="date" name="deadline" id="deadline" class="form-control-custom" value="<?php echo $project['deadline']; ?>">
                    
                    <div class="d-flex gap-2 mt-2">
                        <button type="button" class="quick-date-btn flex-fill" onclick="calcDeadline(1)">+1 ماه</button>
                        <button type="button" class="quick-date-btn flex-fill" onclick="calcDeadline(3)">+3 ماه</button>
                        <button type="button" class="quick-date-btn flex-fill" onclick="calcDeadline(6)">+6 ماه</button>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-action btn-save">
                <?php echo $icons['save']; ?> ذخیره پروژه
            </button>
            <a href="projects.php" class="btn-action btn-cancel">انصراف</a>
            
        </div>
    </div>
</form>

<!-- ─── SCRIPTS ───────────────────────────────────────────────────────────── -->
<script>
    // 1. Money Formatter
    function formatMoney(input) {
        let value = input.value.replace(/,/g, '').replace(/[^0-9]/g, '');
        if (value) {
            input.value = parseInt(value).toLocaleString();
        } else {
            input.value = '';
        }
    }

    // 2. Deadline Calculator
    function calcDeadline(months) {
        const startDateVal = document.getElementById('start_date').value;
        if (!startDateVal) {
            alert('لطفاً ابتدا تاریخ شروع را انتخاب کنید.');
            return;
        }
        const date = new Date(startDateVal);
        date.setMonth(date.getMonth() + months);
        
        const yyyy = date.getFullYear();
        const mm = String(date.getMonth() + 1).padStart(2, '0');
        const dd = String(date.getDate()).padStart(2, '0');
        
        document.getElementById('deadline').value = `${yyyy}-${mm}-${dd}`;
    }

    // 3. Tag System Logic
    document.addEventListener('DOMContentLoaded', function() {
        const tagInput = document.getElementById('tagInput');
        const tagWrapper = document.getElementById('tagWrapper');
        const hiddenInput = document.getElementById('hiddenTagsInput');
        
        let tags = hiddenInput.value ? hiddenInput.value.split(',').filter(t => t.trim() !== '') : [];

        function renderTags() {
            // Clear existing pills (keep input)
            const pills = tagWrapper.querySelectorAll('.tag-pill');
            pills.forEach(p => p.remove());

            tags.forEach((tag, index) => {
                const pill = document.createElement('div');
                pill.className = 'tag-pill';
                pill.innerHTML = `<span>${tag}</span><span class="tag-remove" data-idx="${index}">×</span>`;
                tagWrapper.insertBefore(pill, tagInput);
            });
            hiddenInput.value = tags.join(',');
        }

        tagInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                const val = this.value.trim().replace(',', '');
                if (val && !tags.includes(val)) {
                    tags.push(val);
                    renderTags();
                }
                this.value = '';
            }
            if (e.key === 'Backspace' && this.value === '' && tags.length > 0) {
                tags.pop();
                renderTags();
            }
        });

        tagWrapper.addEventListener('click', function(e) {
            if (e.target.classList.contains('tag-remove')) {
                const idx = parseInt(e.target.dataset.idx);
                tags.splice(idx, 1);
                renderTags();
            } else {
                tagInput.focus();
            }
        });

        // Initial render
        renderTags();
    });
</script>

<?php include __DIR__ . '/../private/footer.php'; ?>