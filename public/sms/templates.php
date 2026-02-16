<?php
// مسیر پیشنهادی: /public/sms/templates.php

// ۱. لود کردن پیش‌نیازها و کلاس‌های ریفکتور شده
require_once __DIR__ . '/../../private/db.php';
require_once __DIR__ . '/../../private/sms/MsgWayClient.php';
require_once __DIR__ . '/../../private/sms/SmsTemplateService.php';

$client = new MsgWayClient($apiKey); // تنظیمات apiKey از فایل اصلی خوانده شود
$templateService = new SmsTemplateService($db);

// ۲. عملیات همگام‌سازی (Sync) در صورت درخواست کاربر
if (isset($_GET['sync']) && isset($_GET['id'])) {
    $remoteId = (int)$_GET['id'];
    $remoteData = $client->getTemplate($remoteId); // استفاده از کلاینت جدید

    if ($remoteData['status'] === 'success') {
        // به‌روزرسانی یا درج الگو در دیتابیس محلی
        $query = "INSERT INTO sms_templates (name, remote_template_id, content, status) 
                  VALUES (?, ?, ?, 'active') 
                  ON DUPLICATE KEY UPDATE content = VALUES(content), status = 'active'";
        $stmt = $db->prepare($query);
        $stmt->execute([$remoteData['data']['title'], $remoteId, $remoteData['data']['template']]);
        $successMsg = "الگو با موفقیت همگام‌سازی شد.";
    }
}

// ۳. دریافت لیست الگوها برای نمایش در جدول
$templates = $templateService->getActiveTemplates();
?>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h5>مدیریت الگوهای پیامک</h5>
        <button class="btn btn-primary btn-sm" onclick="showSyncModal()">افزودن/بروزرسانی الگو</button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>نام الگو</th>
                    <th>شناسه (MsgWay)</th>
                    <th>متن الگو</th>
                    <th>پارامترها (Mapping)</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($templates as $tpl): ?>
                <tr>
                    <td><?= htmlspecialchars($tpl['name']) ?></td>
                    <td><span class="badge bg-info"><?= $tpl['remote_template_id'] ?></span></td>
                    <td class="small"><?= nl2br(htmlspecialchars($tpl['content'])) ?></td>
                    <td>
                        <?php 
                        $map = json_decode($tpl['params_map'], true);
                        if ($map): foreach ($map as $p => $val): ?>
                            <small class="d-block text-muted"><?= $p ?>: <?= $val ?></small>
                        <?php endforeach; else: ?>
                            <span class="text-warning small">تعریف نشده</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="?sync=1&id=<?= $tpl['remote_template_id'] ?>" class="btn btn-light btn-sm">بروزرسانی</a>
                        <button class="btn btn-outline-primary btn-sm" onclick="editMapping(<?= $tpl['id'] ?>)">تنظیم پارامترها</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>