<?php
/**
 * ══════════════════════════════════════════════════════════════════════════════
 * ReadyCRM V3.5 - PROJECT FORM (CREATE/EDIT)
 * ══════════════════════════════════════════════════════════════════════════════
 * فرم پیشرفته ایجاد و ویرایش پروژه با قابلیت‌های:
 * - انتخاب مشتری، مدیر پروژه و تیم
 * - تعیین بودجه، ددلاین و اولویت
 * - مدیریت Milestones
 * - آپلود فایل‌های پیوست
 *
 * @version 3.5.0
 * @author Ready Studio
 * ══════════════════════════════════════════════════════════════════════════════
 */

$page_title = 'پروژه جدید';
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/database.php';
require_once __DIR__ . '/../private/auth.php';
require_once __DIR__ . '/../private/functions.php';

requireLogin();

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = $project_id > 0;

// ─── FETCH PROJECT DATA ─────────────────────────────────────────────────────
if ($is_edit) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch();
        
        if (!$project) {
            setMessage('پروژه یافت نشد', 'error');
            header("Location: projects.php");
            exit();
        }
        
        $page_title = 'ویرایش پروژه: ' . $project['title'];
        
        // Fetch Team Members
        $members_stmt = $pdo->prepare("SELECT user_id, role FROM project_members WHERE project_id = ?");
        $members_stmt->execute([$project_id]);
        $current_members = $members_stmt->fetchAll(PDO::FETCH_COLUMN);
        
    } catch (PDOException $e) {
        setMessage('خطا در بارگذاری پروژه: ' . $e->getMessage(), 'error');
        header("Location: projects.php");
        exit();
    }
} else {
    $project = [
        'project_code' => 'PRJ-' . strtoupper(substr(uniqid(), -6)),
        'title' => '',
        'description' => '',
        'customer_id' => '',
        'manager_id' => $_SESSION['user_id'],
        'status' => 'not_started',
        'priority' => 'medium',
        'start_date' => date('Y-m-d'),
        'deadline' => '',
        'budget' => 0,
        'progress' => 0
    ];
    $current_members = [];
}

// ─── HANDLE FORM SUBMISSION ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (verifyCSRFToken($csrf_token)) {
        $data = [
            'project_code' => sanitizeInput($_POST['project_code']),
            'title' => sanitizeInput($_POST['title']),
            'description' => sanitizeInput($_POST['description']),
            'customer_id' => (int)$_POST['customer_id'] ?: null,
            'manager_id' => (int)$_POST['manager_id'],
            'status' => $_POST['status'],
            'priority' => $_POST['priority'],
            'start_date' => $_POST['start_date'],
            'deadline' => $_POST['deadline'] ?: null,
            'budget' => (float)str_replace(',', '', $_POST['budget']),
            'progress' => (int)$_POST['progress']
        ];
        
        $team_members = $_POST['team_members'] ?? [];
        
        try {
            $pdo->beginTransaction();
            
            if ($is_edit) {
                // UPDATE
                $sql = "UPDATE projects SET 
                        project_code = ?, title = ?, description = ?, customer_id = ?,
                        manager_id = ?, status = ?, priority = ?, start_date = ?,
                        deadline = ?, budget = ?, progress = ?, updated_at = NOW()
                        WHERE id = ?";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $data['project_code'], $data['title'], $data['description'],
                    $data['customer_id'], $data['manager_id'], $data['status'],
                    $data['priority'], $data['start_date'], $data['deadline'],
                    $data['budget'], $data['progress'], $project_id
                ]);
                
                // Update Team Members
                $pdo->prepare("DELETE FROM project_members WHERE project_id = ?")->execute([$project_id]);
                
                logActivity($_SESSION['user_id'], 'update_project', 'projects', $project_id);
                $message = 'پروژه با موفقیت بروزرسانی شد';
                
            } else {
                // INSERT
                $sql = "INSERT INTO projects (
                        project_code, title, description, customer_id, manager_id,
                        status, priority, start_date, deadline, budget, progress, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $data['project_code'], $data['title'], $data['description'],
                    $data['customer_id'], $data['manager_id'], $data['status'],
                    $data['priority'], $data['start_date'], $data['deadline'],
                    $data['budget'], $data['progress'], $_SESSION['user_id']
                ]);
                
                $project_id = $pdo->lastInsertId();
                
                logActivity($_SESSION['user_id'], 'create_project', 'projects', $project_id);
                $message = 'پروژه جدید با موفقیت ایجاد شد';
            }
            
            // Insert Team Members
            if (!empty($team_members)) {
                $member_stmt = $pdo->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'member')");
                foreach ($team_members as $user_id) {
                    $member_stmt->execute([$project_id, (int)$user_id]);
                }
            }
            
            // Always add manager
            $pdo->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'manager') ON DUPLICATE KEY UPDATE role = 'manager'")
                ->execute([$project_id, $data['manager_id']]);
            
            $pdo->commit();
            setMessage($message, 'success');
            header("Location: project_view.php?id=$project_id");
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            setMessage('خطا در ذخیره پروژه: ' . $e->getMessage(), 'error');
            error_log("Project save error: " . $e->getMessage());
        }
    }
}

