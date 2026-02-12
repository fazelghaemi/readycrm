<?php
/**
 * ══════════════════════════════════════════════════════════════════════════════
 * ReadyCRM V3.5 - TASK FORM
 * ══════════════════════════════════════════════════════════════════════════════
 * فرم ثبت و ویرایش وظیفه با طراحی مدرن فلت
 * @version 3.5.0
 * ══════════════════════════════════════════════════════════════════════════════
 */

$task_id = (int)($_GET['id'] ?? 0);
$page_title = $task_id ? 'ویرایش وظیفه' : 'افزودن وظیفه جدید';
$breadcrumb = [
    ['title' => 'داشبورد', 'url' => 'dashboard.php'],
    ['title' => 'مدیریت وظایف', 'url' => 'tasks.php'],
    ['title' => $page_title]
];

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/database.php';
require_once __DIR__ . '/../private/auth.php';
require_once __DIR__ . '/../private/functions.php';

// ─── AUTH CHECK ─────────────────────────────────────────────────────────────
$required_permission = $task_id ? 'edit_task' : 'add_task';
if (!hasPermission($required_permission)) {
    setMessage('شما دسترسی لازم برای این عملیات را ندارید', 'error');
    header('Location: tasks.php');
    exit();
}

// ─── SVG ICONS ──────────────────────────────────────────────────────────────
$icons = [
    'save' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>',
    'arrow-right' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>',
    'type' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 7 4 4 20 4 20 7"></polyline><line x1="9" y1="20" x2="15" y2="20"></line><line x1="12" y1="4" x2="12" y2="20"></line></svg>',
    'align-left' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="17" y1="10" x2="3" y2="10"></line><line x1="21" y1="6" x2="3" y2="6"></line><line x1="21" y1="14" x2="3" y2="14"></line><line x1="17" y1="18" x2="3" y2="18"></line></svg>',
    'user' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>',
    'link' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>',
    'calendar' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
    'bell' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>',
    'activity' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>',
    'flag' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>',
    'undo' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"></polyline><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path></svg>'
];

// ─── INITIALIZE VARIABLES ───────────────────────────────────────────────────
$task = [
    'title' => '',
    'description' => '',
    'type' => 'other',
    'status' => 'pending',
    'priority' => 'medium',
    'due_date' => '',
    'assigned_to' => '',
    'related_type' => $_GET['related_type'] ?? '',
    'related_id' => $_GET['related_id'] ?? '',
    'reminder_datetime' => ''
];

$errors = [];

// Load existing task
if ($task_id) {
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    $existing_task = $stmt->fetch();
    
    if (!$existing_task) {
        setMessage('وظیفه یافت نشد', 'error');
        header('Location: tasks.php');
        exit();
    }
    $task = array_merge($task, $existing_task);
}

// ─── HANDLE FORM SUBMISSION ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (verifyCSRFToken($csrf_token)) {
        // Collect Data
        $task['title'] = sanitizeInput($_POST['title']);
        $task['description'] = sanitizeInput($_POST['description']);
        $task['type'] = $_POST['type'];
        $task['status'] = $_POST['status'];
        $task['priority'] = $_POST['priority'];
        $task['due_date'] = $_POST['due_date'] ?: null;
        $task['assigned_to'] = (int)$_POST['assigned_to'] ?: null;
        $task['related_type'] = $_POST['related_type'] ?: null;
        $task['related_id'] = (int)$_POST['related_id'] ?: null;
        $task['reminder_datetime'] = $_POST['reminder_datetime'] ?: null;
        
        // Validation
        if (empty($task['title'])) $errors[] = 'عنوان وظیفه الزامی است';
        
        if (empty($errors)) {
            try {
                if ($task_id) {
                    $sql = "UPDATE tasks SET title=?, description=?, type=?, status=?, priority=?, due_date=?, assigned_to=?, related_type=?, related_id=?, reminder_datetime=? WHERE id=?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$task['title'], $task['description'], $task['type'], $task['status'], $task['priority'], $task['due_date'], $task['assigned_to'], $task['related_type'], $task['related_id'], $task['reminder_datetime'], $task_id]);
                    logActivity($_SESSION['user_id'], 'update_task', 'tasks', $task_id, $existing_task, $task);
                    setMessage('وظیفه با موفقیت بروزرسانی شد', 'success');
                } else {
                    $sql = "INSERT INTO tasks (title, description, type, status, priority, due_date, assigned_to, related_type, related_id, reminder_datetime, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$task['title'], $task['description'], $task['type'], $task['status'], $task['priority'], $task['due_date'], $task['assigned_to'], $task['related_type'], $task['related_id'], $task['reminder_datetime'], $_SESSION['user_id']]);
                    logActivity($_SESSION['user_id'], 'create_task', 'tasks', $pdo->lastInsertId());
                    setMessage('وظیفه جدید ایجاد شد', 'success');
                }
                header('Location: tasks.php');
                exit();
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $errors[] = 'خطا در ذخیره اطلاعات';
            }
        }
    } else {
        $errors[] = 'توکن امنیتی نامعتبر است';
    }
}

