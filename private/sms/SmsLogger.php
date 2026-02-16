<?php
// مسیر پیشنهادی: /private/sms/SmsLogger.php

class SmsLogger {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * ثبت لاگ ارسال پیامک در جدول sms_logs
     */
    public function log($data) {
        $query = "INSERT INTO sms_logs (
            mobile, customer_id, template_id, campaign_id, 
            msgway_message_id, send_method, status, cost, api_response, sent_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $data['mobile'],
            $data['customer_id'] ?? null, // فیلد جدید اضافه شده در SQL
            $data['template_id'] ?? null,
            $data['campaign_id'] ?? null,
            $data['msgway_message_id'] ?? null,
            $data['send_method'] ?? 'sms',
            $data['status'] ?? 'pending',
            $data['cost'] ?? 0.00, // فیلد جدید برای مدیریت مالی
            json_encode($data['api_response'] ?? [])
        ]);

        $this->updateDailyStats($data['status'], $data['cost']);
        return $this->db->lastInsertId();
    }

    /**
     * به‌روزرسانی آمار روزانه در جدول sms_daily_stats
     */
    private function updateDailyStats($status, $cost) {
        $date = date('Y-m-d');
        $query = "INSERT INTO sms_daily_stats (stat_date, total_sent, total_delivered, total_failed, total_cost)
                  VALUES (?, 1, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE 
                  total_sent = total_sent + 1,
                  total_cost = total_cost + VALUES(total_cost),
                  total_delivered = total_delivered + VALUES(total_delivered),
                  total_failed = total_failed + VALUES(total_failed)";

        $delivered = ($status === 'delivered') ? 1 : 0;
        $failed = ($status === 'failed') ? 1 : 0;

        $stmt = $this->db->prepare($query);
        $stmt->execute([$date, $delivered, $failed, $cost]);
    }

    /**
     * به‌روزرسانی وضعیت پیام (بر اساس وب‌هوک یا استعلام وضعیت)
     */
    public function updateStatus($msgway_message_id, $newStatus) {
        $query = "UPDATE sms_logs SET status = ?, delivered_at = IF(? = 'delivered', NOW(), delivered_at) 
                  WHERE msgway_message_id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$newStatus, $newStatus, $msgway_message_id]);
    }
}