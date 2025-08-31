<?php
require dirname(__FILE__) . '/../vendor/autoload.php';
require_once dirname(__FILE__) . '/configs.php';

use MessageWay\Api\MessageWayAPI;

/* Status OTP */
$messageWay = new MessageWayAPI(API_KEY);
try {
	$status = $messageWay->getStatus("1650364661182050044");
	echo "OTPStatus: " . $status['OTPStatus'] . PHP_EOL;
	echo "OTPVerified: " . $status['OTPVerified'] . PHP_EOL;
	echo "OTPMethod: " . $status['OTPMethod'] . PHP_EOL;
} catch (Exception $e) {
	echo "Error: " . $e->getMessage();
}