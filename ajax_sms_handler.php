<?php
/**
 * ajax_sms_handler.php (v1.2)
 * Enhancements:
 * - Reads extra settings: msgway_lineNumber (sender line), msgway_mobile_format, msgway_resend_time, msgway_otp_length
 * - Includes lineNumber in /send payload when provided (common cause of HTTP 400)
 * - Uses otp length and resend time from settings
 * - Keeps debug=1 behavior for detailed server messages
 */

@session_start();
header('Content-Type: application/json; charset=utf-8');

$projectRootFiles = [
    __DIR__ . '/config/config.php',
    __DIR__ . '/config/database.php',
    __DIR__ . '/includes/functions.php',
    __DIR__ . '/includes/auth.php',
];
foreach ($projectRootFiles as $file) { if (file_exists($file)) require_once $file; }

function jres($ok, $message, $extra = []) {
    $payload = array_merge(['ok' => (bool)$ok, 'message' => (string)$message], $extra);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function normalize_mobile_ir($mobile, $format = 'auto') {
    $m = preg_replace('/\s+/', '', (string)$mobile);
    if ($m === '') return $m;
    // force desired format
    if ($format === '+98') {
        if (preg_match('/^0\d{10}$/', $m)) $m = '+98' . substr($m, 1);
        elseif (preg_match('/^9\d{9}$/', $m)) $m = '+98' . $m;
        elseif ($m[0] !== '+') $m = '+98' . ltrim($m, '0');
        return $m;
    } elseif ($format === '09') {
        if (preg_match('/^\+98\d{10}$/', $m)) $m = '0' . substr($m, 3);
        elseif (preg_match('/^9\d{9}$/', $m)) $m = '0' . $m;
        elseif ($m[0] !== '0') $m = '0' . ltrim($m, '+');
        return $m;
    }
    // auto
    if ($m[0] !== '+') {
        if (preg_match('/^0\d{10}$/', $m)) return '+98' . substr($m, 1);
        if (preg_match('/^9\d{9}$/', $m))   return '+98' . $m;
    }
    return $m;
}

function get_post($key, $default = '') {
    return isset($_POST[$key]) ? (is_string($_POST[$key]) ? trim($_POST[$key]) : $_POST[$key]) : $default;
}

function check_csrf_if_available() {
    $token = get_post('csrf_token', '');
    if (function_exists('verifyCSRFToken')) {
        if (!verifyCSRFToken($token)) jres(false, 'درخواست نامعتبر است. لطفاً صفحه را رفرش کنید.');
    } elseif (isset($_SESSION['csrf_token']) && $_SESSION['csrf_token']) {
        if (!hash_equals($_SESSION['csrf_token'], (string)$token)) jres(false, 'درخواست نامعتبر است. لطفاً صفحه را رفرش کنید.');
    }
}

function get_pdo_flexible() {
    if (class_exists('Database')) {
        try {
            $db = new Database();
            if (method_exists($db, 'getConnection')) return $db->getConnection();
            if (property_exists($db, 'pdo')) return $db->pdo;
        } catch (Throwable $e) { error_log('Database init error: ' . $e->getMessage()); }
    }
    $host = defined('DB_HOST') ? DB_HOST : getenv('DB_HOST');
    $name = defined('DB_NAME') ? DB_NAME : getenv('DB_NAME');
    $user = defined('DB_USER') ? DB_USER : getenv('DB_USER');
    $pass = defined('DB_PASS') ? DB_PASS : getenv('DB_PASS');
    $charset = 'utf8mb4';
    if ($host && $name && $user !== false && $pass !== false) {
        $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";
        $opts = [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false];
        try { return new PDO($dsn, (string)$user, (string)$pass, $opts); } catch (Throwable $e) { error_log('PDO fallback error: ' . $e->getMessage()); }
    }
    return null;
}

function db_get_settings_map($pdo) {
    $out = [
        'msgway_api_key'        => '',
        'msgway_template_code'  => '',
        'msgway_lineNumber'     => '',
        'msgway_mobile_format'  => 'auto',
        'msgway_resend_time'    => 60,
        'msgway_otp_length'     => 6,
    ];
    if (!$pdo) return $out;
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN (
            'msgway_api_key','msgway_template_code','rahpayam_api_key','rahpayam_pattern_code',
            'msgway_lineNumber','msgway_mobile_format','msgway_resend_time','msgway_otp_length'
        )");
        $arr = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $out['msgway_api_key']        = trim($arr['msgway_api_key'] ?? $arr['rahpayam_api_key'] ?? '');
        $out['msgway_template_code']  = trim($arr['msgway_template_code'] ?? $arr['rahpayam_pattern_code'] ?? '');
        $out['msgway_lineNumber']     = trim($arr['msgway_lineNumber'] ?? '');
        $out['msgway_mobile_format']  = trim($arr['msgway_mobile_format'] ?? 'auto');
        $out['msgway_resend_time']    = (int)($arr['msgway_resend_time'] ?? 60);
        $out['msgway_otp_length']     = (int)($arr['msgway_otp_length'] ?? 6);
        if ($out['msgway_otp_length'] < 4 || $out['msgway_otp_length'] > 8) $out['msgway_otp_length'] = 6;
        if ($out['msgway_resend_time'] < 15 || $out['msgway_resend_time'] > 600) $out['msgway_resend_time'] = 60;
        if (!in_array($out['msgway_mobile_format'], ['auto','+98','09'], true)) $out['msgway_mobile_format'] = 'auto';
    } catch (Throwable $e) { error_log('settings read error: ' . $e->getMessage()); }
    return $out;
}

