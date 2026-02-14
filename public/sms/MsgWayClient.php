<?php
/**
 * MessageWay API Client
 * کلاس اتصال به سرویس پیامک MessageWay
 *
 * @version 3.6.0
 * @author ReadyCRM Team
 * @link https://console.msgway.com/
 */

class MsgWayClient
{
    // شناسه‌های ارائه‌دهنده
    const PROVIDER_MAGFA = 1;
    const PROVIDER_FARAZSMS = 2;
    const PROVIDER_KAVENEGAR = 3;
    const PROVIDER_GHASEDAK = 4;
    const PROVIDER_SMSIR = 5;

    // متدهای ارسال
    const METHOD_SMS = 'sms';
    const METHOD_IVR = 'ivr';

    private $api_key;
    private $base_url = 'https://console.msgway.com/api/v1';
    private $timeout = 30;

    /**
     * سازنده کلاس
     */
    public function __construct($api_key)
    {
        $this->api_key = $api_key;
    }

    /**
     * ارسال پیامک متنی
     *
     * @param string|array $mobile شماره موبایل یا آرایه از شماره‌ها
     * @param int $template_id شناسه الگو در MessageWay
     * @param array $params پارامترهای الگو (مانند ['name' => 'علی', 'code' => '1234'])
     * @param int|null $provider_id شناسه ارائه‌دهنده (اختیاری)
     * @return array نتیجه ارسال
     */
    public function sendSMS($mobile, $template_id, $params = [], $provider_id = null)
    {
        if (empty($this->api_key)) {
            return [
                'success' => false,
                'error' => 'API Key تنظیم نشده است'
            ];
        }

        try {
            // نرمال‌سازی شماره موبایل
            $mobiles = is_array($mobile) ? $mobile : [$mobile];
            $normalized_mobiles = array_map([$this, 'normalizeMobileForAPI'], $mobiles);

            // اعتبارسنجی شماره‌ها
            foreach ($normalized_mobiles as $mob) {
                if (!$this->isValidInternationalMobile($mob)) {
                    return [
                        'success' => false,
                        'error' => "شماره موبایل نامعتبر: $mob"
                    ];
                }
            }

            // داده‌های درخواست
            $request_data = [
                'recipients' => $normalized_mobiles,
                'template_id' => (int)$template_id,
                'parameters' => $params
            ];

            // ارائه‌دهنده اختیاری
            if ($provider_id !== null) {
                $request_data['provider_id'] = (int)$provider_id;
            }

            // ارسال درخواست
            $response = $this->makeRequest('/sms/send', $request_data);

            if ($response['success']) {
                return [
                    'success' => true,
                    'message_id' => $response['data']['message_id'] ?? null,
                    'status' => $response['data']['status'] ?? 'sent',
                    'recipients_count' => count($normalized_mobiles),
                    'cost' => $response['data']['cost'] ?? 0,
                    'provider' => $response['data']['provider'] ?? null
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response['error']
                ];
            }

        } catch (Exception $e) {
            error_log("MessageWay SMS Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'خطا در ارسال پیامک'
            ];
        }
    }

    /**
     * ارسال پیام صوتی (IVR)
     *
     * @param string|array $mobile شماره موبایل یا آرایه از شماره‌ها
     * @param int $template_id شناسه الگوی صوتی
     * @param array $params پارامترهای الگو
     * @param int|null $provider_id شناسه ارائه‌دهنده
     * @return array نتیجه ارسال
     */
    public function sendIVR($mobile, $template_id, $params = [], $provider_id = null)
    {
        if (empty($this->api_key)) {
            return [
                'success' => false,
                'error' => 'API Key تنظیم نشده است'
            ];
        }

        try {
            $mobiles = is_array($mobile) ? $mobile : [$mobile];
            $normalized_mobiles = array_map([$this, 'normalizeMobileForAPI'], $mobiles);

            foreach ($normalized_mobiles as $mob) {
                if (!$this->isValidInternationalMobile($mob)) {
                    return [
                        'success' => false,
                        'error' => "شماره موبایل نامعتبر: $mob"
                    ];
                }
            }

            $request_data = [
                'recipients' => $normalized_mobiles,
                'template_id' => (int)$template_id,
                'parameters' => $params
            ];

            if ($provider_id !== null) {
                $request_data['provider_id'] = (int)$provider_id;
            }

            $response = $this->makeRequest('/ivr/send', $request_data);

            if ($response['success']) {
                return [
                    'success' => true,
                    'message_id' => $response['data']['message_id'] ?? null,
                    'status' => $response['data']['status'] ?? 'sent',
                    'recipients_count' => count($normalized_mobiles),
                    'cost' => $response['data']['cost'] ?? 0,
                    'provider' => $response['data']['provider'] ?? null
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response['error']
                ];
            }

        } catch (Exception $e) {
            error_log("MessageWay IVR Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'خطا در ارسال پیام صوتی'
            ];
        }
    }

