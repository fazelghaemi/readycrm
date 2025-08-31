
# Phase 0 — Ready Studio CRM Hardening & Branding

این بسته شامل:
- `includes/security_bootstrap.php` — سشن امن + هدرهای امنیتی + لاگر
- `includes/upload_guard.php` — آپلود امن (MIME/EXIF/اندازه/دایرکتوری تاریخ‌دار)
- `includes/branding.php` — تزریق CSS variables از `settings`
- `public/assets/readystudio-theme.css` — استایل برند Ready Studio
- `.htaccess` — محدودسازی دسترسی به فایل‌های حساس (Apache)
- `scripts/install_branding.sql` — افزودن کلیدهای برند به جدول `settings`
- `scripts/inject_security_include.php` — اینجکت خودکار سکوریتی به ابتدای فایل‌های PHP
- `public/upload_secure_example.php` — نمونهٔ استفاده از آپلود امن

## گام‌ها
1) **کپی پوشه‌ها** داخل ریشهٔ پروژه.
2) اجرای SQL:
   ```sql
   SOURCE scripts/install_branding.sql;
   ```
3) اضافه‌کردن لینک استایل سراسری در layout اصلی (مثلا `includes/header.php`):
   ```html
   <link rel="stylesheet" href="/public/assets/readystudio-theme.css">
   <?php require_once __DIR__.'/../includes/branding.php'; brand_print_css_vars($pdo); ?>
   ```
4) (اختیاری ولی توصیه‌شده) اینجکت خودکار سکوریتی در همهٔ entrypointها:
   ```bash
   php scripts/inject_security_include.php /path/to/your/crm
   ```
   یا به‌صورت دستی در اولین خطوط هر فایل PHP:
   ```php
   require_once __DIR__ . '/includes/security_bootstrap.php';
   ```
5) برای اندپوینت‌های آپلود، از `ug_handle_upload()` استفاده کنید.

> نکته: CSP در حالت **Report-Only** است تا سایت نشکند. پس از تست، در `security_bootstrap.php` مقدار `SECURITY_HEADERS_ENFORCE` را `true` کنید.
