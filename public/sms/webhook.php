<?php
// مسیر پیشنهادی: /public/sms/webhook.php

// ۱. اتصال به دیتابیس و لود کردن کلاس‌های مورد نیاز
require_once __DIR__ . '/../../private/db.php'; 
require_once __DIR__ . '/../../private/sms/SmsLogger.php';

// ۲. دریافت داده‌های ارسالی از راه پیام (بصورت JSON)
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data || !isset($data['OTPReferenceID'])) {
    http_response_code(400);
    exit("Invalid Data");
}

$refID = $data['OTPReferenceID'];
$status = $data['status']; // مقادیری مثل delivered, failed, sent

try {
    $logger = new SmsLogger($db);

    // ۳. به‌روزرسانی جدول لاگ‌های کلی
    $logger->updateStatus($refID, $status);

    // ۴. به‌روزرسانی جدول گیرندگان کمپین (اگر پیام مربوط به یک کمپین باشد)
    $query = "UPDATE sms_campaign_recipients 
              SET status = ?, delivered_at = IF(? = 'delivered', NOW(), NULL) 
              WHERE msgway_message_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$status, $status, $refID]);

    // ۵. پاسخ موفقیت به راه پیام برای جلوگیری از ارسال مجدد وب‌هوک
    http_response_code(200);
    echo json_encode(["status" => "success"]);

} catch (Exception $e) {
    // ثبت خطا در فایل log سرور در صورت بروز مشکل
    error_log("Webhook Error: " . $e->getMessage());
    http_response_code(500);
}