// ─── FETCH DATA FOR DROPDOWNS ──────────────────────────────────────────────
try {
    $customers = $pdo->query("SELECT id, company_name, CONCAT(first_name, ' ', last_name) as contact_name 
                               FROM customers WHERE status = 'active' ORDER BY company_name")->fetchAll();
    
    $users = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) as name, position 
                          FROM users WHERE status = 'active' ORDER BY first_name")->fetchAll();
} catch (PDOException $e) {
    $customers = [];
    $users = [];
    error_log("Dropdown data error: " . $e->getMessage());
}

$csrf_token = generateCSRFToken();
include __DIR__ . '/../private/header.php';
?>

<!-- ─── STYLES ────────────────────────────────────────────────────────────── -->
<style>
    :root {
        --brand: #00b0a4;
        --brand-hover: #00968c;
        --brand-light: #e0f2f1;
    }
    
    .form-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .form-card {
        background: white;
        border-radius: 16px;
        padding: 32px;
        border: 1px solid #e2e8f0;
        margin-bottom: 24px;
    }
    
    .section-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 24px;
        padding-bottom: 12px;
        border-bottom: 2px solid var(--brand-light);
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: #475569;
    }
    
    .form-control, .form-select {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        outline: none;
        transition: all 0.3s ease;
        font-size: 0.95rem;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: var(--brand);
        box-shadow: 0 0 0 3px rgba(0, 176, 164, 0.1);
    }
    
    textarea.form-control {
        min-height: 120px;
        resize: vertical;
    }
    
    .member-selector {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 12px;
        max-height: 300px;
        overflow-y: auto;
        padding: 16px;
        background: #f8fafc;
        border-radius: 12px;
    }
    
    .member-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        background: white;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        transition: all 0.2s ease;
    }
    
    .member-item:hover {
        border-color: var(--brand);
        background: var(--brand-light);
    }
    
    .member-item input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    
    .btn-primary {
        background: var(--brand);
        color: white;
        padding: 12px 32px;
        border-radius: 12px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        background: var(--brand-hover);
        transform: translateY(-2px);
    }
    
    .btn-secondary {
        background: #f1f5f9;
        color: #64748b;
        padding: 12px 32px;
        border-radius: 12px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }
    
    .progress-slider {
        width: 100%;
        height: 8px;
        -webkit-appearance: none;
        appearance: none;
        background: #e2e8f0;
        border-radius: 10px;
        outline: none;
    }
    
    .progress-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 20px;
        height: 20px;
        background: var(--brand);
        border-radius: 50%;
        cursor: pointer;
    }
    
    .progress-value {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--brand);
        text-align: center;
        margin-top: 10px;
    }
</style>

<!-- ─── BREADCRUMB ────────────────────────────────────────────────────────── -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">داشبورد</a></li>
        <li class="breadcrumb-item"><a href="projects.php">پروژه‌ها</a></li>
        <li class="breadcrumb-item active"><?php echo $is_edit ? 'ویرایش' : 'ایجاد'; ?></li>
    </ol>
</nav>

<?php echo displayMessage(); ?>

