<?php
/**
 * Handles sending OTP via MSGway SMS service.
 */

function sendOtpSms(PDO $pdo, string $mobile, string $code): array
{
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('msgway_api_key', 'msgway_template_code', 'msgway_lineNumber')");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $e) {
        error_log("SMS Service DB Error: " . $e->getMessage());
        return ['success' => false, 'message' => "Server error: Cannot read settings."];
    }

    $apiKey = trim($settings['msgway_api_key'] ?? '');
    $templateId = trim($settings['msgway_template_code'] ?? '');
    $lineNumber = trim($settings['msgway_lineNumber'] ?? '');

    if (empty($apiKey) || empty($templateId)) {
        return ['success' => false, 'message' => "Configuration error: API key or template ID is not set."];
    }

    // Normalize mobile number to +98 format
    $formatted_mobile = '+98' . substr($mobile, 1);
    
    $params = [
        "mobile"     => $formatted_mobile,
        "templateID" => (int)$templateId,
        "code"       => $code
    ];

    if (!empty($lineNumber)) {
        $params['lineNumber'] = $lineNumber;
    }

    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => 'https://api.msgway.com/send',
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POSTFIELDS => json_encode($params), 
      CURLOPT_HTTPHEADER => [
          'apiKey: ' . $apiKey,
          'Content-Type: application/json'
      ],
      CURLOPT_TIMEOUT => 20,
      CURLOPT_CONNECTTIMEOUT => 10
    ]);

    $response_body = curl_exec($curl);
    $err = curl_error($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($err) {
        error_log("cURL Error for MSGway: " . $err);
        return ['success' => false, 'message' => "Connection error with SMS provider."];
    } 
    
    $response_data = json_decode($response_body, true);

    if ($http_code == 200 && isset($response_data['code']) && $response_data['code'] == 200) {
        return ['success' => true, 'message' => "SMS sent successfully."];
    }
    
    error_log("MSGway API Error: " . $response_body);
    $error_message = $response_data['message'] ?? 'Invalid response from SMS server.';
    return ['success' => false, 'message' => "SMS Provider Error: " . $error_message];
}
