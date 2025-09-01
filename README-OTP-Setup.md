
# بسته نهایی ورود پیامکی (MSGway / راه‌پیام) — v1.2
این پکیج سه فایل کلیدی را برای پیاده‌سازی «ورود با کد تأیید پیامکی (OTP)» در CRM شما فراهم می‌کند:
- `sms_settings.php` — صفحه تنظیمات کامل (ذخیره در جدول `settings`)
- `ajax_sms_handler.php` — هندلر AJAX برای ارسال/تأیید OTP (یکپارچه با تنظیمات)
- `login_with_otp.php` — فرم لاگین با OTP (Ajax) + لاگین عادی

## نصب سریع
1) سه فایل بالا را در روت پروژه/CRM آپلود کنید.
2) فایل `sms_settings.php` را باز کنید و مقادیر زیر را ذخیره کنید:
   - **apiKey**: کلید معتبر
   - **templateID**: الگوی OTP تایید‌شده (برای تست معمولاً ۳)
   - **lineNumber**: در صورت اجبار حساب شما (در بسیاری از حساب‌ها ضروری است — نبودنش منجر به 400 می‌شود)
   - `msgway_mobile_format`، `msgway_resend_time`، `msgway_otp_length` نیز از همین صفحه قابل تنظیم‌اند.
3) برای بررسی دقیق خطاها، صفحه لاگین را با `?smsdebug=1` باز کنید تا هنگام ارسال، `debug=1` هم ارسال شود و پیام‌های دقیق سرور نمایش داده شود.
4) اگر نیاز دارید عملیات ورود واقعی با دیتابیس کاربران شما انجام شود، هوک `onOtpLoginSuccess($mobile, $pdo)` را (طبق نمونه‌ی `hooks/onOtpLoginSuccess.sample.php`) در سیستم خودتان پیاده‌سازی کنید.

## پیش‌نیاز جدول تنظیمات
اگر جدول `settings` را ندارید، این اسکریپت ساده را اجرا کنید (در `schema_settings.sql` هم موجود است):
```sql
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key`   varchar(191) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## نکات دیباگ خطای 400
- مقدار **lineNumber** را در `sms_settings.php` پر کنید (در بسیاری از حساب‌ها اجباری است).
- **templateID** باید الگوی OTP تأیید‌شده باشد (برای تست از ۳ استفاده کنید).
- **apiKey** معتبر باشد و حساب/خط شما محدودیت نداشته باشد.
- فرمت شماره بر اساس `msgway_mobile_format` نرمال‌سازی می‌شود (`+98` یا `09` یا `auto`).

## فایل‌ها و نقش آن‌ها
- `sms_settings.php (v2)`
  - کلیدها: `msgway_api_key`, `msgway_template_code`, `msgway_lineNumber`, `msgway_mobile_format`, `msgway_resend_time`, `msgway_otp_length`
  - سازگاری قدیمی: mirror به `rahpayam_api_key`, `rahpayam_pattern_code`
- `ajax_sms_handler.php (v1.2)`
  - اکشن‌ها: `send_otp`، `verify_otp`
  - خواندن تنظیمات از DB، ارسال به API با `lineNumber` (در صورت تنظیم)، دیباگ ۴۰۰ با پیام دقیق
- `login_with_otp.php`
  - UI ورود پیامکی (Ajax) + ورود کلاسیک
  - پس از تایید OTP، در صورت وجود هوک `onOtpLoginSuccess` آن را صدا می‌زند، وگرنه ورود موقت سِشنی انجام می‌دهد

## امنیت و بهترین‌عمل‌ها
- CSRF در همهٔ درخواست‌های Ajax رعایت شده است.
- ریت‌لیمیت: فاصلهٔ ارسال مجدد از تنظیمات خوانده می‌شود.
- کد OTP در سشن به‌مدت ۵ دقیقه ذخیره می‌شود (در صورت نیاز، به DB منتقل کنید).
- لاگ ارورها در سرور ذخیره می‌شود (برای پیگیری مشکلات مفید است).

---
ساخت Ready Studio — راه‌پیام / MSGway
