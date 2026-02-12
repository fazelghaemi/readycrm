<?php
/**
 * ══════════════════════════════════════════════════════════════════════════════
 * ReadyCRM V3.0 - TASK DETAIL VIEW
 * ══════════════════════════════════════════════════════════════════════════════
 * طراحی مدرن فلت بدون سایه با آیکون‌های SVG اختصاصی
 * @version 3.2.0
 * ══════════════════════════════════════════════════════════════════════════════
 */

$task_id = (int)($_GET['id'] ?? 0);
$page_title = 'جزئیات وظیفه';
$breadcrumb = [
    ['title' => 'داشبورد', 'url' => 'dashboard.php'],
    ['title' => 'مدیریت وظایف', 'url' => 'tasks.php'],
    ['title' => 'مشاهده وظیفه']
];

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/database.php';
require_once __DIR__ . '/../private/auth.php';
require_once __DIR__ . '/../private/functions.php';

// ─── AUTH CHECK ─────────────────────────────────────────────────────────────
if (!hasPermission('view_tasks')) {
    setMessage('شما دسترسی لازم برای مشاهده این صفحه را ندارید', 'error');
    header('Location: tasks.php');
    exit();
}

if (!$task_id) {
    header('Location: tasks.php');
    exit();
}

// ─── SVG ICONS REPOSITORY ───────────────────────────────────────────────────
// مجموعه آیکون‌های مدرن بر اساس درخواست کاربر
$icons = [
    'check' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>',
    'clock' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>',
    'calendar' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
    'user' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>',
    'edit' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>',
    'trash' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>',
    'paperclip' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>',
    'message' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>',
    'send' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>',
    'plus' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>',
    'arrow-right' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>',
    'play' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>',
    'pause' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>',
    'file-pdf' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>',
    'file-word' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>',
    'file-generic' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>',
];

// ─── HANDLE ACTIONS ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (verifyCSRFToken($csrf_token)) {
        
        // 1. Update Status
        if ($action === 'update_status' && hasPermission('edit_task')) {
            $new_status = $_POST['new_status'];
            $completed_at = $new_status === 'completed' ? 'NOW()' : 'NULL';
            $pdo->prepare("UPDATE tasks SET status = ?, completed_at = $completed_at WHERE id = ?")->execute([$new_status, $task_id]);
            logActivity($_SESSION['user_id'], 'update_task_status', 'tasks', $task_id, ['status' => $new_status]);
            header("Location: task_view.php?id=$task_id");
            exit();
        }

        // 2. Add Subtask
        if ($action === 'add_subtask') {
            $title = trim($_POST['subtask_title']);
            if ($title) {
                $pdo->prepare("INSERT INTO task_subtasks (task_id, title) VALUES (?, ?)")->execute([$task_id, $title]);
            }
            header("Location: task_view.php?id=$task_id");
            exit();
        }

        // 3. Toggle Subtask
        if ($action === 'toggle_subtask') {
            $subtask_id = $_POST['subtask_id'];
            $is_completed = $_POST['is_completed'] ? 1 : 0;
            $pdo->prepare("UPDATE task_subtasks SET is_completed = ? WHERE id = ? AND task_id = ?")->execute([$is_completed, $subtask_id, $task_id]);
            exit(); // AJAX response
        }

        // 4. Add Comment
        if ($action === 'add_comment') {
            $comment = trim($_POST['comment']);
            if ($comment) {
                $pdo->prepare("INSERT INTO comments (related_type, related_id, user_id, body) VALUES ('task', ?, ?, ?)")->execute([$task_id, $_SESSION['user_id'], $comment]);
            }
            header("Location: task_view.php?id=$task_id");
            exit();
        }
    }
}

