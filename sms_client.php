
<?php

/**
 * این تابع مسئول ارسال پیامک OTP با استفاده از سرویس msgway است.
 *
 * @param string $mobile_number شماره موبایل دریافت کننده
 * @param string $otp_code کد یکبار مصرف جهت ارسال
 * @return bool برمی‌گرداند true در صورت موفقیت و false در صورت شکست
 */
function send_otp_message($mobile_number, $otp_code) {
    
    // 1. اتصال به دیتابیس
    // مطمئن شوید آدرس این فایل صحیح است.
    require_once('config/database.php'); // <--- !!! 

    // 2. خواندن تنظیمات از دیتابیس
    try {
        // متغیر $pdo باید در فایل database.php تعریف شده باشد
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('msgway_api_key', 'msgway_pattern_code')");
        $settings_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $e) {
        // در یک پروژه واقعی، بهتر است خطا را در یک فایل لاگ ذخیره کنید
        // error_log("Database Error in sms_client: " . $e->getMessage());
        return false;
    }

    $apiKey = $settings_data['msgway_api_key'] ?? '';
    $patternCode = $settings_data['msgway_pattern_code'] ?? '';

    // 3. بررسی وجود تنظیمات
    // اگر کلید API یا کد الگو در دیتابیس ذخیره نشده باشد، عملیات متوقف می‌شود
    if (empty($apiKey) || empty($patternCode)) {
        // error_log("SMS settings (API Key or Pattern Code) are not configured.");
        return false;
    }

    // 4. آماده‌سازی و ارسال درخواست به msgway (بر اساس مستندات رسمی)
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api.msgway.com/send',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 10, // اضافه کردن تایم‌اوت ۱۰ ثانیه‌ای
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => json_encode([
        "template_code" => $patternCode,
        "receptor" => $mobile_number,
        "params" => [
            [
                "name" => "param1",
                "value" => $otp_code
            ]
        ]
    ]),
      CURLOPT_HTTPHEADER => array(
        'MsgWay-ApiKey: ' . $apiKey,
        'Content-Type: application/json'
      ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    // 5. تحلیل پاسخ و بازگرداندن نتیجه
    if ($err) {
      // خطا در ارتباط cURL
      // error_log("cURL Error in sms_client: " . $err);
      return false;
    } 
    
    $response_data = json_decode($response, true);
    if (isset($response_data['status']) && $response_data['status'] == 'OK') {
        return true; // پیامک با موفقیت برای ارسال در صف قرار گرفت
    }
    
    // error_log("msgway API Error: " . $response);
    return false; // شکست در ارسال پیامک
}