<?php

/**
 * این تابع مسئول ارسال پیامک OTP با استفاده از سرویس msgway است.
 * [نسخه نهایی و صحیح]
 * @param string $mobile_number شماره موبایل دریافت کننده
 * @param string $otp_code کد یکبار مصرف جهت ارسال
 * @param PDO $pdo آبجکت اتصال به دیتابیس
 * @return string برمی‌گرداند "SUCCESS" در صورت موفقیت و یک پیام خطای کاربرپسند در صورت شکست
 */
function send_otp_message($mobile_number, $otp_code, $pdo)
{
    if (!$pdo) {
        return "خطای سرور: اتصال به دیتابیس برقرار نیست.";
    }

    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('msgway_api_key', 'msgway_template_code')");
        $settings_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $e) {
        error_log("Database Error in sms_client: " . $e->getMessage());
        return "خطای سرور: امکان خواندن تنظیمات وجود ندارد.";
    }

    $apiKey = trim($settings_data['msgway_api_key'] ?? '');
    $templateId = trim($settings_data['msgway_template_code'] ?? '');

    if (empty($apiKey) || empty($templateId)) {
        return "خطای تنظیمات: کلید API یا کد پترن در پنل تنظیمات وارد نشده است.";
    }

    // تبدیل خودکار شماره موبایل به فرمت +98
    $formatted_mobile = $mobile_number;
    if (substr($formatted_mobile, 0, 1) === '0') {
        $formatted_mobile = '+98' . substr($formatted_mobile, 1);
    }
    
    $curl = curl_init();

    // پارامترها مطابق با آخرین نمونه کد صحیح
    $params = [
        "mobile" => $formatted_mobile,
        "method" => "sms",
        "templateID" => (int)$templateId,
        "code" => $otp_code
    ];

    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api.msgway.com/send',
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POSTFIELDS => json_encode($params), 
      CURLOPT_HTTPHEADER => array(
        'apiKey: ' . $apiKey,
      ),
      CURLOPT_TIMEOUT => 20,
      CURLOPT_CONNECTTIMEOUT => 20
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
      error_log("cURL Error: " . $err);
      return "خطای اتصال: امکان برقراری ارتباط با سرور پیامک وجود ندارد.";
    } 
    
    $response_data = json_decode($response, true);
    if (isset($response_data['code']) && $response_data['code'] == 200) {
        return "SUCCESS";
    }
    
    error_log("msgway API Error: " . $response);
    $error_message = $response_data['message'] ?? 'پاسخ سرور پیامک نامعتبر بود. لطفاً تنظیمات و اعتبار حساب خود را بررسی کنید.';
    return "خطا از سرور پیامک: " . $error_message;
}