// ─── FETCH DATA ─────────────────────────────────────────────────────────────
// Task Details
$stmt = $pdo->prepare("
    SELECT t.*, 
           CONCAT(u.first_name, ' ', u.last_name) as assignee_name, u.avatar as assignee_avatar,
           CONCAT(c.first_name, ' ', c.last_name) as creator_name
    FROM tasks t
    LEFT JOIN users u ON t.assigned_to = u.id
    LEFT JOIN users c ON t.created_by = c.id
    WHERE t.id = ?
");
$stmt->execute([$task_id]);
$task = $stmt->fetch();

if (!$task) {
    header('Location: tasks.php');
    exit();
}

// Subtasks
$subtasks = $pdo->prepare("SELECT * FROM task_subtasks WHERE task_id = ? ORDER BY id ASC");
$subtasks->execute([$task_id]);
$subtasks_list = $subtasks->fetchAll();

// Comments
$comments = $pdo->prepare("
    SELECT c.*, CONCAT(u.first_name, ' ', u.last_name) as user_name, u.avatar 
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.related_type = 'task' AND c.related_id = ? 
    ORDER BY c.created_at DESC
");
$comments->execute([$task_id]);
$comments_list = $comments->fetchAll();

// Files
$files = $pdo->prepare("SELECT * FROM files WHERE related_type = 'task' AND related_id = ?");
$files->execute([$task_id]);
$files_list = $files->fetchAll();

// Calculate Progress
$total_sub = count($subtasks_list);
$done_sub = 0;
foreach($subtasks_list as $s) if($s['is_completed']) $done_sub++;
$progress = $total_sub > 0 ? round(($done_sub / $total_sub) * 100) : ($task['status'] === 'completed' ? 100 : 0);

$csrf_token = generateCSRFToken();
include __DIR__ . '/../private/header.php';
?>

<!-- ─── STYLES ────────────────────────────────────────────────────────────── -->
<style>
    :root {
        --brand: #00b0a4;
        --brand-hover: #00968c;
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

    /* Card Styling - Flat & Clean */
    .app-card {
        background: white;
        border: 1px solid var(--gray-border);
        border-radius: var(--radius-card);
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: none !important; /* Force no shadow */
    }

    /* Grid Layout */
    .task-layout {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 24px;
        margin-top: 24px;
    }

    /* Headers */
    .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
        font-weight: 700;
        font-size: 1.1rem;
        color: var(--dark);
    }

    /* Badges */
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .status-badge.pending { background: #fff7ed; color: #c2410c; }
    .status-badge.in_progress { background: #eff6ff; color: #1d4ed8; }
    .status-badge.completed { background: #f0fdf4; color: #15803d; }
    .status-badge.cancelled { background: #fef2f2; color: #b91c1c; }

    /* Subtasks */
    .subtask-item {
        display: flex;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid var(--gray-border);
        transition: 0.2s;
    }
    .subtask-item:last-child { border-bottom: none; }
    .subtask-checkbox {
        width: 20px;
        height: 20px;
        border: 2px solid #cbd5e1;
        border-radius: 6px;
        margin-left: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        transition: 0.2s;
    }
    .subtask-checkbox.checked {
        background: var(--brand);
        border-color: var(--brand);
    }
    .subtask-text {
        color: var(--dark);
        font-size: 0.95rem;
    }
    .subtask-text.done {
        text-decoration: line-through;
        color: var(--gray-text);
    }

    /* Attachments */
    .file-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 16px;
    }
    .file-card {
        border: 1px solid var(--gray-border);
        border-radius: var(--radius-elem);
        padding: 16px;
        text-align: center;
        transition: 0.2s;
        text-decoration: none;
        color: var(--dark);
    }
    .file-card:hover {
        border-color: var(--brand);
        background: #f0fdfa;
    }

    /* Comments */
    .comment-item {
        display: flex;
        gap: 16px;
        margin-bottom: 20px;
    }
    .comment-avatar {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #64748b;
    }
    .comment-bubble {
        background: var(--bg-light);
        padding: 12px 16px;
        border-radius: 0 16px 16px 16px;
        flex-grow: 1;
    }

    /* Sidebar Items */
    .sidebar-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
        font-size: 0.95rem;
    }
    .sidebar-label {
        color: var(--gray-text);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .sidebar-value {
        font-weight: 600;
        color: var(--dark);
    }

    /* Buttons */
    .btn-brand {
        background: var(--brand);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: var(--radius-elem);
        font-weight: 500;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: 0.2s;
    }
    .btn-brand:hover {
        background: var(--brand-hover);
    }
    
    .btn-outline {
        background: transparent;
        border: 1px solid var(--gray-border);
        color: var(--dark);
        padding: 8px 16px;
        border-radius: var(--radius-elem);
        font-weight: 500;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: 0.2s;
        text-decoration: none;
    }
    .btn-outline:hover {
        border-color: var(--brand);
        color: var(--brand);
    }

    /* Inputs */
    .form-input {
        width: 100%;
        padding: 10px 16px;
        border: 1px solid var(--gray-border);
        border-radius: var(--radius-elem);
        outline: none;
        transition: 0.2s;
    }
    .form-input:focus {
        border-color: var(--brand);
    }

    @media (max-width: 900px) {
        .task-layout { grid-template-columns: 1fr; }
    }
</style>

<!-- ─── PAGE HEADER ───────────────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="tasks.php" class="btn-outline" style="padding: 8px;">
            <?php echo $icons['arrow-right']; ?>
        </a>
        <div>
            <h4 class="mb-0 fw-bold" style="color: var(--dark);">مشاهده وظیفه</h4>
            <div class="text-muted small">مدیریت پروژه / <?php echo htmlspecialchars($task['title']); ?></div>
        </div>
    </div>
    
    <div class="d-flex gap-2">
        <a href="project_view.php?task_id=<?php echo $task_id; ?>" class="btn-outline d-none d-md-flex">
            مشاهده در پروژه
        </a>
        <?php if (hasPermission('edit_task')): ?>
            <a href="task_form.php?id=<?php echo $task_id; ?>" class="btn-outline">
                <?php echo $icons['edit']; ?> ویرایش
            </a>
        <?php endif; ?>
        <?php if (hasPermission('delete_task')): ?>
            <button onclick="confirmDelete()" class="btn-outline text-danger" style="border-color: #fee2e2;">
                <?php echo $icons['trash']; ?>
            </button>
        <?php endif; ?>
    </div>
</div>

<div class="task-layout">
    
    <!-- ─── LEFT COLUMN (MAIN CONTENT) ────────────────────────────────────── -->
    <div class="main-column">
        
        <!-- Description Card -->
        <div class="app-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="d-flex gap-2">
                    <span class="status-badge <?php echo $task['status']; ?>">
                        <?php echo getStatusTitle($task['status'], 'task'); ?>
                    </span>
                    <span class="status-badge" style="background: #f1f5f9; color: var(--dark);">
                        <?php echo getPriorityTitle($task['priority']); ?>
                    </span>
                </div>
            </div>

            <h2 class="h4 fw-bold mb-3"><?php echo htmlspecialchars($task['title']); ?></h2>
            
            <div class="text-secondary" style="line-height: 1.8;">
                <?php echo $task['description'] ? nl2br(htmlspecialchars($task['description'])) : '<span class="text-muted fst-italic">بدون توضیحات</span>'; ?>
            </div>
        </div>

        <!-- Subtasks (Checklist) -->
        <div class="app-card">
            <div class="section-header">
                <span>چک‌لیست انجام کار</span>
                <span class="badge bg-light text-dark fw-normal"><?php echo "$done_sub / $total_sub"; ?></span>
            </div>

            <div class="progress mb-3" style="height: 6px; border-radius: 10px;">
                <div class="progress-bar" style="width: <?php echo $progress; ?>%; background: var(--brand);"></div>
            </div>

            <div id="subtaskList">
                <?php foreach ($subtasks_list as $sub): ?>
                    <div class="subtask-item" onclick="toggleSubtask(<?php echo $sub['id']; ?>, <?php echo $sub['is_completed']; ?>)">
                        <div class="subtask-checkbox <?php echo $sub['is_completed'] ? 'checked' : ''; ?>">
                            <?php if ($sub['is_completed']) echo $icons['check']; ?>
                        </div>
                        <div class="subtask-text <?php echo $sub['is_completed'] ? 'done' : ''; ?>">
                            <?php echo htmlspecialchars($sub['title']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <form method="POST" class="mt-3 d-flex gap-2">
                <input type="hidden" name="action" value="add_subtask">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="text" name="subtask_title" class="form-input" placeholder="افزودن مورد جدید به چک‌لیست..." required>
                <button type="submit" class="btn-outline" style="white-space: nowrap;">
                    <?php echo $icons['plus']; ?> افزودن
                </button>
            </form>
        </div>

        <!-- Attachments -->
        <div class="app-card">
            <div class="section-header">
                <span>فایل‌های پیوست</span>
                <a href="files.php?task_id=<?php echo $task_id; ?>" class="text-decoration-none small" style="color: var(--brand);">
                    <?php echo $icons['plus']; ?> افزودن فایل
                </a>
            </div>

            <?php if (empty($files_list)): ?>
                <div class="text-center text-muted py-3 small">هیچ فایلی پیوست نشده است</div>
            <?php else: ?>
                <div class="file-grid">
                    <?php foreach ($files_list as $file): 
                        $ext = pathinfo($file['original_name'], PATHINFO_EXTENSION);
                        $icon = $icons['file-generic'];
                        if (in_array($ext, ['jpg', 'png', 'jpeg'])) $icon = '<img src="'.$file['file_path'].'" style="width:32px;height:32px;object-fit:cover;border-radius:6px;">';
                        elseif ($ext == 'pdf') $icon = $icons['file-pdf'];
                        elseif (in_array($ext, ['doc', 'docx'])) $icon = $icons['file-word'];
                    ?>
                        <a href="<?php echo $file['file_path']; ?>" target="_blank" class="file-card">
                            <div class="mb-2"><?php echo $icon; ?></div>
                            <div class="text-truncate small"><?php echo htmlspecialchars($file['original_name']); ?></div>
                            <div class="text-muted" style="font-size: 0.7rem;"><?php echo formatFileSize($file['file_size']); ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Comments -->
        <div class="app-card">
            <div class="section-header">
                <span>نظرات و گفتگو</span>
            </div>

            <div class="comments-container mb-4">
                <?php if (empty($comments_list)): ?>
                    <p class="text-muted small text-center">هنوز نظری ثبت نشده است.</p>
                <?php else: ?>
                    <?php foreach ($comments_list as $comment): ?>
                        <div class="comment-item">
                            <div class="comment-avatar">
                                <?php echo mb_substr($comment['user_name'], 0, 1); ?>
                            </div>
                            <div class="comment-bubble">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="fw-bold small"><?php echo htmlspecialchars($comment['user_name']); ?></span>
                                    <span class="text-muted" style="font-size: 0.7rem;"><?php echo formatPersianDate($comment['created_at']); ?></span>
                                </div>
                                <div class="text-secondary small">
                                    <?php echo nl2br(htmlspecialchars($comment['body'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <form method="POST" class="d-flex gap-3">
                <input type="hidden" name="action" value="add_comment">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="comment-avatar" style="background: var(--brand); color: white;">
                    <?php echo $icons['user']; ?>
                </div>
                <div class="flex-grow-1">
                    <input type="text" name="comment" class="form-input" placeholder="نوشتن نظر جدید..." required>
                    <div class="d-flex justify-content-end mt-2">
                        <button type="submit" class="btn-brand" style="padding: 6px 16px; font-size: 0.9rem;">
                            <?php echo $icons['send']; ?> ارسال
                        </button>
                    </div>
                </div>
            </form>
        </div>

    </div>

    <!-- ─── RIGHT COLUMN (SIDEBAR) ────────────────────────────────────────── -->
    <div class="sidebar-column">
        
        <!-- Main Actions -->
        <div class="app-card" style="background: #f0fdfa; border-color: var(--brand);">
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <?php if ($task['status'] !== 'completed'): ?>
                    <input type="hidden" name="new_status" value="completed">
                    <button type="submit" class="btn-brand w-100 justify-content-center">
                        <?php echo $icons['check']; ?> تکمیل وظیفه
                    </button>
                <?php else: ?>
                    <input type="hidden" name="new_status" value="in_progress">
                    <button type="submit" class="btn-outline w-100 justify-content-center" style="background: white;">
                        بازگشایی مجدد
                    </button>
                <?php endif; ?>
            </form>
        </div>

        <!-- Meta Info -->
        <div class="app-card">
            <div class="sidebar-row">
                <div class="sidebar-label"><?php echo $icons['calendar']; ?> تاریخ ایجاد</div>
                <div class="sidebar-value"><?php echo formatPersianDate($task['created_at'], 'Y/m/d'); ?></div>
            </div>
            
            <div class="sidebar-row">
                <div class="sidebar-label"><?php echo $icons['clock']; ?> سررسید</div>
                <div class="sidebar-value <?php echo (strtotime($task['due_date']) < time() && $task['status']!='completed') ? 'text-danger' : ''; ?>">
                    <?php echo $task['due_date'] ? formatPersianDate($task['due_date'], 'Y/m/d') : '---'; ?>
                </div>
            </div>

            <div class="sidebar-row">
                <div class="sidebar-label"><?php echo $icons['play']; ?> زمان صرف شده</div>
                <div class="sidebar-value d-flex align-items-center gap-2">
                    <span id="timeDisplay"><?php echo gmdate("H:i:s", $task['time_spent'] ?? 0); ?></span>
                    <!-- دکمه تایمر نمایشی است، در بک‌اند نیاز به لاجیک دارد -->
                    <button class="btn-outline p-1" style="border-radius: 50%; width: 24px; height: 24px; padding: 0; justify-content: center;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- People -->
        <div class="app-card">
            <div class="section-header" style="font-size: 1rem;">افراد مرتبط</div>
            
            <div class="mb-3">
                <span class="text-muted small d-block mb-1">مسئول انجام</span>
                <div class="d-flex align-items-center gap-2">
                    <div class="comment-avatar" style="width: 32px; height: 32px; background: var(--brand); color: white; font-size: 0.8rem;">
                        <?php echo $task['assignee_name'] ? mb_substr($task['assignee_name'], 0, 1) : '?'; ?>
                    </div>
                    <a href="#" class="text-decoration-none text-dark fw-bold small">
                        <?php echo $task['assignee_name'] ?: 'تعیین نشده'; ?>
                    </a>
                </div>
            </div>

            <div>
                <span class="text-muted small d-block mb-1">ایجاد کننده</span>
                <div class="d-flex align-items-center gap-2">
                    <div class="comment-avatar" style="width: 32px; height: 32px; background: #e2e8f0; font-size: 0.8rem;">
                        <?php echo $task['creator_name'] ? mb_substr($task['creator_name'], 0, 1) : '?'; ?>
                    </div>
                    <span class="small text-secondary">
                        <?php echo $task['creator_name']; ?>
                    </span>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ─── SCRIPTS ───────────────────────────────────────────────────────────── -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function toggleSubtask(id, currentStatus) {
        const newStatus = currentStatus ? 0 : 1;
        
        // Optimistic UI Update (Immediate visual feedback)
        // In a real scenario, fetch logic goes here.
        // For now we reload to sync with PHP logic provided above.
        
        const formData = new FormData();
        formData.append('action', 'toggle_subtask');
        formData.append('subtask_id', id);
        formData.append('is_completed', newStatus);
        formData.append('csrf_token', '<?php echo $csrf_token; ?>');

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        }).then(() => {
            location.reload(); 
        });
    }

    function confirmDelete() {
        Swal.fire({
            title: 'حذف وظیفه',
            text: "آیا از حذف این وظیفه مطمئن هستید؟",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'بله، حذف کن',
            cancelButtonText: 'خیر'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.action = 'tasks.php'; // Assuming tasks.php handles delete via POST
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>

<?php include __DIR__ . '/../private/footer.php'; ?>