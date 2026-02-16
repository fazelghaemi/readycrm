<?php
// مسیر پیشنهادی: /public/sms/campaigns.php

// ۱. اتصال به دیتابیس و سرویس‌ها
require_once __DIR__ . '/../../private/db.php';

// ۲. دریافت لیست کمپین‌ها به همراه آمار از جداول sms_campaigns و sms_templates
$query = "SELECT c.*, t.name as template_name 
          FROM sms_campaigns c 
          LEFT JOIN sms_templates t ON c.template_id = t.id 
          ORDER BY c.created_at DESC";
$stmt = $db->query($query);
$campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">لیست کمپین‌های پیامکی</h5>
        <a href="campaign_form.php" class="btn btn-success btn-sm">ایجاد کمپین جدید</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>نام کمپین</th>
                    <th>الگو</th>
                    <th>وضعیت</th>
                    <th>آمار ارسال</th>
                    <th>هزینه (تومان)</th>
                    <th>تاریخ ایجاد</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($campaigns as $camp): 
                    // محاسبه درصد پیشرفت بر اساس داده‌های دیتابیس
                    $progress = ($camp['total_recipients'] > 0) 
                        ? round(($camp['sent_count'] / $camp['total_recipients']) * 100) 
                        : 0;
                    
                    $statusClass = match($camp['status']) {
                        'completed' => 'bg-success',
                        'processing' => 'bg-primary',
                        'failed' => 'bg-danger',
                        default => 'bg-secondary'
                    };
                ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($camp['name']) ?></strong>
                        <div class="progress mt-1" style="height: 5px;">
                            <div class="progress-bar <?= $statusClass ?>" style="width: <?= $progress ?>%"></div>
                        </div>
                    </td>
                    <td><small><?= htmlspecialchars($camp['template_name']) ?></small></td>
                    <td><span class="badge <?= $statusClass ?>"><?= $camp['status'] ?></span></td>
                    <td>
                        <small class="d-block text-success">تحویل: <?= $camp['delivered_count'] ?></small>
                        <small class="d-block text-danger">خطا: <?= $camp['failed_count'] ?></small>
                    </td>
                    <td><?= number_format($camp['actual_cost']) ?></td>
                    <td><small><?= $camp['created_at'] ?></small></td>
                    <td>
                        <div class="btn-group">
                            <a href="campaign_view.php?id=<?= $camp['id'] ?>" class="btn btn-outline-info btn-sm">گزارش</a>
                            <?php if($camp['status'] === 'draft'): ?>
                                <button class="btn btn-outline-success btn-sm" onclick="runCampaign(<?= $camp['id'] ?>)">ارسال</button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function runCampaign(id) {
    if(confirm('آیا از شروع ارسال این کمپین اطمینان دارید؟')) {
        // فراخوانی فایل پردازشگر (که بعدا ریفکتور می‌کنیم)
        window.location.href = 'run_campaign_worker.php?id=' + id;
    }
}
</script>