<?php
/**
 * GapGPT API Client
 * کلاس اتصال به سرویس هوش مصنوعی GapGPT
 * 
 * @version 1.0.0
 * @author Ready Studio
 * @link https://gapgpt.app/platform-v2/pricing
 */

class GapGPTClient
{
    private $api_key;
    private $model;
    private $temperature;
    private $max_tokens;
    private $base_url = 'https://api.gapgpt.app/v1';
    private $timeout = 30;

    /**
     * سازنده کلاس
     */
    public function __construct($settings)
    {
        $this->api_key = $settings['api_key'] ?? '';
        $this->model = $settings['model'] ?? 'gapgpt-deepseek-v3';
        $this->temperature = (float)($settings['temperature'] ?? 0.7);
        $this->max_tokens = (int)($settings['max_tokens'] ?? 2000);
    }

    /**
     * ارسال پیام به GapGPT و دریافت پاسخ
     */
    public function sendMessage($prompt, $system_message = null)
    {
        if (empty($this->api_key)) {
            return [
                'success' => false,
                'error' => 'API Key تنظیم نشده است'
            ];
        }

        try {
            // ساخت پیام‌های ارسالی
            $messages = [];

            // سیستم پرامپت (اختیاری)
            if ($system_message) {
                $messages[] = [
                    'role' => 'system',
                    'content' => $system_message
                ];
            }

            // پیام اصلی کاربر
            $messages[] = [
                'role' => 'user',
                'content' => $prompt
            ];

            // داده‌های درخواست
            $request_data = [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => $this->temperature,
                'max_tokens' => $this->max_tokens,
                'stream' => false
            ];

            // ارسال درخواست
            $response = $this->makeRequest('/chat/completions', $request_data);

            if ($response['success']) {
                // استخراج پاسخ از response
                $ai_message = $response['data']['choices'][0]['message']['content'] ?? '';

                if (empty($ai_message)) {
                    return [
                        'success' => false,
                        'error' => 'پاسخ دریافتی از API خالی است'
                    ];
                }

                return [
                    'success' => true,
                    'message' => $ai_message,
                    'usage' => $response['data']['usage'] ?? [],
                    'model' => $response['data']['model'] ?? $this->model
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response['error']
                ];
            }

        } catch (Exception $e) {
            error_log("GapGPT API Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'خطا در ارتباط با سرویس هوش مصنوعی'
            ];
        }
    }

    /**
     * ارسال درخواست HTTP به API
     */
    private function makeRequest($endpoint, $data)
    {
        $url = $this->base_url . $endpoint;

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->api_key,
            'Accept: application/json'
        ];

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        $curl_errno = curl_errno($ch);

        curl_close($ch);

        // بررسی خطاهای cURL
        if ($curl_errno) {
            error_log("cURL Error: $curl_error");
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
            'Rate limit exceeded' => 'محدودیت تعداد درخواست - لطفاً کمی صبر کنید',
            'Insufficient credits' => 'اعتبار کافی نیست',
            'Model not found' => 'مدل هوش مصنوعی یافت نشد',
            'Invalid request' => 'درخواست نامعتبر',
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
     */
    public function testConnection()
    {
        if (empty($this->api_key)) {
            return [
                'success' => false,
                'message' => 'API Key تنظیم نشده است'
            ];
        }

        $test_message = 'سلام، این یک پیام تستی است. لطفاً فقط یک جمله کوتاه پاسخ بده.';

        $result = $this->sendMessage($test_message);

        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'اتصال به GapGPT با موفقیت برقرار شد',
                'model' => $result['model'] ?? $this->model,
                'response' => $result['message']
            ];
        } else {
            return [
                'success' => false,
                'message' => 'خطا در اتصال: ' . $result['error']
            ];
        }
    }

    /**
     * دریافت لیست مدل‌های در دسترس
     * توجه: این متد فرضی است و باید بر اساس API واقعی GapGPT تنظیم شود
     */
    public function getAvailableModels()
    {
        // مدل‌های استاندارد GapGPT
        return [
            [
                'id' => 'gapgpt-deepseek-v3',
                'name' => 'DeepSeek V3',
                'description' => 'مدل قدرتمند و سریع',
                'max_tokens' => 8000
            ],
            [
                'id' => 'gapgpt-gpt-4o',
                'name' => 'GPT-4O',
                'description' => 'مدل پیشرفته OpenAI',
                'max_tokens' => 4096
            ],
            [
                'id' => 'gapgpt-claude-3.5-sonnet',
                'name' => 'Claude 3.5 Sonnet',
                'description' => 'مدل پیشرفته Anthropic',
                'max_tokens' => 8000
            ],
            [
                'id' => 'gapgpt-gemini-2.0-flash',
                'name' => 'Gemini 2.0 Flash',
                'description' => 'مدل سریع Google',
                'max_tokens' => 8000
            ]
        ];
    }

    /**
     * محاسبه تخمینی هزینه بر اساس تعداد توکن
     */
    public function estimateCost($input_tokens, $output_tokens)
    {
        // قیمت‌های فرضی - باید از API واقعی گرفته شود
        $pricing = [
            'gapgpt-deepseek-v3' => ['input' => 0.27, 'output' => 1.1],
            'gapgpt-gpt-4o' => ['input' => 2.5, 'output' => 10],
            'gapgpt-claude-3.5-sonnet' => ['input' => 3, 'output' => 15],
            'gapgpt-gemini-2.0-flash' => ['input' => 0.075, 'output' => 0.3]
        ];

        if (!isset($pricing[$this->model])) {
            return null;
        }

        $model_pricing = $pricing[$this->model];

        // محاسبه هزینه (به تومان)
        $input_cost = ($input_tokens / 1000000) * $model_pricing['input'];
        $output_cost = ($output_tokens / 1000000) * $model_pricing['output'];

        return [
            'input_cost' => $input_cost,
            'output_cost' => $output_cost,
            'total_cost' => $input_cost + $output_cost,
            'currency' => 'تومان',
            'model' => $this->model
        ];
    }

    /**
     * ارسال چند پیام به صورت batch (اختیاری)
     */
    public function sendBatch($messages)
    {
        $results = [];

        foreach ($messages as $message) {
            $results[] = $this->sendMessage($message);
        }

        return $results;
    }

    /**
     * تنظیم تایم‌اوت درخواست
     */
    public function setTimeout($seconds)
    {
        $this->timeout = max(5, (int)$seconds);
        return $this;
    }

    /**
     * تنظیم دما (Temperature)
     */
    public function setTemperature($temperature)
    {
        $this->temperature = max(0, min(2, (float)$temperature));
        return $this;
    }

    /**
     * تنظیم حداکثر توکن
     */
    public function setMaxTokens($max_tokens)
    {
        $this->max_tokens = max(1, (int)$max_tokens);
        return $this;
    }

    /**
     * تنظیم مدل
     */
    public function setModel($model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * دریافت اطلاعات تنظیمات فعلی
     */
    public function getConfig()
    {
        return [
            'model' => $this->model,
            'temperature' => $this->temperature,
            'max_tokens' => $this->max_tokens,
            'timeout' => $this->timeout,
            'api_key_set' => !empty($this->api_key)
        ];
    }
}
