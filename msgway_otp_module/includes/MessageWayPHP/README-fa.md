<div dir=rtl>

![messageWay](examples/assets/logo-fa.png)

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
![Swagger][ico-swagger]
[![Global Smart OTP][ico-messageWay]][link-messageWay]

# راه‌پیام

این کتابخانه برای کار با سامانه راه‌پیام (MessageWay) آماده شده است. ([English 🇬🇧](https://github.com/MessageWay/MessageWayPHP/blob/main/README.md))

برای مشاهده نمونه کدها می‌توانید فایل‌های داخل پوشه [examples](https://github.com/MessageWay/MessageWayPHP/tree/main/examples)
را بررسی کنید.

## روش‌های ارسال

- پیامک (از سرشماره‌های: 2000, 3000, 9000, 50004)
- پیامک بین‌المللی (با twilio)
- پیام‌رسان‌ها:
    - ~~پیام‌رسان [واتساپ](https://whatsapp.com)~~
    - پیام‌رسان [گپ](https://gap.im)
    - پیام‌رسان [آی‌گپ](https://igap.net)
- تماس صوتی

## نیازمندی‌ها

- PHP 7.4 یا بالاتر
- ext-curl
- ext-json
- composer

## نصب

### با استفاده از Composer

ابتدا دستور ذیل را از طرق ترمینال اجرا کنید.
<div dir=ltr>

```shell
$ composer require messageway/messagewayphp
```

</div>

سپس کدهای ذیل را در ابتدای فایل موردنظر درج کنید، مقدار apiKey را باید پس از ثبت نام از طریق منو پروژه‌ها در سایت
messageWay بدست آورید.
<div dir=ltr>

```php
require dirname(__FILE__) . '/../vendor/autoload.php';
use MessageWay\Api\MessageWayAPI;

// Get apiKey from https://MSGWay.com/dashboard/document/
$apiKey = "";
$mobile = "";
$templateID = 3;
$messageWay = new MessageWayAPI($apiKey);
```

</div>

### بدون استفاده از Composer

ابتدا پروژه را از طریق اجرای دستور ذیل در ترمینال دریافت کنید.

<div dir=ltr>

```sh
$ git clone git@github.com:MessageWay/MessageWayPHP.git
```

</div>

همچنین دانلود پروژه با کلیک روی [این دکمه](https://github.com/MessageWay/MessageWayPHP/archive/refs/heads/main.zip) امکان‌پذیر
است.

سپس کدهای ذیل را در ابتدای فایل موردنظر درج کنید، مقدار apiKey را باید پس از ثبت نام از طریق منو پروژه‌ها در سایت **«راه‌پیام»** بدست آورید.

<div dir=ltr>

```php
require dirname(__FILE__) . '/MessageWayPHP/src/MessageWayAPI.php';
use MessageWay\Api\MessageWayAPI;

// Get apiKey from https://MSGWay.com/dashboard/document/
$apiKey = "";
$mobile = "";
$templateID = 3;
$messageWay = new MessageWayAPI($apiKey);
```

</div>

----

## ارسال پیام

### از طریق پیامک

<div dir=ltr>

```php
try {
	$otp = $messageWay->sendViaSMS($mobile, $templateID);
	echo "referenceID: " . $otp['referenceID'] . PHP_EOL;
	echo "sender: " . $otp['sender'] . PHP_EOL;
} catch (Exception $e) {
	echo "Error: " . $e->getMessage();
}
```

</div>

### از طریق پیام‌رسان

<div dir=ltr>

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

</div>

### از طریق تماس صوتی

<div dir=ltr>

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

</div>


---

## تایید کد OTP

<div dir=ltr>

```php
try {
	$verify = $messageWay->verifyOTP($OTP, $mobile);
	echo "OTPVerify: " . $verify['status'] . PHP_EOL;
} catch (Exception $e) {
	echo "Error: " . $e->getMessage();
}
```

</div>

---

## نمایش وضعیت OTP

<div dir=ltr>

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

</div>

---

## دریافت موجودی

<div dir=ltr>

```php
try {
    $balance = $messageWay->getBalance();
    echo "Balance: " . $balance;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

</div>

---

## دریافت الگو

<div dir=ltr>

```php
try {
	$template = $messageWay->getTemplate($templateID);
	echo "Template: " . $template['template'] . PHP_EOL;
	echo "Params: " . implode(", ", $template['params']) . PHP_EOL;
} catch (Exception $e) {
	echo "Error: " . $e->getMessage();
}
```

</div>

---

</div>

[ico-version]: https://img.shields.io/packagist/v/MessageWay/MessageWayPHP.svg?style=for-the-badge

[ico-downloads]: https://img.shields.io/packagist/dt/MessageWay/MessageWayPHP.svg?style=for-the-badge

[ico-messageWay]: https://img.shields.io/badge/-MSGWay.com-critical?link=https://MSGWay.com&style=for-the-badge

[ico-swagger]: https://img.shields.io/swagger/valid/3.0?specUrl=https%3A%2F%2Fdoc.MSGWay.com%2Fswagger.json&style=for-the-badge

[link-packagist]: https://packagist.org/packages/MessageWay/messagewayphp

[link-downloads]: https://packagist.org/packages/MessageWay/messagewayphp

[link-messageWay]: https://MSGWay.com/