function make_otp($len = 6) {
    $len = (int)$len;
    if ($len < 4) $len = 4; if ($len > 8) $len = 8;
    $min = (int)str_pad('1', $len, '0'); // e.g., 100000 for 6
    $max = (int)str_pad('',  $len, '9'); // e.g., 999999 for 6
    return (string)random_int($min, $max);
}

function send_otp_message($mobile_number, $otp_code, $pdo, $debug=false) {
    if (!$pdo) return ["ok"=>false,"msg"=>"خطای سرور: اتصال به دیتابیس برقرار نیست."];

    $settings  = db_get_settings_map($pdo);
    $apiKey    = $settings['msgway_api_key'];
    $templateId= $settings['msgway_template_code'];
    $line      = $settings['msgway_lineNumber'];
    $format    = $settings['msgway_mobile_format'];

    if ($apiKey === '' || $templateId === '') {
        return ["ok"=>false,"msg"=>"خطای تنظیمات: کلید API یا کد الگو خالی است."];
    }

    $formatted = normalize_mobile_ir($mobile_number, $format);
    $params = [
        "mobile"     => $formatted,
        "templateID" => (int)$templateId,
        "code"       => $otp_code,
    ];
    if ($line !== '') $params['lineNumber'] = $line;

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => 'https://api.msgway.com/send',
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_POSTFIELDS     => json_encode($params, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER     => [
            'apiKey: ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
    ]);

    $response  = curl_exec($curl);
    $curlErr   = curl_error($curl);
    $httpCode  = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($curlErr) {
        error_log("cURL Error: ".$curlErr);
        return ["ok"=>false,"msg"=>"خطای اتصال: امکان برقراری ارتباط با سرور پیامک وجود ندارد.","debug"=>($debug?["http"=>$httpCode,"raw"=>$response]:null)];
    }

    $decoded = json_decode($response, true);
    $isJson  = (json_last_error() === JSON_ERROR_NONE && is_array($decoded));

    if ($httpCode !== 200) {
        $serverMsg = $isJson ? ($decoded['message'] ?? 'خطای ناشناخته از سرور') : 'پاسخ غیر JSON از سرور';
        $extra = $debug ? ["http"=>$httpCode, "raw"=>$response, "json"=>$isJson ? $decoded : null, "sent"=>$params] : null;
        return ["ok"=>false,"msg"=>"خطا از سرور پیامک: ".$serverMsg." (HTTP ".$httpCode.")","debug"=>$extra];
    }

    if (!$isJson) {
        error_log("Invalid JSON from msgway: ".$response);
        return ["ok"=>false,"msg"=>"خطا از سرور پیامک: پاسخ سرور پیامک نامعتبر بود.","debug"=>($debug?["http"=>$httpCode,"raw"=>$response]:null)];
    }

    if (isset($decoded['code']) && (int)$decoded['code'] === 200) {
        return ["ok"=>true,"msg"=>"SUCCESS","data"=>$decoded];
    }

    $serverMsg = $decoded['message'] ?? 'پاسخ نامعتبر';
    return ["ok"=>false,"msg"=>"خطا از سرور پیامک: ".$serverMsg, "debug"=>($debug?["http"=>$httpCode,"json"=>$decoded, "sent"=>$params]:null)];
}

