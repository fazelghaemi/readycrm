<?php
require dirname(__FILE__) . '/../vendor/autoload.php';
require_once dirname(__FILE__) . '/configs.php';

use MessageWay\Api\MessageWayAPI;

/* Send Message by Gap Messenger */
$messageWay = new MessageWayAPI(API_KEY);

try {
	$otp = $messageWay->sendViaMessenger(MOBILE, TEMPLATE_ID, 'gap');
	echo "referenceID: " . $otp['referenceID'] . PHP_EOL;
	echo "sender: " . $otp['sender'] . PHP_EOL;
} catch (Exception $e) {
	echo "Error: " . $e->getMessage();
}