// ─── FETCH DROPDOWN DATA ────────────────────────────────────────────────────
$users = $pdo->query("SELECT id, first_name, last_name FROM users WHERE status = 'active'")->fetchAll();
$customers = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM customers LIMIT 50")->fetchAll();
$leads = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) as name, title FROM leads LIMIT 50")->fetchAll();

$csrf_token = generateCSRFToken();
include __DIR__ . '/../private/header.php';
?>

<!-- ─── STYLES ────────────────────────────────────────────────────────────── -->
<style>
    :root {
        --brand: #00b0a4;
        --brand-hover: #00968c;
        --brand-soft: #e0f2f1;
        --dark: #010101;
        --gray-border: #e2e8f0;
        --gray-text: #64748b;
        --bg-light: #f8fafc;
        --radius-card: 16px;
        --radius-elem: 12px;
    }

    body {
        background-color: var(--bg-light);
        color: var(--dark);
    }

    /* Card Styling */
    .app-card {
        background: white;
        border: 1px solid var(--gray-border);
        border-radius: var(--radius-card);
        padding: 30px;
        margin-bottom: 24px;
        transition: border-color 0.2s;
    }
    
    .app-card:focus-within {
        border-color: var(--brand);
    }

    /* Grid Layout */
    .form-layout {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 24px;
        margin-top: 24px;
    }

    /* Form Elements */
    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 8px;
    }
    
    .form-label svg {
        color: var(--gray-text);
        opacity: 0.7;
    }

    .form-input, .form-select, .form-textarea {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid var(--gray-border);
        border-radius: var(--radius-elem);
        font-size: 0.95rem;
        color: var(--dark);
        background: white;
        transition: 0.2s;
        font-family: inherit;
    }

    .form-input:focus, .form-select:focus, .form-textarea:focus {
        outline: none;
        border-color: var(--brand);
        box-shadow: 0 0 0 3px rgba(0, 176, 164, 0.1);
    }

    .form-textarea {
        resize: vertical;
        min-height: 120px;
        line-height: 1.6;
    }

    /* Section Header */
    .section-title {
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 24px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--gray-border);
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Buttons */
    .btn-brand {
        background: var(--brand);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: var(--radius-elem);
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: 0.2s;
        text-decoration: none;
    }
    .btn-brand:hover {
        background: var(--brand-hover);
        transform: translateY(-1px);
    }

    .btn-outline {
        background: white;
        border: 1px solid var(--gray-border);
        color: var(--gray-text);
        padding: 12px 24px;
        border-radius: var(--radius-elem);
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: 0.2s;
    }
    .btn-outline:hover {
        border-color: var(--dark);
        color: var(--dark);
        background: var(--bg-light);
    }
    
    .btn-sm-custom {
        padding: 6px 12px;
        font-size: 0.85rem;
        border: 1px solid var(--gray-border);
        background: var(--bg-light);
        color: var(--gray-text);
        border-radius: 8px;
        cursor: pointer;
        transition: 0.2s;
    }
    .btn-sm-custom:hover {
        background: white;
        border-color: var(--brand);
        color: var(--brand);
    }

    /* Select Dropdown Arrow Override */
    select.form-select {
        appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: left 0.75rem center;
        background-size: 16px 12px;
        padding-left: 2.5rem; /* For RTL */
    }

    /* Error Alert */
    .alert-danger {
        background: #fef2f2;
        border: 1px solid #fee2e2;
        color: #991b1b;
        padding: 16px;
        border-radius: var(--radius-elem);
        margin-bottom: 24px;
        font-size: 0.9rem;
    }

    @media (max-width: 900px) {
        .form-layout { grid-template-columns: 1fr; }
    }
</style>