    /**
     * دریافت موجودی حساب
     *
     * @return array اطلاعات موجودی
     */
    public function getBalance()
    {
        if (empty($this->api_key)) {
            return [
                'success' => false,
                'error' => 'API Key تنظیم نشده است'
            ];
        }

        try {
            $response = $this->makeRequest('/account/balance', [], 'GET');

            if ($response['success']) {
                return [
                    'success' => true,
                    'balance' => $response['data']['balance'] ?? 0,
                    'currency' => $response['data']['currency'] ?? 'ریال',
                    'last_updated' => $response['data']['last_updated'] ?? date('Y-m-d H:i:s')
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response['error']
                ];
            }

        } catch (Exception $e) {
            error_log("MessageWay Balance Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'خطا در دریافت موجودی'
            ];
        }
    }

    /**
     * دریافت لیست الگوهای پیامک
     *
     * @return array لیست الگوها
     */
    public function getTemplates()
    {
        if (empty($this->api_key)) {
            return [
                'success' => false,
                'error' => 'API Key تنظیم نشده است'
            ];
        }

        try {
            $response = $this->makeRequest('/templates', [], 'GET');

            if ($response['success']) {
                return [
                    'success' => true,
                    'templates' => $response['data']['templates'] ?? [],
                    'total' => $response['data']['total'] ?? 0
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response['error']
                ];
            }

        } catch (Exception $e) {
            error_log("MessageWay Templates Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'خطا در دریافت الگوها'
            ];
        }
    }

    /**
     * بررسی وضعیت ارسال پیام
     *
     * @param string $message_id شناسه پیام
     * @return array وضعیت پیام
     */
    public function getMessageStatus($message_id)
    {
        if (empty($this->api_key)) {
            return [
                'success' => false,
                'error' => 'API Key تنظیم نشده است'
            ];
        }

        try {
            $response = $this->makeRequest("/messages/{$message_id}/status", [], 'GET');

            if ($response['success']) {
                return [
                    'success' => true,
                    'status' => $response['data']['status'] ?? 'unknown',
                    'delivered_at' => $response['data']['delivered_at'] ?? null,
                    'error_message' => $response['data']['error_message'] ?? null
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response['error']
                ];
            }

        } catch (Exception $e) {
            error_log("MessageWay Status Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'خطا در دریافت وضعیت پیام'
            ];
        }
    }

    /**
     * ارسال درخواست HTTP به API
     *
     * @param string $endpoint نقطه پایانی API
     * @param array $data داده‌های ارسالی
     * @param string $method متد HTTP (POST یا GET)
     * @return array نتیجه درخواست
     */
    private function makeRequest($endpoint, $data = [], $method = 'POST')
    {
        $url = $this->base_url . $endpoint;

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->api_key,
            'Accept: application/json'
        ];

        $ch = curl_init();

