<?php
require __DIR__ . '/send_otp.php';
$mobile = isset($_GET['mobile']) ? (string)$_GET['mobile'] : '09XXXXXXXXX';
$code   = isset($_GET['code'])   ? (string)$_GET['code']   : null;
header('Content-Type: application/json; charset=utf-8');
echo json_encode(msgway_send_otp($mobile, $code), JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
