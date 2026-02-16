<?php
/**
 * ══════════════════════════════════════════════════════════════════════════════
 * ReadyCRM V3.6 - SMS CAMPAIGN FORM (CREATE/EDIT)
 * ══════════════════════════════════════════════════════════════════════════════
 * فرم پیشرفته ایجاد و ویرایش کمپین پیامکی با قابلیت‌های:
 * - انتخاب الگو یا پیام دستی
 * - انتخاب مخاطبان (مشتریان، لیدها، دستی، CSV)
 * - برنامه‌ریزی زمانی ارسال
 * - برآورد هزینه Real-time
 * - پیش‌نمایش پیام
 *
 * @version 3.6.0
 * @author ReadyCRM Team
 * @package ReadyCRM\SMS
 * ══════════════════════════════════════════════════════════════════════════════
 */

$campaign_id = (int)($_GET['id'] ?? 0);
$page_title = $campaign_id ? 'ویرایش کمپین پیامکی' : 'کمپین پیامکی جدید';
$breadcrumb = [
    ['title' => 'داشبورد', 'url' => 'dashboard.php'],
    ['title' => 'کمپین‌های پیامکی', 'url' => 'campaigns.php'],
    ['title' => $page_title]
];

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/database.php';
require_once __DIR__ . '/../private/auth.php';
require_once __DIR__ . '/../private/functions.php';
require_once __DIR__ . '/../private/sms/SmsTemplateService.php';
require_once __DIR__ . '/../private/sms/SmsRecipientResolver.php';

// ─── AUTH CHECK ─────────────────────────────────────────────────────────────
requireLogin();
if (!hasPermission('manage_sms_campaigns')) {
    setMessage('شما دسترسی لازم برای این عملیات را ندارید', 'error');
    header('Location: campaigns.php');
    exit();
}

// ─── SVG ICONS (مطابق task_form.php) ───────────────────────────────────────
$icons = [
    'save' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>',
    'arrow-right' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>',
    'send' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>',
    'users' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
    'calendar' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
    'type' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 7 4 4 20 4 20 7"></polyline><line x1="9" y1="20" x2="15" y2="20"></line><line x1="12" y1="4" x2="12" y2="20"></line></svg>',
    'file-text' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>',
    'dollar' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>',
    'upload' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>'
];

// ─── INITIALIZE VARIABLES ───────────────────────────────────────────────────
$campaign = [
    'name' => '',
    'template_id' => '',
    'message_text' => '',
    'recipient_type' => 'customers',
    'recipient_filters' => '',
    'scheduled_at' => '',
    'status' => 'draft'
];

$errors = [];
$templateService = new SmsTemplateService($pdo, $_SESSION['user_id']);

// ─── LOAD EXISTING CAMPAIGN ─────────────────────────────────────────────────
if ($campaign_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM sms_campaigns WHERE id = ?");
        $stmt->execute([$campaign_id]);
        $existing_campaign = $stmt->fetch();

        if (!$existing_campaign) {
            setMessage('کمپین یافت نشد', 'error');
            header('Location: campaigns.php');
            exit();
        }

        // Only allow editing draft or scheduled campaigns
        if (!in_array($existing_campaign['status'], ['draft', 'scheduled'])) {
            setMessage('فقط کمپین‌های پیش‌نویس یا برنامه‌ریزی‌شده قابل ویرایش هستند', 'warning');
            header('Location: campaigns.php');
            exit();
        }

        $campaign = array_merge($campaign, $existing_campaign);
    } catch (PDOException $e) {
        setMessage('خطا در بارگذاری کمپین: ' . $e->getMessage(), 'error');
        header('Location: campaigns.php');
        exit();
    }
}