<!-- ─── MAIN FORM ─────────────────────────────────────────────────────────── -->
<form method="POST" class="form-container">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    
    <!-- Basic Info -->
    <div class="form-card">
        <h2 class="section-title">📋 اطلاعات اصلی</h2>
        
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">کد پروژه *</label>
                    <input type="text" name="project_code" class="form-control" 
                           value="<?php echo htmlspecialchars($project['project_code']); ?>" 
                           <?php echo $is_edit ? 'readonly' : ''; ?> required>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="form-group">
                    <label class="form-label">عنوان پروژه *</label>
                    <input type="text" name="title" class="form-control" 
                           value="<?php echo htmlspecialchars($project['title']); ?>" 
                           placeholder="مثال: توسعه سیستم CRM" required>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">توضیحات پروژه</label>
            <textarea name="description" class="form-control" 
                      placeholder="اهداف، نیازمندی‌ها و جزئیات پروژه را شرح دهید..."><?php echo htmlspecialchars($project['description']); ?></textarea>
        </div>
    </div>
    
    <!-- Assignment -->
    <div class="form-card">
        <h2 class="section-title">👥 اختصاص و تیم</h2>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">مشتری</label>
                    <select name="customer_id" class="form-select">
                        <option value="">بدون مشتری</option>
                        <?php foreach ($customers as $c): ?>
                        <option value="<?php echo $c['id']; ?>" 
                                <?php echo ($project['customer_id'] == $c['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['company_name'] . ' - ' . $c['contact_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">مدیر پروژه *</label>
                    <select name="manager_id" class="form-select" required>
                        <?php foreach ($users as $u): ?>
                        <option value="<?php echo $u['id']; ?>" 
                                <?php echo ($project['manager_id'] == $u['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u['name'] . ' - ' . $u['position']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">اعضای تیم (چند انتخابی)</label>
            <div class="member-selector">
                <?php foreach ($users as $u): ?>
                <label class="member-item">
                    <input type="checkbox" name="team_members[]" value="<?php echo $u['id']; ?>"
                           <?php echo in_array($u['id'], $current_members) ? 'checked' : ''; ?>>
                    <span><?php echo htmlspecialchars($u['name']); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Timeline & Budget -->
    <div class="form-card">
        <h2 class="section-title">📅 زمان‌بندی و بودجه</h2>
        
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">تاریخ شروع *</label>
                    <input type="date" name="start_date" class="form-control" 
                           value="<?php echo $project['start_date']; ?>" required>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">ددلاین</label>
                    <input type="date" name="deadline" class="form-control" 
                           value="<?php echo $project['deadline']; ?>">
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">بودجه (تومان)</label>
                    <input type="text" name="budget" class="form-control" 
                           value="<?php echo number_format($project['budget']); ?>" 
                           placeholder="50,000,000">
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">وضعیت *</label>
                    <select name="status" class="form-select" required>
                        <option value="not_started" <?php echo ($project['status'] == 'not_started') ? 'selected' : ''; ?>>شروع نشده</option>
                        <option value="in_progress" <?php echo ($project['status'] == 'in_progress') ? 'selected' : ''; ?>>در حال انجام</option>
                        <option value="on_hold" <?php echo ($project['status'] == 'on_hold') ? 'selected' : ''; ?>>متوقف شده</option>
                        <option value="completed" <?php echo ($project['status'] == 'completed') ? 'selected' : ''; ?>>تکمیل شده</option>
                        <option value="cancelled" <?php echo ($project['status'] == 'cancelled') ? 'selected' : ''; ?>>لغو شده</option>
                    </select>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">اولویت *</label>
                    <select name="priority" class="form-select" required>
                        <option value="low" <?php echo ($project['priority'] == 'low') ? 'selected' : ''; ?>>کم</option>
                        <option value="medium" <?php echo ($project['priority'] == 'medium') ? 'selected' : ''; ?>>متوسط</option>
                        <option value="high" <?php echo ($project['priority'] == 'high') ? 'selected' : ''; ?>>بالا</option>
                        <option value="urgent" <?php echo ($project['priority'] == 'urgent') ? 'selected' : ''; ?>>فوری</option>
                    </select>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">پیشرفت (%)</label>
                    <input type="range" name="progress" class="progress-slider" 
                           min="0" max="100" step="5" 
                           value="<?php echo $project['progress']; ?>" 
                           oninput="document.getElementById('progressValue').textContent = this.value + '%'">
                    <div class="progress-value" id="progressValue"><?php echo $project['progress']; ?>%</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Actions -->
    <div class="d-flex gap-3 justify-content-end">
        <a href="projects.php" class="btn-secondary">انصراف</a>
        <button type="submit" class="btn-primary">
            <i class="fas fa-save me-2"></i>
            <?php echo $is_edit ? 'بروزرسانی پروژه' : 'ایجاد پروژه'; ?>
        </button>
    </div>
</form>

<?php include __DIR__ . '/../private/footer.php'; ?>
