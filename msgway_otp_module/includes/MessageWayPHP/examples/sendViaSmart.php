<?php
require dirname(__FILE__) . '/../vendor/autoload.php';
require_once dirname(__FILE__) . '/configs.php';

use MessageWay\Api\MessageWayAPI;

/* Send Smart Message */
$messageWay = new MessageWayAPI(API_KEY);

try {
	$otp = $messageWay->setParams([
		'p1',
		'p2',
		'p3',
		'p4',
	])->sendViaSmart(MOBILE, TEMPLATE_ID);
	echo "ReferenceID: " . $otp['referenceID'] . PHP_EOL;
	echo "Sender: " . $otp['sender'] . PHP_EOL;
} catch (Exception $e) {
	echo "Error: " . $e->getMessage();
}