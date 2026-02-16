<?php
// مسیر پیشنهادی: /public/sms/campaign_view.php

require_once __DIR__ . '/../../private/db.php';

$campaignId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ۱. دریافت اطلاعات کلی کمپین و نام الگو
$queryCampaign = "SELECT c.*, t.name as template_name, t.content as template_content 
                  FROM sms_campaigns c 
                  LEFT JOIN sms_templates t ON c.template_id = t.id 
                  WHERE c.id = ?";
$stmtCamp = $db->prepare($queryCampaign);
$stmtCamp->execute([$campaignId]);
$campaign = $stmtCamp->fetch(PDO::FETCH_ASSOC);

if (!$campaign) {
    die("کمپین مورد نظر یافت نشد.");
}

// ۲. دریافت لیست مخاطبان و وضعیت ارسال هر کدام
$queryRecipients = "SELECT r.*, cu.first_name, cu.last_name 
                    FROM sms_campaign_recipients r
                    LEFT JOIN customers cu ON r.customer_id = cu.id
                    WHERE r.campaign_id = ?
                    ORDER BY r.id ASC";
$stmtRec = $db->prepare($queryRecipients);
$stmtRec->execute([$campaignId]);
$recipients = $stmtRec->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header"><h5>خلاصه کمپین</h5></div>
            <div class="card-body">
                <p><strong>نام:</strong> <?= htmlspecialchars($campaign['name']) ?></p>
                <p><strong>الگو:</strong> <?= htmlspecialchars($campaign['template_name']) ?></p>
                <p><strong>وضعیت:</strong> <span class="badge bg-primary"><?= $campaign['status'] ?></span></p>
                <hr>
                <div class="d-flex justify-content-between">
                    <span>کل مخاطبان:</span> <span><?= $campaign['total_recipients'] ?></span>
                </div>
                <div class="d-flex justify-content-between text-success">
                    <span>تحویل شده:</span> <span><?= $campaign['delivered_count'] ?></span>
                </div>
                <div class="d-flex justify-content-between text-danger">
                    <span>ناموفق:</span> <span><?= $campaign['failed_count'] ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h5>لیست گیرندگان و وضعیت تحویل</h5></div>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>مخاطب</th>
                            <th>شماره موبایل</th>
                            <th>وضعیت</th>
                            <th>Reference ID</th>
                            <th>زمان تحویل</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recipients as $res): 
                            $statusBadge = match($res['status']) {
                                'delivered' => 'bg-success',
                                'sent' => 'bg-info',
                                'failed' => 'bg-danger',
                                'pending' => 'bg-secondary',
                                default => 'bg-dark'
                            };
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($res['first_name'] . ' ' . $res['last_name'] ?: 'ناشناس') ?></td>
                            <td><?= $res['mobile'] ?></td>
                            <td><span class="badge <?= $statusBadge ?>"><?= $res['status'] ?></span></td>
                            <td><small class="text-muted"><?= $res['msgway_message_id'] ?: '-' ?></small></td>
                            <td><small><?= $res['delivered_at'] ?: '-' ?></small></td>
                        </tr>
                        <?php if ($res['status'] === 'failed' && $res['error_message']): ?>
                        <tr class="table-light">
                            <td colspan="5"><small class="text-danger">علت خطا: <?= htmlspecialchars($res['error_message']) ?></small></td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>