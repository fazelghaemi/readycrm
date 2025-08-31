![messageWay](examples/assets/logo.png)

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
![Swagger][ico-swagger]
[![MessageWay][ico-MSGWay]][link-MSGWay]

# MessageWay PHP SDK

A PHP SDK for the MessageWay API. ([فارسی 🇮🇷](https://github.com/MessageWay/MessageWayPHP/blob/main/README-fa.md))

## Available Methods

- SMS (Iran: 2000, 3000, 9000, 50004)
- Global SMS (with Twilio)
- Messenger
    - ~~[Whatsapp](https://whatsapp.com) Messenger~~
    - [Gap](https://gap.im) Messenger
    - [iGap](https://igap.net) Messenger
- IVR

## Requirements

- PHP 7.4 or higher
- ext-curl
- ext-json
- composer

## Installation

### with Composer

```shell
$ composer require messageway/messagewayphp
```

#### Require

```php
require dirname(__FILE__) . '/../vendor/autoload.php';
use MessageWay\Api\MessageWayAPI;

// Get apiKey from https://MSGWay.com/dashboard/document/
$apiKey = "";
$mobile = "";
$templateID = 3;
$messageWay = new MessageWayAPI($apiKey);
```

### without Composer

```sh
$ git clone git@github.com:MessageWay/MessageWayPHP.git
```

#### Require

```php
require dirname(__FILE__) . '/MessageWayPHP/src/MessageWayAPI.php';
use MessageWay\Api\MessageWayAPI;

// Get apiKey from https://MSGWay.com/dashboard/document/
$apiKey = "";
$mobile = "";
$templateID = 3;
$messageWay = new MessageWayAPI($apiKey);
```

----

## Send OTP

### By SMS

```php
try {
	$otp = $messageWay->sendViaSMS($mobile, $templateID);
	echo "referenceID: " . $otp['referenceID'] . PHP_EOL;
	echo "sender: " . $otp['sender'] . PHP_EOL;
} catch (Exception $e) {
	echo "Error: " . $e->getMessage();
}
```

### By  Messenger

```php
try {
	$provider = $messageWay->getProviderByName('gap');
	$otp = $messageWay->sendViaMessenger($mobile, $templateID, $provider);
	echo "referenceID: " . $otp['referenceID'] . PHP_EOL;
	echo "sender: " . $otp['sender'] . PHP_EOL;
} catch (Exception $e) {
	echo "Error: " . $e->getMessage();
}
```

### By  IVR

```php
$templateID = 2;
try {
	$otp = $messageWay->sendViaIVR($mobile, $templateID);
	echo "referenceID: " . $otp['referenceID'] . PHP_EOL;
	echo "sender: " . $otp['sender'] . PHP_EOL;
} catch (Exception $e) {
	echo "Error: " . $e->getMessage();
}
```

---

## Verify

```php
try {
	$verify = $messageWay->verifyOTP($OTP, $mobile);
	echo "OTPVerify: " . $verify['status'] . PHP_EOL;
} catch (Exception $e) {
	echo "Error: " . $e->getMessage();
}
```

---

## Status

```php
try {
	$status = $messageWay->getStatus($OTPRefrenceID);
    echo "OTPStatus: " . $status['OTPStatus'] . PHP_EOL;
	echo "OTPVerified: " . $status['OTPVerified'] . PHP_EOL;
	echo "OTPMethod: " . $status['OTPMethod'] . PHP_EOL;
} catch (Exception $e) {
	echo "Error: " . $e->getMessage();
}
```

---

## Balance

```php
try {
	$balance = $messageWay->getBalance();
    echo "Balance: " . $balance;
} catch (Exception $e) {
	echo "Error: " . $e->getMessage();
}
```

---

## Get Template

```php
$templateID = 2;
try {
	$template = $messageWay->getTemplate($templateID);
	echo "Template: " . $template['template'] . PHP_EOL;
	echo "Params: " . implode(", ", $template['params']) . PHP_EOL;
} catch (Exception $e) {
	echo "Error: " . $e->getMessage();
}
```

---


## License

MIT

[ico-version]: https://img.shields.io/packagist/v/messageway/MessageWayPHP.svg?style=for-the-badge

[ico-downloads]: https://img.shields.io/packagist/dt/messageway/MessageWayPHP.svg?style=for-the-badge

[ico-MSGWay]: https://img.shields.io/badge/-MSGWay.com-critical?link=https://MSGWay.com&style=for-the-badge

[ico-swagger]: https://img.shields.io/swagger/valid/3.0?specUrl=https%3A%2F%2Fdoc.msgway.com%2Fswagger.json&style=for-the-badge

[link-packagist]: https://packagist.org/packages/messageway/messagewayphp

[link-downloads]: https://packagist.org/packages/messageway/messagewayphp

[link-MSGWay]: https://MSGWay.com/