<!-- ─── PAGE HEADER ───────────────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="tasks.php" class="btn-outline" style="padding: 10px;">
            <?php echo $icons['arrow-right']; ?>
        </a>
        <div>
            <h4 class="mb-0 fw-bold" style="color: var(--dark);"><?php echo $page_title; ?></h4>
            <div class="text-muted small">مدیریت وظایف / فرم ثبت اطلاعات</div>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert-danger">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" id="taskForm">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    
    <div class="form-layout">
        
        <!-- ─── LEFT COLUMN (MAIN INFO) ────────────────────────────────────── -->
        <div class="main-column">
            <div class="app-card">
                <div class="section-title"><?php echo $icons['type']; ?> اطلاعات اصلی</div>
                
                <div class="form-group">
                    <label for="title" class="form-label">عنوان وظیفه <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="title" class="form-input" 
                           value="<?php echo htmlspecialchars($task['title']); ?>" 
                           placeholder="مثال: تماس با شرکت برای پیگیری قرارداد..." required autofocus>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label"><?php echo $icons['align-left']; ?> توضیحات تکمیلی</label>
                    <textarea name="description" id="description" class="form-textarea" 
                              placeholder="جزئیات کامل وظیفه را اینجا بنویسید..."><?php echo htmlspecialchars($task['description']); ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="type" class="form-label"><?php echo $icons['activity']; ?> نوع فعالیت</label>
                        <select name="type" id="type" class="form-select">
                            <option value="call" <?php echo $task['type'] === 'call' ? 'selected' : ''; ?>>تماس تلفنی</option>
                            <option value="email" <?php echo $task['type'] === 'email' ? 'selected' : ''; ?>>ارسال ایمیل</option>
                            <option value="meeting" <?php echo $task['type'] === 'meeting' ? 'selected' : ''; ?>>جلسه حضوری/آنلاین</option>
                            <option value="follow_up" <?php echo $task['type'] === 'follow_up' ? 'selected' : ''; ?>>پیگیری</option>
                            <option value="other" <?php echo $task['type'] === 'other' ? 'selected' : ''; ?>>سایر</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 form-group">
                        <label for="assigned_to" class="form-label"><?php echo $icons['user']; ?> مسئول انجام</label>
                        <select name="assigned_to" id="assigned_to" class="form-select">
                            <option value="">خودم (<?php echo $_SESSION['user_name']; ?>)</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?php echo $u['id']; ?>" <?php echo $task['assigned_to'] == $u['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Related To Section -->
            <div class="app-card">
                <div class="section-title"><?php echo $icons['link']; ?> ارتباط با</div>
                
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="related_type" class="form-label">نوع ارتباط</label>
                        <select name="related_type" id="related_type" class="form-select" onchange="toggleRelatedSelect()">
                            <option value="">-- انتخاب کنید --</option>
                            <option value="customer" <?php echo $task['related_type'] === 'customer' ? 'selected' : ''; ?>>مشتری</option>
                            <option value="lead" <?php echo $task['related_type'] === 'lead' ? 'selected' : ''; ?>>سرنخ (Lead)</option>
                        </select>
                    </div>

                    <!-- Customer Select -->
                    <div class="col-md-6 form-group" id="group_customer" style="display: none;">
                        <label for="related_id_customer" class="form-label">انتخاب مشتری</label>
                        <select name="related_id" id="related_id_customer" class="form-select" disabled>
                            <option value="">جستجو و انتخاب مشتری...</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo ($task['related_type'] === 'customer' && $task['related_id'] == $c['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Lead Select -->
                    <div class="col-md-6 form-group" id="group_lead" style="display: none;">
                        <label for="related_id_lead" class="form-label">انتخاب سرنخ</label>
                        <select name="related_id" id="related_id_lead" class="form-select" disabled>
                            <option value="">جستجو و انتخاب لید...</option>
                            <?php foreach ($leads as $l): ?>
                                <option value="<?php echo $l['id']; ?>" <?php echo ($task['related_type'] === 'lead' && $task['related_id'] == $l['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($l['title'] . ' - ' . $l['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── RIGHT COLUMN (SETTINGS) ────────────────────────────────────── -->
        <div class="sidebar-column">
            
            <div class="app-card">
                <div class="section-title"><?php echo $icons['flag']; ?> تنظیمات وضعیت</div>
                
                <div class="form-group">
                    <label for="status" class="form-label">وضعیت</label>
                    <select name="status" id="status" class="form-select">
                        <option value="pending" <?php echo $task['status'] === 'pending' ? 'selected' : ''; ?>>در انتظار</option>
                        <option value="in_progress" <?php echo $task['status'] === 'in_progress' ? 'selected' : ''; ?>>در حال انجام</option>
                        <option value="completed" <?php echo $task['status'] === 'completed' ? 'selected' : ''; ?>>تکمیل شده</option>
                        <option value="cancelled" <?php echo $task['status'] === 'cancelled' ? 'selected' : ''; ?>>لغو شده</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="priority" class="form-label">اولویت</label>
                    <select name="priority" id="priority" class="form-select">
                        <option value="low" <?php echo $task['priority'] === 'low' ? 'selected' : ''; ?>>کم</option>
                        <option value="medium" <?php echo $task['priority'] === 'medium' ? 'selected' : ''; ?>>متوسط</option>
                        <option value="high" <?php echo $task['priority'] === 'high' ? 'selected' : ''; ?>>بالا</option>
                        <option value="urgent" <?php echo $task['priority'] === 'urgent' ? 'selected' : ''; ?>>فوری</option>
                    </select>
                </div>
            </div>

            <div class="app-card">
                <div class="section-title"><?php echo $icons['calendar']; ?> زمان‌بندی</div>
                
                <div class="form-group">
                    <label for="due_date" class="form-label">سررسید</label>
                    <input type="datetime-local" name="due_date" id="due_date" class="form-input"
                           value="<?php echo $task['due_date'] ? date('Y-m-d\TH:i', strtotime($task['due_date'])) : ''; ?>">
                    
                    <div class="d-flex gap-2 mt-2">
                        <button type="button" class="btn-sm-custom w-50" onclick="setQuickDate(24)">فردا</button>
                        <button type="button" class="btn-sm-custom w-50" onclick="setQuickDate(168)">هفته بعد</button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reminder_datetime" class="form-label"><?php echo $icons['bell']; ?> یادآوری</label>
                    <input type="datetime-local" name="reminder_datetime" id="reminder_datetime" class="form-input"
                           value="<?php echo $task['reminder_datetime'] ? date('Y-m-d\TH:i', strtotime($task['reminder_datetime'])) : ''; ?>">
                </div>
            </div>

            <div class="d-flex flex-column gap-3 mt-2">
                <button type="submit" class="btn-brand w-100">
                    <?php echo $icons['save']; ?> ذخیره تغییرات
                </button>
                <div class="d-flex gap-2">
                    <a href="tasks.php" class="btn-outline w-100">
                        انصراف
                    </a>
                    <?php if($task_id): ?>
                    <button type="button" class="btn-outline w-100 text-danger border-danger" onclick="document.getElementById('taskForm').reset()">
                        <?php echo $icons['undo']; ?> بازنشانی
                    </button>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</form>

<script>
    function toggleRelatedSelect() {
        const type = document.getElementById('related_type').value;
        const groupCustomer = document.getElementById('group_customer');
        const groupLead = document.getElementById('group_lead');
        const selectCustomer = document.getElementById('related_id_customer');
        const selectLead = document.getElementById('related_id_lead');

        // Hide all first
        groupCustomer.style.display = 'none';
        groupLead.style.display = 'none';
        selectCustomer.disabled = true;
        selectLead.disabled = true;
        
        // Remove 'related_id' name to avoid conflict, we will add it back to active one
        selectCustomer.removeAttribute('name');
        selectLead.removeAttribute('name');

        if (type === 'customer') {
            groupCustomer.style.display = 'block';
            selectCustomer.disabled = false;
            selectCustomer.setAttribute('name', 'related_id');
        } else if (type === 'lead') {
            groupLead.style.display = 'block';
            selectLead.disabled = false;
            selectLead.setAttribute('name', 'related_id');
        }
    }

    function setQuickDate(hours) {
        const now = new Date();
        now.setHours(now.getHours() + hours);
        now.setMinutes(0); // Round to hour
        // Format to YYYY-MM-DDTHH:MM
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hour = String(now.getHours()).padStart(2, '0');
        const minute = String(now.getMinutes()).padStart(2, '0');
        
        const isoString = `${year}-${month}-${day}T${hour}:${minute}`;
        document.getElementById('due_date').value = isoString;
    }

    // Run on load to set initial state
    document.addEventListener('DOMContentLoaded', toggleRelatedSelect);
</script>

<?php include __DIR__ . '/../private/footer.php'; ?>