
# MSGway OTP Login Module (Ready Studio Branded)

This module adds **OTP-based login** to your CRM using **MSGway**. Only **admins** can configure API settings.

## Files
- `install.sql` — creates `settings` and `user_otps` tables and inserts default keys
- `includes/settings.php` — simple key/value helper
- `includes/csrf.php` — CSRF token helpers
- `includes/msgway_client.php` — wrapper around the official MSGway PHP SDK
- `includes/otp.php` — generate / send / verify / rate-limit
- `admin_msgway_settings.php` — settings UI (restrict to admin)
- `login_with_otp.php` — OTP login page for users
- `assets/msgway-otp.css` — ReadyStudio-themed UI

## Install
1. Import `install.sql` to your CRM database.
2. Install MSGway PHP SDK (per official docs):
   ```bash
   composer require messageway/messagewayphp
   ```
   *Or clone the SDK into `msgway_otp_module/includes/MessageWayPHP`.*
3. Move this folder to your CRM root (or adjust includes paths).
4. Link **admin-only** settings page: `/msgway_otp_module/admin_msgway_settings.php`
5. Link the OTP login page from your login: `/msgway_otp_module/login_with_otp.php`

## Security
- OTP codes are **not stored in plaintext** (bcrypt hash).
- CSRF tokens added to all forms.
- Per-mobile resend throttle and attempt counter + block.
- Sessions should be configured with HttpOnly/SameSite/Secure flags.

## References
- MSGway PHP SDK methods (sendViaSMS, verifyOTP, getStatus) per official repo.