// ─── HANDLE FORM SUBMISSION ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (verifyCSRFToken($csrf_token)) {
        $campaign['name'] = sanitizeInput($_POST['name']);
        $campaign['template_id'] = (int)$_POST['template_id'] ?: null;
        $campaign['message_text'] = sanitizeInput($_POST['message_text']);
        $campaign['recipient_type'] = $_POST['recipient_type'];
        $campaign['recipient_filters'] = sanitizeInput($_POST['recipient_filters']);
        $campaign['scheduled_at'] = $_POST['scheduled_at'] ?: null;
        $campaign['status'] = $_POST['status'] ?? 'draft';

        // Validation
        if (empty($campaign['name'])) {
            $errors[] = 'نام کمپین الزامی است';
        }

        if (!$campaign['template_id'] && empty($campaign['message_text'])) {
            $errors[] = 'باید الگو انتخاب کنید یا متن پیام را وارد کنید';
        }

        // Validate scheduled_at if status is scheduled
        if ($campaign['status'] === 'scheduled') {
            if (empty($campaign['scheduled_at'])) {
                $errors[] = 'برای کمپین برنامه‌ریزی‌شده، زمان ارسال الزامی است';
            } elseif (strtotime($campaign['scheduled_at']) < time()) {
                $errors[] = 'زمان ارسال نمی‌تواند در گذشته باشد';
            }
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                if ($campaign_id) {
                    // UPDATE
                    $sql = "UPDATE sms_campaigns SET
                            name = ?, template_id = ?, message_text = ?,
                            recipient_type = ?, recipient_filters = ?,
                            scheduled_at = ?, status = ?, updated_at = NOW()
                            WHERE id = ?";

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $campaign['name'], $campaign['template_id'], $campaign['message_text'],
                        $campaign['recipient_type'], $campaign['recipient_filters'],
                        $campaign['scheduled_at'], $campaign['status'], $campaign_id
                    ]);

                    logActivity($_SESSION['user_id'], 'update_sms_campaign', 'sms_campaigns', $campaign_id);
                    $message = 'کمپین با موفقیت بروزرسانی شد';

                } else {
                    // INSERT
                    $sql = "INSERT INTO sms_campaigns (
                            name, template_id, message_text, recipient_type,
                            recipient_filters, scheduled_at, status, created_by
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $campaign['name'], $campaign['template_id'], $campaign['message_text'],
                        $campaign['recipient_type'], $campaign['recipient_filters'],
                        $campaign['scheduled_at'], $campaign['status'], $_SESSION['user_id']
                    ]);

                    $campaign_id = $pdo->lastInsertId();
                    logActivity($_SESSION['user_id'], 'create_sms_campaign', 'sms_campaigns', $campaign_id);
                    $message = 'کمپین جدید با موفقیت ایجاد شد';
                }

                $pdo->commit();
                setMessage($message, 'success');
                header("Location: campaign_view.php?id=$campaign_id");
                exit();

            } catch (PDOException $e) {
                $pdo->rollBack();
                setMessage('خطا در ذخیره کمپین: ' . $e->getMessage(), 'error');
                error_log("Campaign save error: " . $e->getMessage());
            }
        }
    } else {
        $errors[] = 'توکن امنیتی نامعتبر است';
    }
}

// ─── FETCH DROPDOWN DATA ────────────────────────────────────────────────────
try {
    $templates = $templateService->getTemplates(['status' => 'active']);
    $customers = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) as name, company_name 
                               FROM customers WHERE status = 'active' LIMIT 100")->fetchAll();
    $leads = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) as name, title 
                          FROM leads WHERE status != 'lost' LIMIT 100")->fetchAll();
} catch (PDOException $e) {
    $templates = [];
    $customers = [];
    $leads = [];
    error_log("Dropdown data error: " . $e->getMessage());
}

$csrf_token = generateCSRFToken();
include __DIR__ . '/../private/header.php';
?>

<!-- ─── STYLES (مطابق task_form.php) ────────────────────────────────────── -->
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

    /* Select Dropdown Arrow Override */
    select.form-select {
        appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: left 0.75rem center;
        background-size: 16px 12px;
        padding-left: 2.5rem;
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

    /* Message Preview */
    .message-preview {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: var(--radius-elem);
        padding: 20px;
        color: white;
        min-height: 150px;
        position: relative;
    }

    .message-preview::before {
        content: 'پیش‌نمایش پیام';
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 0.75rem;
        opacity: 0.8;
    }

    .preview-text {
        margin-top: 30px;
        font-size: 0.95rem;
        line-height: 1.8;
        white-space: pre-wrap;
    }

    .char-counter {
        margin-top: 10px;
        font-size: 0.85rem;
        opacity: 0.9;
    }

    /* Cost Estimate */
    .cost-estimate {
        background: var(--brand-soft);
        border-radius: var(--radius-elem);
        padding: 16px;
        margin-top: 16px;
    }

    .cost-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        font-size: 0.9rem;
    }

    .cost-total {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--brand);
        padding-top: 12px;
        border-top: 2px solid var(--brand);
    }

    /* Recipient Selector */
    .recipient-box {
        max-height: 300px;
        overflow-y: auto;
        background: var(--bg-light);
        border-radius: var(--radius-elem);
        padding: 16px;
    }

    .recipient-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        background: white;
        border-radius: 8px;
        margin-bottom: 8px;
        border: 1px solid var(--gray-border);
        transition: 0.2s;
    }

    .recipient-item:hover {
        border-color: var(--brand);
        background: var(--brand-soft);
    }

    .recipient-item input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    @media (max-width: 900px) {
        .form-layout { grid-template-columns: 1fr; }
    }
