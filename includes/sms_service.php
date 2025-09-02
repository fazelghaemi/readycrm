<?php
/**
 * Handles sending OTP via MSGway SMS service.
 * Final corrected version using GET method and +98 mobile format normalization.
 */

function sendOtpSms(PDO $pdo, string $mobile, string $code): array
{
    try {
        // Fetch all required SMS settings in one query
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'msgway_%'");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $e) {
        error_log("SMS Service DB Error: " . $e->getMessage());
        return ['success' => false, 'message' => "خطای سرور: امکان خواندن تنظیمات وجود ندارد."];
    }

    $apiKey = trim($settings['msgway_api_key'] ?? '');
    $templateId = trim($settings['msgway_template_code'] ?? '');

    if (empty($apiKey) || empty($templateId)) {
        return ['success' => false, 'message' => "خطای تنظیمات: کلید API یا کد الگو در پنل تنظیمات وارد نشده است."];
    }

    // **CRITICAL FIX**: Normalizing mobile number to the international +98 format for better reliability.
    $formatted_mobile = $mobile;
    if (substr($formatted_mobile, 0, 1) === '0') {
        $formatted_mobile = '+98' . substr($formatted_mobile, 1);
    }
    
    $baseUrl = 'https://api.msgway.com/send';
    $params = [
        'apiKey'     => $apiKey,
        'mobile'     => $formatted_mobile, // Using the normalized number
        'method'     => 'sms',
        'templateID' => $templateId,
        'code'       => $code
    ];
    // Build the final URL with query parameters
    $url = $baseUrl . '?' . http_build_query($params);

    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => 20,
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_USERAGENT => 'ReadyCRM-OTP-Client/1.1' // Setting a user agent is good practice
    ]);

    $response_body = curl_exec($curl);
    $err = curl_error($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($err) {
        error_log("cURL Error for MSGway: " . $err);
        return ['success' => false, 'message' => "خطای اتصال: امکان ارتباط با سرویس‌دهنده پیامک وجود ندارد."];
    } 
    
    $response_data = json_decode($response_body, true);

    if ($http_code == 200) {
        return ['success' => true, 'message' => "پیامک با موفقیت ارسال شد."];
    }
    
    // Log more detailed error information for easier debugging
    error_log("MSGway API Error (HTTP {$http_code}) | URL: {$url} | Response: {$response_body}");
    $error_message = $response_data['message'] ?? 'پاسخ نامعتبر از سرور پیامک. تنظیمات و اعتبار حساب خود را بررسی کنید.';
    return ['success' => false, 'message' => "خطای سرویس پیامک: " . $error_message];
}

