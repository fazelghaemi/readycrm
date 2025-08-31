
# MSGway OTP Module (Drop-in, No Composer)
1) Upload `msgway_otp_module` to `public_html/` and ensure this path exists:
   `public_html/msgway_otp_module/includes/MessageWayPHP/src/MessageWayAPI.php` (SDK files)
2) Import `msgway_otp_module/install.sql` in phpMyAdmin.
3) Open `/msgway_otp_module/admin_msgway_settings.php` and fill API Key + Template ID.
4) Login page: `/msgway_otp_module/login_with_otp.php`.
