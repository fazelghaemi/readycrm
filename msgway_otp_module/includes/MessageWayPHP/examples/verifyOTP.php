<?php
require dirname(__FILE__) . '/../vendor/autoload.php';
require_once dirname(__FILE__) . '/configs.php';

use MessageWay\Api\MessageWayAPI;

/* Verify OTP */
$messageWay = new MessageWayAPI(API_KEY);
try {
	$verify = $messageWay->verifyOTP("9288878", MOBILE);
	echo "OTPVerify: " . $verify['status'] . PHP_EOL;
} catch (Exception $e) {
	echo "Error: " . $e->getMessage();
}