<?php
function msgway_normalize_mobile($mobile) {
    $m = preg_replace('/\s+/', '', (string)$mobile);
    if ($m === '') return $m;
    if ($m[0] !== '+') {
        if (preg_match('/^0\d{10}$/', $m)) return '+98' . substr($m, 1);
        if (preg_match('/^9\d{9}$/',  $m)) return '+98' . $m;
    }
    return $m;
}
function msgway_send_otp($mobile, $code = null) {
    $cfg = require __DIR__ . '/config.php';
    $apiKey     = trim((string)$cfg['apiKey'] ?? '');
    $templateID = (int)($cfg['templateID'] ?? 0);
    $lineNumber = trim((string)($cfg['lineNumber'] ?? ''));
    if ($apiKey === '' || $templateID <= 0) {
        return ['code'=>-1, 'message'=>'Missing apiKey or templateID in config.php'];
    }
    $mobile = msgway_normalize_mobile($mobile);
    if (!preg_match('/^\+?\d{10,15}$/', $mobile)) {
        return ['code'=>-1, 'message'=>'Invalid mobile format'];
    }
    if ($code === null) {
        $code = (string)random_int(100000, 999999);
    }
    $params = ["mobile"=>$mobile, "templateID"=>$templateID, "code"=>$code];
    if ($lineNumber !== '') $params["lineNumber"] = $lineNumber;
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.msgway.com/send',
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_POSTFIELDS => json_encode($params, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => ['apiKey: ' . $apiKey, 'Content-Type: application/json', 'Accept: application/json'],
    ]);
    $response = curl_exec($curl);
    if ($response === false) {
        $err = curl_error($curl); curl_close($curl);
        return ['code'=>-1, 'message'=>'cURL error: '.$err];
    }
    curl_close($curl);
    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        return ['code'=>-1, 'message'=>'Invalid JSON from MSGWay', 'raw'=>$response];
    }
    return $decoded;
}
