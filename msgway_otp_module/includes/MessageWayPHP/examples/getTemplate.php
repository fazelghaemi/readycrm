<?php
require dirname(__FILE__) . '/../vendor/autoload.php';
require_once dirname(__FILE__) . '/configs.php';

use MessageWay\Api\MessageWayAPI;

/* Send Message by SMS */
$messageWay = new MessageWayAPI(API_KEY);

try {
	$template = $messageWay->getTemplate(282);
	echo "Template: " . $template['template'] . PHP_EOL;
	echo "Params: " . implode(", ", $template['params']) . PHP_EOL;
} catch (Exception $e) {
	echo "Error: " . $e->getMessage();
}