</style>

<!-- ─── PAGE HEADER ───────────────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="campaigns.php" class="btn-outline" style="padding: 10px;">
            <?php echo $icons['arrow-right']; ?>
        </a>
        <div>
            <h4 class="mb-0 fw-bold" style="color: var(--dark);"><?php echo $page_title; ?></h4>
            <div class="text-muted small">مدیریت کمپین‌های پیامکی / فرم ثبت اطلاعات</div>
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

<form method="POST" id="campaignForm">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

    <div class="form-layout">

        <!-- ─── LEFT COLUMN (MAIN INFO) ────────────────────────────────────── -->
        <div class="main-column">

            <!-- اطلاعات اصلی -->
            <div class="app-card">
                <div class="section-title"><?php echo $icons['type']; ?> اطلاعات کمپین</div>

                <div class="form-group">
                    <label for="name" class="form-label">نام کمپین <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-input"
                           value="<?php echo htmlspecialchars($campaign['name']); ?>"
                           placeholder="مثال: کمپین تبریک عید نوروز 1404" required autofocus>
                </div>

                <div class="form-group">
                    <label for="template_id" class="form-label"><?php echo $icons['file-text']; ?> انتخاب الگو (اختیاری)</label>
                    <select name="template_id" id="template_id" class="form-select" onchange="loadTemplate()">
                        <option value="">بدون الگو - پیام دستی</option>
                        <?php foreach ($templates as $t): ?>
                        <option value="<?php echo $t['id']; ?>"
                                data-body="<?php echo htmlspecialchars($t['body']); ?>"
                                <?php echo $campaign['template_id'] == $t['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">می‌توانید از الگوهای از پیش تعریف‌شده استفاده کنید یا پیام دستی بنویسید</small>
                </div>

                <div class="form-group">
                    <label for="message_text" class="form-label">متن پیام <span class="text-danger">*</span></label>
                    <textarea name="message_text" id="message_text" class="form-textarea"
                              placeholder="متن پیام خود را اینجا بنویسید... (حداکثر 612 کاراکتر)"
                              maxlength="612" oninput="updatePreview()" required><?php echo htmlspecialchars($campaign['message_text']); ?></textarea>
                    <small class="text-muted">
                        پارامترهای قابل استفاده: {name}, {company}, {code}
                    </small>
                </div>
            </div>

            <!-- انتخاب مخاطبان -->
            <div class="app-card">
                <div class="section-title"><?php echo $icons['users']; ?> انتخاب مخاطبان</div>

                <div class="form-group">
                    <label for="recipient_type" class="form-label">نوع مخاطب</label>
                    <select name="recipient_type" id="recipient_type" class="form-select" onchange="toggleRecipientSelector()">
                        <option value="all_customers" <?php echo $campaign['recipient_type'] === 'all_customers' ? 'selected' : ''; ?>>تمام مشتریان فعال</option>
                        <option value="customers" <?php echo $campaign['recipient_type'] === 'customers' ? 'selected' : ''; ?>>انتخاب از مشتریان</option>
                        <option value="all_leads" <?php echo $campaign['recipient_type'] === 'all_leads' ? 'selected' : ''; ?>>تمام لیدهای فعال</option>
                        <option value="leads" <?php echo $campaign['recipient_type'] === 'leads' ? 'selected' : ''; ?>>انتخاب از لیدها</option>
                        <option value="manual" <?php echo $campaign['recipient_type'] === 'manual' ? 'selected' : ''; ?>>ورود دستی شماره‌ها</option>
                        <option value="csv" <?php echo $campaign['recipient_type'] === 'csv' ? 'selected' : ''; ?>>آپلود فایل CSV</option>
                    </select>
                </div>

                <!-- انتخاب مشتریان -->
                <div class="form-group" id="group_customers" style="display: none;">
                    <label class="form-label">انتخاب مشتریان</label>
                    <div class="recipient-box">
                        <?php foreach ($customers as $c): ?>
                        <label class="recipient-item">
                            <input type="checkbox" name="selected_customers[]" value="<?php echo $c['id']; ?>">
                            <span><?php echo htmlspecialchars($c['name'] . ($c['company_name'] ? ' - ' . $c['company_name'] : '')); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- انتخاب لیدها -->
                <div class="form-group" id="group_leads" style="display: none;">
                    <label class="form-label">انتخاب لیدها</label>
                    <div class="recipient-box">
                        <?php foreach ($leads as $l): ?>
                        <label class="recipient-item">
                            <input type="checkbox" name="selected_leads[]" value="<?php echo $l['id']; ?>">
                            <span><?php echo htmlspecialchars($l['name'] . ' - ' . $l['title']); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- ورود دستی -->
                <div class="form-group" id="group_manual" style="display: none;">
                    <label for="manual_phones" class="form-label">شماره‌های تلفن (هر شماره در یک خط)</label>
                    <textarea name="manual_phones" id="manual_phones" class="form-textarea"
                              placeholder="09123456789&#10;09121234567&#10;..."><?php echo htmlspecialchars($campaign['recipient_filters'] ?? ''); ?></textarea>
                    <small class="text-muted">هر شماره را در یک خط جدید وارد کنید</small>
                </div>

                <!-- آپلود CSV -->
                <div class="form-group" id="group_csv" style="display: none;">
                    <label for="csv_file" class="form-label">فایل CSV</label>
                    <input type="file" name="csv_file" id="csv_file" class="form-input" accept=".csv">
                    <small class="text-muted">فایل باید شامل ستون phone باشد</small>
                </div>
            </div>

        </div>

        <!-- ─── RIGHT COLUMN (SETTINGS & PREVIEW) ──────────────────────────── -->
        <div class="sidebar-column">

            <!-- زمان‌بندی -->
            <div class="app-card">
                <div class="section-title"><?php echo $icons['calendar']; ?> زمان‌بندی ارسال</div>

                <div class="form-group">
                    <label for="status" class="form-label">وضعیت کمپین</label>
                    <select name="status" id="status" class="form-select" onchange="toggleScheduleField()">
                        <option value="draft" <?php echo $campaign['status'] === 'draft' ? 'selected' : ''; ?>>پیش‌نویس</option>
                        <option value="scheduled" <?php echo $campaign['status'] === 'scheduled' ? 'selected' : ''; ?>>برنامه‌ریزی‌شده</option>
                        <option value="active" <?php echo $campaign['status'] === 'active' ? 'selected' : ''; ?>>فوری (ارسال بلافاصله)</option>
                    </select>
                </div>

                <div class="form-group" id="schedule_field" style="display: none;">
                    <label for="scheduled_at" class="form-label">زمان ارسال</label>
                    <input type="datetime-local" name="scheduled_at" id="scheduled_at" class="form-input"
                           value="<?php echo $campaign['scheduled_at'] ? date('Y-m-d\TH:i', strtotime($campaign['scheduled_at'])) : ''; ?>">
                </div>
            </div>

            <!-- پیش‌نمایش پیام -->
            <div class="app-card">
                <div class="section-title">پیش‌نمایش</div>
                <div class="message-preview">
                    <div class="preview-text" id="previewText">
                        متن پیام شما اینجا نمایش داده می‌شود...
                    </div>
                    <div class="char-counter">
                        <span id="charCount">0</span> / 612 کاراکتر
                    </div>
                </div>
            </div>

            <!-- برآورد هزینه -->
            <div class="app-card">
                <div class="section-title"><?php echo $icons['dollar']; ?> برآورد هزینه</div>
                <div class="cost-estimate">
                    <div class="cost-row">
                        <span>تعداد مخاطبان:</span>
                        <strong id="recipientCount">0</strong>
                    </div>
                    <div class="cost-row">
                        <span>تعداد پیام (70 کاراکتری):</span>
                        <strong id="messageCount">0</strong>
                    </div>
                    <div class="cost-row">
                        <span>هزینه هر پیام:</span>
                        <strong>500 تومان</strong>
                    </div>
                    <div class="cost-row cost-total">
                        <span>هزینه کل:</span>
                        <span id="totalCost">0 تومان</span>
                    </div>
                </div>
            </div>

            <!-- دکمه‌های عملیات -->
            <div class="d-flex flex-column gap-3">
                <button type="submit" class="btn-brand w-100">
                    <?php echo $icons['save']; ?> ذخیره کمپین
                </button>
                <a href="campaigns.php" class="btn-outline w-100">
                    انصراف
                </a>
            </div>

        </div>
    </div>
</form>

<script>
// ─── TEMPLATE LOADER ────────────────────────────────────────────────────────
function loadTemplate() {
    const select = document.getElementById('template_id');
    const option = select.options[select.selectedIndex];
    const body = option.getAttribute('data-body');
    
    if (body) {
        document.getElementById('message_text').value = body;
        updatePreview();
    }
}

// ─── PREVIEW UPDATER ────────────────────────────────────────────────────────
function updatePreview() {
    const text = document.getElementById('message_text').value;
    const preview = document.getElementById('previewText');
    const charCount = document.getElementById('charCount');
    
    preview.textContent = text || 'متن پیام شما اینجا نمایش داده می‌شود...';
    charCount.textContent = text.length;
    
    updateCostEstimate();
}

// ─── COST ESTIMATOR ─────────────────────────────────────────────────────────
function updateCostEstimate() {
    const text = document.getElementById('message_text').value;
    const recipientCount = getRecipientCount();
    
    // هر 70 کاراکتر = 1 پیام
    const messageCount = Math.ceil(text.length / 70) || 1;
    const costPerMessage = 500;
    const totalCost = recipientCount * messageCount * costPerMessage;
    
    document.getElementById('recipientCount').textContent = recipientCount;
    document.getElementById('messageCount').textContent = messageCount;
    document.getElementById('totalCost').textContent = totalCost.toLocaleString('fa-IR') + ' تومان';
}

function getRecipientCount() {
    const type = document.getElementById('recipient_type').value;
    
    if (type === 'all_customers') {
        return <?php echo count($customers); ?>;
    } else if (type === 'all_leads') {
        return <?php echo count($leads); ?>;
    } else if (type === 'customers') {
        return document.querySelectorAll('input[name="selected_customers[]"]:checked').length;
    } else if (type === 'leads') {
        return document.querySelectorAll('input[name="selected_leads[]"]:checked').length;
    } else if (type === 'manual') {
        const phones = document.getElementById('manual_phones').value.split('\n').filter(p => p.trim());
        return phones.length;
    }
    
    return 0;
}

// ─── RECIPIENT SELECTOR TOGGLE ──────────────────────────────────────────────
function toggleRecipientSelector() {
    const type = document.getElementById('recipient_type').value;
    
    // Hide all
    document.getElementById('group_customers').style.display = 'none';
    document.getElementById('group_leads').style.display = 'none';
    document.getElementById('group_manual').style.display = 'none';
    document.getElementById('group_csv').style.display = 'none';
    
    // Show selected
    if (type === 'customers') {
        document.getElementById('group_customers').style.display = 'block';
    } else if (type === 'leads') {
        document.getElementById('group_leads').style.display = 'block';
    } else if (type === 'manual') {
        document.getElementById('group_manual').style.display = 'block';
    } else if (type === 'csv') {
        document.getElementById('group_csv').style.display = 'block';
    }
    
    updateCostEstimate();
}

// ─── SCHEDULE FIELD TOGGLE ──────────────────────────────────────────────────
function toggleScheduleField() {
    const status = document.getElementById('status').value;
    const scheduleField = document.getElementById('schedule_field');
    
    scheduleField.style.display = (status === 'scheduled') ? 'block' : 'none';
}

// ─── INITIALIZATION ─────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    updatePreview();
    toggleRecipientSelector();
    toggleScheduleField();
    
    // Add event listeners for cost update
    document.querySelectorAll('input[type="checkbox"]').forEach(cb => {
        cb.addEventListener('change', updateCostEstimate);
    });
    
    document.getElementById('manual_phones')?.addEventListener('input', updateCostEstimate);
});
</script>

<?php include __DIR__ . '/../private/footer.php'; ?>
