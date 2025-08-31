<?php
require dirname(__FILE__) . '/../vendor/autoload.php';
require_once dirname(__FILE__) . '/configs.php';

use MessageWay\Api\MessageWayAPI;

/* Get Balance */
$messageWay = new MessageWayAPI(API_KEY);

try {
	$balance = $messageWay->getBalance();
	echo "Balance: " . $balance;
} catch (Exception $e) {
	echo "Error: " . $e->getMessage();
}