// -------------------------------------------------------------------------------------
// Main
// -------------------------------------------------------------------------------------
if (!isset($_SESSION['otp'])) $_SESSION['otp'] = [];
if (!isset($_SESSION['otp_rate'])) $_SESSION['otp_rate'] = ['last'=>0, 'count'=>0];

$ACTION = get_post('action', 'send_otp');

if ($ACTION === 'send_otp') {
    check_csrf_if_available();

    $mobile = get_post('mobile', '');
    if ($mobile === '') jres(false, 'شماره موبایل الزامی است.');

    $pdo   = get_pdo_flexible();
    $cfg   = db_get_settings_map($pdo);

    $normalized = normalize_mobile_ir($mobile, $cfg['msgway_mobile_format']);
    if (!preg_match('/^\+?\d{10,15}$/', $normalized)) jres(false, 'فرمت شماره موبایل معتبر نیست.');

    $now = time();
    $resend = (int)$cfg['msgway_resend_time'];
    if (!empty($_SESSION['otp_rate']['last']) && ($now - (int)$_SESSION['otp_rate']['last'] < $resend)) {
        jres(false, 'لطفاً کمی بعد دوباره تلاش کنید.', ['wait'=>$resend - ($now - (int)$_SESSION['otp_rate']['last'])]);
    }

    $code  = make_otp($cfg['msgway_otp_length']);
    $debug = (get_post('debug','')==='1' && (!function_exists('isLoggedIn') || isLoggedIn()));
    $res   = send_otp_message($normalized, $code, $pdo, $debug);

    if ($res['ok']) {
        $_SESSION['otp'][$normalized] = ['code'=>$code, 'expires'=>$now + 300, 'attempts'=>0];
        $_SESSION['otp_rate'] = ['last'=>$now, 'count'=>($_SESSION['otp_rate']['count'] ?? 0)+1];
        jres(true, 'کد تایید ارسال شد.', ['mobile'=>$normalized, 'expires_in'=>300, 'resend_in'=>$resend]);
    } else {
        $extra = [];
        if ($debug && isset($res['debug'])) $extra['debug'] = $res['debug'];
        jres(false, $res['msg'], $extra);
    }

} elseif ($ACTION === 'verify_otp') {
    check_csrf_if_available();

    $mobile = get_post('mobile', '');
    $code   = get_post('code', '');
    if ($mobile === '' || $code === '') jres(false, 'شماره موبایل و کد الزامی است.');

    $pdo = get_pdo_flexible();
    $cfg = db_get_settings_map($pdo);

    $normalized = normalize_mobile_ir($mobile, $cfg['msgway_mobile_format']);
    $item = $_SESSION['otp'][$normalized] ?? null;
    if (!$item) jres(false, 'کد یافت نشد. مجدداً درخواست کد بدهید.');
    if ($item['expires'] < time()) { unset($_SESSION['otp'][$normalized]); jres(false, 'کد منقضی شده است. مجدداً درخواست کد بدهید.'); }

    $_SESSION['otp'][$normalized]['attempts'] = (int)$item['attempts'] + 1;
    if ($_SESSION['otp'][$normalized]['attempts'] > 5) { unset($_SESSION['otp'][$normalized]); jres(false, 'تعداد تلاش بیش از حد مجاز. لطفاً دوباره کد دریافت کنید.'); }

    if (hash_equals((string)$item['code'], (string)$code)) { unset($_SESSION['otp'][$normalized]); jres(true, 'تایید موفق بود.', ['mobile'=>$normalized]); }
    jres(false, 'کد وارد شده نادرست است.');
} else {
    jres(false, 'عملیات نامعتبر.');
}
