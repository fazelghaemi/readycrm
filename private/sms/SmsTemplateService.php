<?php
// مسیر پیشنهادی: /private/sms/SmsTemplateService.php

class SmsTemplateService {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * یافتن الگوی مرتبط با یک رویداد خاص (مثلاً invoice_issued)
     */
    public function getTemplateByEvent($eventKey) {
        $query = "SELECT t.* FROM sms_templates t
                  JOIN sms_event_settings e ON t.id = e.template_id
                  WHERE e.event_key = ? AND e.is_enabled = 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$eventKey]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * آماده‌سازی پارامترهای پیامک بر اساس نقشه‌ی تعریف شده
     * این متد داده‌های خام (مثل شیء فاکتور) را می‌گیرد و به آرایه‌ی عددی مورد نیاز راه پیام تبدیل می‌کند
     */
    public function prepareParams($template, $rawData) {
        if (empty($template['params_map'])) {
            return [];
        }

        $map = json_decode($template['params_map'], true);
        $finalParams = [];

        // تبدیل نام فیلدها به مقادیر واقعی بر اساس کلیدهای ارسالی
        // مثال: اگر در نقشه داریم {"1": "first_name"}، مقدار کلید first_name را در جایگاه 1 قرار می‌دهد
        foreach ($map as $position => $dataKey) {
            $finalParams[] = $rawData[$dataKey] ?? '';
        }

        return $finalParams;
    }

    /**
     * دریافت لیست تمام الگوهای فعال سیستمی
     */
    public function getActiveTemplates() {
        $stmt = $this->db->query("SELECT * FROM sms_templates WHERE status = 'active'");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}