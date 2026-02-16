<?php
// مسیر پیشنهادی: /private/sms/SmsCampaignService.php

class SmsCampaignService {
    private $db;
    private $client;
    private $logger;

    public function __construct($db, $client, $logger) {
        $this->db = $db;
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * اجرای یک کمپین پیامکی
     */
    public function runCampaign($campaignId) {
        // ۱. دریافت اطلاعات کمپین
        $campaign = $this->getCampaignDetails($campaignId);
        if (!$campaign) return false;

        // ۲. آپدیت وضعیت به "در حال پردازش"
        $this->updateCampaignStatus($campaignId, 'processing');

        // ۳. دریافت مخاطبان کمپین که هنوز ارسال نشده‌اند
        $recipients = $this->getPendingRecipients($campaignId);

        foreach ($recipients as $recipient) {
            // آماده‌سازی پارامترها (اگر به صورت JSON ذخیره شده باشند)
            $params = json_decode($recipient['params'], true) ?: [];

            // ۴. ارسال از طریق راه پیام
            $response = $this->client->send(
                $recipient['mobile'],
                $campaign['remote_template_id'],
                $params,
                $campaign['send_method']
            );

            // ۵. ثبت نتیجه در دیتابیس
            $this->processResponse($campaignId, $recipient['id'], $response, $recipient);
        }

        // ۶. اتمام کمپین
        $this->updateCampaignStatus($campaignId, 'completed');
        return true;
    }

    /**
     * پردازش پاسخ API برای هر مخاطب
     */
    private function processResponse($campaignId, $recipientId, $response, $recipient) {
        $status = ($response['status'] === 'success') ? 'sent' : 'failed';
        $msgId = $response['data']['OTPReferenceID'] ?? null;

        // آپدیت جدول گیرندگان کمپین (فیلدهای جدید SQL)
        $query = "UPDATE sms_campaign_recipients SET 
                  status = ?, msgway_message_id = ?, sent_at = NOW(), error_message = ? 
                  WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $status, 
            $msgId, 
            ($status === 'failed') ? json_encode($response['error']) : null,
            $recipientId
        ]);

        // ثبت در لاگ کلی سیستم
        $this->logger->log([
            'mobile' => $recipient['mobile'],
            'customer_id' => null, // اگر در آینده به جدول مشتری وصل شد
            'template_id' => null, // آی‌دی داخلی الگو
            'campaign_id' => $campaignId,
            'msgway_message_id' => $msgId,
            'status' => $status,
            'api_response' => $response
        ]);
    }

    private function getCampaignDetails($id) {
        $stmt = $this->db->prepare("SELECT c.*, t.remote_template_id FROM sms_campaigns c 
                                    JOIN sms_templates t ON c.template_id = t.id WHERE c.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getPendingRecipients($campaignId) {
        $stmt = $this->db->prepare("SELECT * FROM sms_campaign_recipients WHERE campaign_id = ? AND status = 'pending'");
        $stmt->execute([$campaignId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function updateCampaignStatus($id, $status) {
        $query = "UPDATE sms_campaigns SET status = ?, " . 
                 ($status === 'completed' ? "completed_at = NOW()" : "updated_at = NOW()") . 
                 " WHERE id = ?";
        $this->db->prepare($query)->execute([$status, $id]);
    }
}