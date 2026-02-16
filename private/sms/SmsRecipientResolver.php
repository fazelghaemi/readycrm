<?php
// مسیر پیشنهادی: /private/sms/SmsRecipientResolver.php

class SmsRecipientResolver {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * استخراج مخاطبان بر اساس تنظیمات کمپین
     */
    public function resolve($audienceType, $filter = null) {
        switch ($audienceType) {
            case 'all_customers':
                return $this->getAllCustomers();
            case 'segment':
                return $this->getSegmentedCustomers($filter);
            case 'manual':
                return []; // در این حالت مخاطبان قبلاً در جدول recipients درج شده‌اند
            default:
                return [];
        }
    }

    /**
     * دریافت تمام مشتریان فعال
     */
    private function getAllCustomers() {
        $query = "SELECT id as customer_id, mobile, first_name, last_name 
                  FROM customers 
                  WHERE status = 'active' AND mobile IS NOT NULL AND mobile != ''";
        $stmt = $this->db->query($query);
        return $this->formatResults($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * دریافت مشتریان بر اساس فیلتر خاص (مثلاً یک صنعت خاص یا شهر خاص)
     */
    private function getSegmentedCustomers($filterJson) {
        $filters = json_decode($filterJson, true);
        $where = ["status = 'active'"];
        $params = [];

        if (!empty($filters['city'])) {
            $where[] = "city = ?";
            $params[] = $filters['city'];
        }

        if (!empty($filters['industry'])) {
            $where[] = "industry = ?";
            $params[] = $filters['industry'];
        }

        $query = "SELECT id as customer_id, mobile, first_name, last_name 
                  FROM customers 
                  WHERE " . implode(' AND ', $where);
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $this->formatResults($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * استانداردسازی خروجی برای استفاده در کمپین
     * تبدیل نام و نام خانوادگی به پارامترهای قابل استفاده در الگو
     */
    private function formatResults($results) {
        $formatted = [];
        foreach ($results as $row) {
            $formatted[] = [
                'customer_id' => $row['customer_id'],
                'mobile' => $this->sanitizeMobile($row['mobile']),
                'params' => json_encode([
                    $row['first_name'] . ' ' . $row['last_name'], // پارامتر {1}
                    date('Y/m/d') // پارامتر {2} - مثال
                ])
            ];
        }
        return $formatted;
    }

    /**
     * اصلاح فرمت شماره موبایل برای سازگاری با راه پیام
     */
    private function sanitizeMobile($mobile) {
        $mobile = preg_replace('/\D/', '', $mobile);
        if (str_starts_with($mobile, '0')) {
            $mobile = '98' . substr($mobile, 1);
        }
        if (!str_starts_with($mobile, '98')) {
            $mobile = '98' . $mobile;
        }
        return '+' . $mobile;
    }
}