        $curl_options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ];

        if ($method === 'POST') {
            $curl_options[CURLOPT_POST] = true;
            $curl_options[CURLOPT_POSTFIELDS] = json_encode($data, JSON_UNESCAPED_UNICODE);
        } elseif ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
            $curl_options[CURLOPT_URL] = $url;
        }

        curl_setopt_array($ch, $curl_options);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        $curl_errno = curl_errno($ch);

        curl_close($ch);

        // بررسی خطاهای cURL
        if ($curl_errno) {
            error_log("MessageWay cURL Error: $curl_error");
            return [
                'success' => false,
                'error' => 'خطا در اتصال به سرور: ' . $curl_error
            ];
        }

        // بررسی HTTP Status Code
        if ($http_code !== 200) {
            $error_message = $this->parseErrorResponse($response, $http_code);
            return [
                'success' => false,
                'error' => $error_message
            ];
        }

        // تبدیل JSON به آرایه
        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'خطا در پردازش پاسخ سرور'
            ];
        }

        return [
            'success' => true,
            'data' => $decoded
        ];
    }

    /**
     * پردازش و ترجمه پیام‌های خطا
     *
     * @param string $response پاسخ خام API
     * @param int $http_code کد وضعیت HTTP
     * @return string پیام خطای فارسی
     */
    private function parseErrorResponse($response, $http_code)
    {
        $decoded = json_decode($response, true);

        if (isset($decoded['error']['message'])) {
            $error = $decoded['error']['message'];
        } elseif (isset($decoded['message'])) {
            $error = $decoded['message'];
        } else {
            $error = 'خطای ناشناخته';
        }

        // ترجمه خطاهای رایج
        $error_translations = [
            'Invalid API key' => 'کلید API نامعتبر است',
            'Insufficient balance' => 'موجودی کافی نیست',
            'Template not found' => 'الگوی پیامک یافت نشد',
            'Invalid mobile number' => 'شماره موبایل نامعتبر است',
            'Rate limit exceeded' => 'محدودیت تعداد درخواست - لطفاً کمی صبر کنید',
            'Template not approved' => 'الگوی پیامک تأیید نشده است',
            'Provider unavailable' => 'ارائه‌دهنده در دسترس نیست',
            'Invalid parameters' => 'پارامترهای نامعتبر',
            'Server error' => 'خطای سرور',
            'Timeout' => 'زمان درخواست تمام شد'
        ];

        foreach ($error_translations as $en => $fa) {
            if (stripos($error, $en) !== false) {
                return $fa;
            }
        }

        // بر اساس HTTP Code
        switch ($http_code) {
            case 400:
                return 'درخواست نامعتبر (400)';
            case 401:
                return 'کلید API نامعتبر است (401)';
            case 403:
                return 'دسترسی غیرمجاز (403)';
            case 404:
                return 'سرویس یافت نشد (404)';
            case 422:
                return 'داده‌های ارسالی نامعتبر است (422)';
            case 429:
                return 'محدودیت تعداد درخواست - لطفاً کمی صبر کنید (429)';
            case 500:
            case 502:
            case 503:
                return 'خطای سرور - لطفاً بعداً تلاش کنید (' . $http_code . ')';
            default:
                return "خطای ناشناخته (HTTP $http_code): $error";
        }
    }

    /**
     * تست اتصال به API
     *
     * @return array نتیجه تست
     */
    public function testConnection()
    {
        if (empty($this->api_key)) {
            return [
                'success' => false,
                'message' => 'API Key تنظیم نشده است'
            ];
        }

        // تست با دریافت موجودی
        $balance_result = $this->getBalance();

        if ($balance_result['success']) {
            return [
                'success' => true,
                'message' => 'اتصال به MessageWay با موفقیت برقرار شد',
                'balance' => $balance_result['balance'],
                'currency' => $balance_result['currency']
            ];
        } else {
            return [
                'success' => false,
                'message' => 'خطا در اتصال: ' . $balance_result['error']
            ];
        }
    }

    /**
     * نرمال‌سازی شماره موبایل برای ارسال به API
     * تبدیل به فرمت بین‌المللی (98xxxxxxxxxx)
     *
     * @param string $mobile شماره موبایل
     * @return string شماره نرمال‌شده
     */
    private function normalizeMobileForAPI($mobile)
    {
        // حذف کاراکترهای غیرعددی
        $mobile = preg_replace('/\D/', '', $mobile);

        // 09xxxxxxxxx -> 989xxxxxxxxx
        if (strlen($mobile) == 11 && $mobile[0] == '0') {
            return '98' . substr($mobile, 1);
        }

        // 9xxxxxxxxx -> 989xxxxxxxxx
        if (strlen($mobile) == 10 && $mobile[0] == '9') {
            return '98' . $mobile;
        }

        // قبلاً فرمت بین‌المللی است
        return $mobile;
    }

    /**
     * اعتبارسنجی شماره موبایل بین‌المللی
     *
     * @param string $mobile شماره موبایل
     * @return bool
     */
    private function isValidInternationalMobile($mobile)
    {
        // فرمت: 989xxxxxxxxx (12 رقم)
        return preg_match('/^989[0-9]{9}$/', $mobile);
    }

    /**
     * تنظیم تایم‌اوت درخواست
     *
     * @param int $seconds ثانیه
     * @return $this
     */
    public function setTimeout($seconds)
    {
        $this->timeout = max(5, (int)$seconds);
        return $this;
    }

    /**
     * دریافت اطلاعات تنظیمات فعلی
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'base_url' => $this->base_url,
            'timeout' => $this->timeout,
            'api_key_set' => !empty($this->api_key)
        ];
    }
}
