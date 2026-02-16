<?php
// مسیر پیشنهادی: /public/sms/campaign_form.php

require_once __DIR__ . '/../../private/db.php';
require_once __DIR__ . '/../../private/sms/SmsTemplateService.php';
require_once __DIR__ . '/../../private/sms/SmsRecipientResolver.php';

$templateService = new SmsTemplateService($db);
$resolver = new SmsRecipientResolver($db);

// ۱. پردازش فرم هنگام ارسال (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $templateId = (int)$_POST['template_id'];
    $audienceType = $_POST['audience_type']; // manual, all_customers, segment
    
    try {
        $db->beginTransaction();

        // ۲. ایجاد رکورد اصلی کمپین
        $stmt = $db->prepare("INSERT INTO sms_campaigns (name, template_id, audience_type, created_by, status) VALUES (?, ?, ?, ?, 'draft')");
        $stmt->execute([$name, $templateId, $audienceType, $_SESSION['user_id']]);
        $campaignId = $db->lastInsertId();

        // ۳. استخراج مخاطبان بر اساس نوع انتخاب شده
        $recipients = $resolver->resolve($audienceType, $_POST['filters'] ?? null);

        // ۴. درج دسته‌جمعی مخاطبان در جدول گیرندگان
        if (!empty($recipients)) {
            $sql = "INSERT INTO sms_campaign_recipients (campaign_id, customer_id, mobile, params, status) VALUES (?, ?, ?, ?, 'pending')";
            $insertStmt = $db->prepare($sql);
            foreach ($recipients as $r) {
                $insertStmt->execute([$campaignId, $r['customer_id'], $r['mobile'], $r['params']]);
            }
            
            // ۵. به‌روزرسانی تعداد کل مخاطبان در جدول اصلی کمپین
            $db->prepare("UPDATE sms_campaigns SET total_recipients = ? WHERE id = ?")
               ->execute([count($recipients), $campaignId]);
        }

        $db->commit();
        header("Location: campaigns.php?success=1");
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        $error = "خطا در ایجاد کمپین: " . $e->getMessage();
    }
}

$templates = $templateService->getActiveTemplates();
?>

<div class="card">
    <div class="card-header"><h5>ایجاد کمپین پیامکی جدید</h5></div>
    <div class="card-body">
        <form method="post">
            <div class="mb-3">
                <label>نام کمپین</label>
                <input type="text" name="name" class="form-control" required placeholder="مثلا: تبریک عید نوروز">
            </div>

            <div class="mb-3">
                <label>انتخاب الگو (Template)</label>
                <select name="template_id" class="form-select" required>
                    <?php foreach ($templates as $tpl): ?>
                        <option value="<?= $tpl['id'] ?>"><?= htmlspecialchars($tpl['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label>جامعه مخاطبان</label>
                <select name="audience_type" class="form-select" onchange="toggleFilters(this.value)">
                    <option value="all_customers">تمامی مشتریان فعال</option>
                    <option value="segment">بر اساس فیلتر (سگمنت)</option>
                    <option value="manual">انتخاب دستی (از لیست مشتریان)</option>
                </select>
            </div>

            <div id="segment-filters" class="d-none border p-3 mb-3 bg-light">
                <h6>فیلترهای سگمنت</h6>
                <div class="row">
                    <div class="col-md-6">
                        <label>شهر</label>
                        <input type="text" name="filters[city]" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label>صنعت</label>
                        <input type="text" name="filters[industry]" class="form-control">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">ایجاد کمپین و استخراج مخاطبان</button>
        </form>
    </div>
</div>

<script>
function toggleFilters(val) {
    document.getElementById('segment-filters').classList.toggle('d-none', val !== 'segment');
}
</script>