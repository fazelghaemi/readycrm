<?php
require dirname(__FILE__) . '/../vendor/autoload.php';
require_once dirname(__FILE__) . '/configs.php';

use MessageWay\Api\MessageWayAPI;

/* Send Message by SMS */
$messageWay = new MessageWayAPI(API_KEY);

try {
	$otp = $messageWay->setParams([
		'p1',
		'p2',
		'p3',
		'p4',
	])->sendViaSMS(MOBILE, TEMPLATE_ID);
	echo "referenceID: " . $otp['referenceID'] . PHP_EOL;
	echo "sender: " . $otp['sender'] . PHP_EOL;
} catch (Exception $e) {
	echo "Error: " . $e->getMessage();
}