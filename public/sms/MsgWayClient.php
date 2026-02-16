<?php
// مسیر پیشنهادی: /private/sms/MsgWayClient.php

class MsgWayClient {
    private $apiKey;
    private $baseUrl = "https://api.msgway.com";

    /**
     * در این بخش بهتر است apiKey از فایل تنظیمات اصلی سامانه یا دیتابیس خوانده شود
     */
    public function __construct($apiKey = null) {
        $this->apiKey = $apiKey;
    }

    /**
     * متد عمومی برای ارسال درخواست به API
     */
    private function request($endpoint, $params = []) {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "{$this->baseUrl}/{$endpoint}",
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_HTTPHEADER => [
                'apiKey: ' . $this->apiKey,
                'Content-Type: application/json'
            ],
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            return ['status' => 'error', 'error' => ['message' => $error]];
        }

        return json_decode($response, true);
    }

    /**
     * ارسال پیام (SMS/Messenger)
     * مطابق با مستندات راه پیام
     */
    public function send($mobile, $templateID, $params = [], $method = 'sms') {
        $data = [
            "mobile" => $mobile,
            "method" => $method,
            "templateID" => (int)$templateID,
            "params" => $params
        ];
        return $this->request('send', $data);
    }

    /**
     * دریافت موجودی حساب
     */
    public function getBalance() {
        return $this->request('balance/get');
    }

    /**
     * دریافت جزئیات یک الگو
     */
    public function getTemplate($templateID) {
        return $this->request('template/get', ["templateID" => (int)$templateID]);
    }

    /**
     * بررسی وضعیت پیام ارسالی
     */
    public function getStatus($otpReferenceID) {
        return $this->request('status', ["OTPReferenceID" => $otpReferenceID]);
    }
}