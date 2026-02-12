<?php
/**
 * ══════════════════════════════════════════════════════════════════════════════
 * ReadyCRM V3.5 - COMPLETE HELPER FUNCTIONS
 * ══════════════════════════════════════════════════════════════════════════════
 * @version 3.5.0
 * @author ReadyCRM Team
 * ══════════════════════════════════════════════════════════════════════════════
 */

// جلوگیری از دسترسی مستقیم
if (!defined('APP_NAME')) {
    die('Direct access not allowed.');
}

// ═══════════════════════════════════════════════════════════════════════════════
// CSRF TOKEN MANAGEMENT
// ═══════════════════════════════════════════════════════════════════════════════

if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verifyCSRFToken')) {
    function verifyCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// FLASH MESSAGES
// ═══════════════════════════════════════════════════════════════════════════════

if (!function_exists('displayMessage')) {
    function displayMessage($type = '') {
        $messages = ['success_message', 'error_message', 'warning_message', 'info_message'];
        $output = '';

        foreach ($messages as $message_type) {
            if (isset($_SESSION[$message_type])) {
                $class = str_replace('_message', '', $message_type);
                $icon  = getMessageIcon($class);
                $alert_class = ($class === 'error') ? 'danger' : $class;

                $output .= '<div class="alert alert-' . $alert_class . ' alert-dismissible fade show shadow-sm" role="alert">';
                $output .= '<i class="' . $icon . ' me-2"></i>';
                $output .= htmlspecialchars($_SESSION[$message_type]);
                $output .= '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                $output .= '</div>';

                unset($_SESSION[$message_type]);
            }
        }

        return $output;
    }
}

if (!function_exists('getMessageIcon')) {
    function getMessageIcon($type) {
        $icons = [
            'success' => 'fas fa-check-circle',
            'error'   => 'fas fa-times-circle',
            'warning' => 'fas fa-exclamation-triangle',
            'info'    => 'fas fa-info-circle'
        ];
        return $icons[$type] ?? 'fas fa-info-circle';
    }
}

if (!function_exists('setMessage')) {
    function setMessage($message, $type = 'success') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION[$type . '_message'] = $message;
    }
}

if (!function_exists('getMessage')) {
    function getMessage() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $types = ['success', 'error', 'warning', 'info'];
        foreach ($types as $type) {
            $key = $type . '_message';
            if (isset($_SESSION[$key])) {
                $msg = ['text' => $_SESSION[$key], 'type' => $type];
                unset($_SESSION[$key]);
                return $msg;
            }
        }
        return null;
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// PASSWORD RESET FUNCTIONS
// ═══════════════════════════════════════════════════════════════════════════════

if (!function_exists('createPasswordResetToken')) {
    function createPasswordResetToken($user_id, $email) {
        global $pdo;

        try {
            $expiry = defined('PASSWORD_RESET_TOKEN_EXPIRY') ? PASSWORD_RESET_TOKEN_EXPIRY : 3600;
            $token      = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', time() + $expiry);

            $stmt = $pdo->prepare("DELETE FROM password_reset_tokens WHERE user_id = ? OR email = ?");
            $stmt->execute([$user_id, $email]);

            $stmt = $pdo->prepare("
                INSERT INTO password_reset_tokens (user_id, email, token, expires_at, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $email,
                $token,
                $expires_at,
                getRealIpAddr(),
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);

            return ['success' => true, 'token' => $token];

        } catch (Exception $e) {
            error_log('Password reset token creation failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'خطای سیستمی'];
        }
    }
}

if (!function_exists('verifyPasswordResetToken')) {
    function verifyPasswordResetToken($token) {
        global $pdo;

        try {
            $stmt = $pdo->prepare("
                SELECT id, user_id, email, expires_at, is_used
                FROM password_reset_tokens
                WHERE token = ? AND is_used = FALSE
            ");
            $stmt->execute([$token]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) {
                return ['success' => false, 'message' => 'توکن نامعتبر یا استفاده شده است'];
            }
            if (strtotime($data['expires_at']) < time()) {
                return ['success' => false, 'message' => 'توکن منقضی شده است'];
            }

            return [
                'success'  => true,
                'user_id'  => $data['user_id'],
                'email'    => $data['email'],
                'token_id' => $data['id']
            ];

        } catch (Exception $e) {
            error_log('Token verification failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'خطای سیستمی'];
        }
    }
}

if (!function_exists('markTokenAsUsed')) {
    function markTokenAsUsed($token) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("UPDATE password_reset_tokens SET is_used = TRUE, used_at = NOW() WHERE token = ?");
            return $stmt->execute([$token]);
        } catch (Exception $e) {
            error_log('Mark token as used failed: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('resetPasswordWithToken')) {
    function resetPasswordWithToken($token, $new_password) {
        global $pdo;

        $verify = verifyPasswordResetToken($token);
        if (!$verify['success']) {
            return $verify;
        }

        try {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $verify['user_id']]);
            markTokenAsUsed($token);
            return ['success' => true, 'message' => 'رمز عبور با موفقیت تغییر کرد'];
        } catch (Exception $e) {
            error_log('Password reset failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'خطای سیستمی'];
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// EMAIL FUNCTIONS
// ═══════════════════════════════════════════════════════════════════════════════

if (!function_exists('sendEmail')) {
    function sendEmail($to, $subject, $body, $is_html = true) {
        try {
            $from_name  = defined('MAIL_FROM_NAME')  ? MAIL_FROM_NAME  : 'CRM System';
            $from_email = defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : 'noreply@localhost';

            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: {$from_name} <{$from_email}>\r\n";
            $headers .= "Reply-To: {$from_email}\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

            $result = mail($to, $subject, $body, $headers);
            return ['success' => $result, 'message' => $result ? 'ایمیل ارسال شد' : 'خطا در ارسال ایمیل'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

if (!function_exists('getPasswordResetEmailTemplate')) {
    function getPasswordResetEmailTemplate($reset_link, $user_name = '') {
        $expiry_minutes = defined('PASSWORD_RESET_TOKEN_EXPIRY') ? PASSWORD_RESET_TOKEN_EXPIRY / 60 : 60;
        $app_name = defined('APP_NAME') ? APP_NAME : 'CRM';

        return '<!DOCTYPE html>
        <html lang="fa" dir="rtl">
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Tahoma, Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 40px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #00b0a4 0%, #17b89a 100%); color: white; padding: 30px; text-align: center; }
                .content { padding: 40px 30px; line-height: 1.8; color: #333; }
                .btn { display: inline-block; background: #00b0a4; color: white; padding: 14px 40px; text-decoration: none; border-radius: 8px; margin: 20px 0; font-weight: bold; }
                .warning { background: #fff3cd; border-right: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 13px; color: #666; border-top: 1px solid #e9ecef; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header"><h1>🔐 بازیابی رمز عبور</h1></div>
                <div class="content">
                    <p>سلام' . ($user_name ? ' ' . htmlspecialchars($user_name) : '') . '،</p>
                    <p>درخواستی برای بازیابی رمز عبور حساب کاربری شما دریافت شده است.</p>
                    <p style="text-align:center;"><a href="' . $reset_link . '" class="btn">بازیابی رمز عبور</a></p>
                    <div class="warning"><strong>⚠️ توجه:</strong> این لینک تنها به مدت ' . $expiry_minutes . ' دقیقه معتبر است.</div>
                    <p>اگر شما این درخواست را ارسال نکرده‌اید، این ایمیل را نادیده بگیرید.</p>
                </div>
                <div class="footer"><p>© ' . date('Y') . ' ' . htmlspecialchars($app_name) . ' — تمامی حقوق محفوظ است.</p></div>
            </div>
        </body>
        </html>';
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// PERSIAN DATE (JALALI) FUNCTIONS
// ═══════════════════════════════════════════════════════════════════════════════

if (!function_exists('gregorian_to_jalali')) {
    function gregorian_to_jalali($gy, $gm, $gd, &$jy, &$jm, &$jd) {
        $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];

        if ($gy <= 1600) {
            $jy = 0;
            $gy -= 621;
        } else {
            $jy = 979;
            $gy -= 1600;
        }

        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;

        $days = (365 * $gy)
              + (int)(($gy2 + 3) / 4)
              - (int)(($gy2 + 99) / 100)
              + (int)(($gy2 + 399) / 400)
              - 80
              + $gd
              + $g_d_m[$gm - 1];

        $jy += 33 * (int)($days / 12053);
        $days %= 12053;
        $jy += 4 * (int)($days / 1461);
        $days %= 1461;

        if ($days > 365) {
            $jy += (int)(($days - 1) / 365);
            $days = ($days - 1) % 365;
        }

        if ($days < 186) {
            $jm = 1 + (int)($days / 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + (int)(($days - 186) / 30);
            $jd = 1 + (($days - 186) % 30);
        }
    }
}

if (!function_exists('jdate')) {
    function jdate($format, $timestamp = '') {
        $timestamp = $timestamp ? (int)$timestamp : time();

        $jy = $jm = $jd = 0;
        gregorian_to_jalali(
            (int)date('Y', $timestamp),
            (int)date('n', $timestamp),
            (int)date('j', $timestamp),
            $jy, $jm, $jd
        );

        $persian_months = [
            1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد',
            4 => 'تیر',     5 => 'مرداد',     6 => 'شهریور',
            7 => 'مهر',     8 => 'آبان',       9 => 'آذر',
            10 => 'دی',    11 => 'بهمن',      12 => 'اسفند'
        ];

        $output = '';
        $len    = strlen($format);

        for ($i = 0; $i < $len; $i++) {
            $c = $format[$i];
            switch ($c) {
                case 'Y': $output .= $jy;                          break;
                case 'y': $output .= substr($jy, -2);              break;
                case 'M': $output .= $persian_months[$jm];         break;
                case 'm': $output .= sprintf('%02d', $jm);         break;
                case 'n': $output .= $jm;                          break;
                case 'd': $output .= sprintf('%02d', $jd);         break;
                case 'j': $output .= $jd;                          break;
                case 'H': $output .= date('H', $timestamp);        break;
                case 'i': $output .= date('i', $timestamp);        break;
                case 's': $output .= date('s', $timestamp);        break;
                case 'D': $output .= date('D', $timestamp);        break;
                case 'l': $output .= date('l', $timestamp);        break;
                default:  $output .= $c;
            }
        }

        return $output;
    }
}

if (!function_exists('formatPersianDate')) {
    function formatPersianDate($date, $format = 'Y/m/d') {
        if (!$date || $date === '0000-00-00' || $date === '0000-00-00 00:00:00' || $date === null) {
            return '—';
        }
        $timestamp = is_numeric($date) ? (int)$date : strtotime($date);
        if (!$timestamp || $timestamp <= 0) return '—';
        return jdate($format, $timestamp);
    }
}

if (!function_exists('convertToJalaliForChart')) {
    function convertToJalaliForChart($gregorian_date) {
        $timestamp = strtotime($gregorian_date);
        if (!$timestamp) return '—';

        $jy = $jm = $jd = 0;
        gregorian_to_jalali(
            (int)date('Y', $timestamp),
            (int)date('n', $timestamp),
            (int)date('j', $timestamp),
            $jy, $jm, $jd
        );

        $persian_months = [
            1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد',
            4 => 'تیر',     5 => 'مرداد',     6 => 'شهریور',
            7 => 'مهر',     8 => 'آبان',       9 => 'آذر',
            10 => 'دی',    11 => 'بهمن',      12 => 'اسفند'
        ];

        return $persian_months[$jm] . ' ' . $jy;
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// MONEY & STRING FORMATTING
// ═══════════════════════════════════════════════════════════════════════════════

if (!function_exists('formatMoney')) {
    function formatMoney($amount, $currency = null) {
        if ($amount === null || $amount === '') return '0';

        $curr = $currency ?? (defined('CURRENCY') ? CURRENCY : '');
        $clean  = preg_replace('/[^0-9.]/', '', $amount);
        $number = floatval($clean);

        return number_format($number, 0, '.', ',') . ($curr ? ' ' . $curr : '');
    }
}

if (!function_exists('formatPhone')) {
    function formatPhone($phone) {
        if (!$phone) return '-';
        $phone = preg_replace('/\D/', '', $phone);
        if (strlen($phone) == 11 && substr($phone, 0, 2) == '09') {
            return substr($phone, 0, 4) . '-' . substr($phone, 4, 3) . '-' . substr($phone, 7);
        }
        return $phone;
    }
}

if (!function_exists('truncateText')) {
    function truncateText($text, $length = 100, $suffix = '...') {
        if (mb_strlen($text, 'UTF-8') <= $length) return $text;
        return mb_substr($text, 0, $length, 'UTF-8') . $suffix;
    }
}

if (!function_exists('generateUniqueCode')) {
    function generateUniqueCode($prefix = '', $length = 8) {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code  = $prefix;
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $code;
    }
}

if (!function_exists('formatFileSize')) {
    function formatFileSize($bytes) {
        if (!$bytes) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < 4) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// FILE UPLOAD
// ═══════════════════════════════════════════════════════════════════════════════

if (!function_exists('isValidFileType')) {
    function isValidFileType($filename, $allowed_types = null) {
        $types = $allowed_types ?? (defined('UPLOAD_ALLOWED_TYPES') ? UPLOAD_ALLOWED_TYPES : ['jpg','jpeg','png','pdf','doc','docx']);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $types);
    }
}

if (!function_exists('isValidFileSize')) {
    function isValidFileSize($file_size, $max_size = null) {
        $max = $max_size ?? (defined('UPLOAD_MAX_SIZE') ? UPLOAD_MAX_SIZE : 5242880);
        return $file_size <= $max;
    }
}

if (!function_exists('uploadFile')) {
    function uploadFile($file, $upload_path = null, $allowed_types = null) {
        $path = $upload_path ?? (defined('UPLOAD_PATH') ? UPLOAD_PATH : 'uploads/');

        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return ['success' => false, 'message' => 'فایلی انتخاب نشده است'];
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'خطا در آپلود فایل'];
        }
        if (!isValidFileType($file['name'], $allowed_types)) {
            return ['success' => false, 'message' => 'فرمت فایل مجاز نیست'];
        }
        if (!isValidFileSize($file['size'])) {
            return ['success' => false, 'message' => 'اندازه فایل بیش از حد مجاز است'];
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename  = uniqid() . '_' . time() . '.' . $extension;
        $full_path = rtrim($path, '/') . '/' . $filename;

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        if (move_uploaded_file($file['tmp_name'], $full_path)) {
            return [
                'success'       => true,
                'filename'      => $filename,
                'path'          => $full_path,
                'original_name' => $file['name']
            ];
        }

        return ['success' => false, 'message' => 'خطا در ذخیره فایل'];
    }
}

if (!function_exists('deleteFile')) {
    function deleteFile($file_path) {
        if (file_exists($file_path)) {
            return unlink($file_path);
        }
        return false;
    }
}

if (!function_exists('getUploadPath')) {
    function getUploadPath($type = 'general') {
        $base = defined('ROOT_PATH') ? ROOT_PATH . 'uploads/' : __DIR__ . '/../uploads/';
        $paths = [
            'project'  => $base . 'projects/',
            'avatar'   => $base . 'avatars/',
            'document' => $base . 'documents/',
            'general'  => $base,
        ];
        $path = $paths[$type] ?? $base;
        if (!is_dir($path)) mkdir($path, 0755, true);
        return $path;
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// PAGINATION
// ═══════════════════════════════════════════════════════════════════════════════

if (!function_exists('createPagination')) {
    function createPagination($current_page, $total_records, $records_per_page, $base_url) {
        $total_pages = (int)ceil($total_records / $records_per_page);
        if ($total_pages <= 1) return '';

        $output  = '<nav aria-label="صفحه‌بندی"><ul class="pagination justify-content-center">';

        if ($current_page > 1) {
            $output .= '<li class="page-item"><a class="page-link" href="' . $base_url . '&page=' . ($current_page - 1) . '">قبلی</a></li>';
        }

        $start = max(1, $current_page - 2);
        $end   = min($total_pages, $current_page + 2);

        for ($i = $start; $i <= $end; $i++) {
            $active  = ($i == $current_page) ? ' active' : '';
            $output .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $base_url . '&page=' . $i . '">' . $i . '</a></li>';
        }

        if ($current_page < $total_pages) {
            $output .= '<li class="page-item"><a class="page-link" href="' . $base_url . '&page=' . ($current_page + 1) . '">بعدی</a></li>';
        }

        $output .= '</ul></nav>';
        return $output;
    }
}

if (!function_exists('generateRandomColor')) {
    function generateRandomColor() {
        $colors = ['#00b0a4', '#0284c7', '#16a34a', '#9333ea', '#ea580c', '#dc2626'];
        return $colors[array_rand($colors)];
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// TRANSLATION & STATUS HELPERS
// ═══════════════════════════════════════════════════════════════════════════════

if (!function_exists('getRoleTitle')) {
    function getRoleTitle($role) {
        $roles = [
            'admin'   => 'مدیر کل',
            'manager' => 'مدیر',
            'sales'   => 'فروشنده',
            'user'    => 'کاربر'
        ];
        return $roles[$role] ?? $role;
    }
}

if (!function_exists('getStatusTitle')) {
    function getStatusTitle($status, $type = 'general') {
        $all = [
            'general' => [
                'active'       => 'فعال',
                'inactive'     => 'غیرفعال',
                'pending'      => 'در انتظار',
                'suspended'    => 'تعلیق شده',
                'completed'    => 'تکمیل شده',
                'cancelled'    => 'لغو شده',
                'confirmed'    => 'تایید شده',
                'processing'   => 'در حال پردازش',
                'shipped'      => 'ارسال شده',
                'delivered'    => 'تحویل داده شده',
                'draft'        => 'پیش‌نویس',
                'discontinued' => 'متوقف شده',
                'in_progress'  => 'در حال انجام',
            ],
            'lead' => [
                'new'         => 'جدید',
                'contacted'   => 'تماس گرفته شده',
                'qualified'   => 'واجد شرایط',
                'proposal'    => 'پیشنهاد ارسال شده',
                'negotiation' => 'در حال مذاکره',
                'won'         => 'موفق',
                'lost'        => 'از دست رفته'
            ],
            'task' => [
                'pending'     => 'در انتظار',
                'in_progress' => 'در حال انجام',
                'completed'   => 'تکمیل شده',
                'cancelled'   => 'لغو شده'
            ],
            'project' => [
                'not_started' => 'شروع نشده',
                'in_progress' => 'در حال انجام',
                'on_hold'     => 'متوقف شده',
                'completed'   => 'تکمیل شده',
                'cancelled'   => 'لغو شده',
            ],
            'user' => [
                'active'    => 'فعال',
                'inactive'  => 'غیرفعال',
                'suspended' => 'معلق'
            ],
            'product' => [
                'active'       => 'فعال',
                'inactive'     => 'غیرفعال',
                'discontinued' => 'متوقف شده'
            ]
        ];

        return $all[$type][$status]
            ?? $all['general'][$status]
            ?? $status;
    }
}

if (!function_exists('getPriorityTitle')) {
    function getPriorityTitle($priority) {
        $p = [
            'low'    => 'کم',
            'medium' => 'متوسط',
            'high'   => 'بالا',
            'urgent' => 'فوری'
        ];
        return $p[$priority] ?? $priority;
    }
}

if (!function_exists('getStatusClass')) {
    function getStatusClass($status, $type = 'general') {
        $all = [
            'general' => [
                'active'      => 'success',
                'inactive'    => 'secondary',
                'pending'     => 'warning',
                'suspended'   => 'danger',
                'completed'   => 'success',
                'cancelled'   => 'danger',
                'in_progress' => 'primary'
            ],
            'lead' => [
                'new'         => 'primary',
                'contacted'   => 'info',
                'qualified'   => 'warning',
                'proposal'    => 'secondary',
                'negotiation' => 'dark',
                'won'         => 'success',
                'lost'        => 'danger'
            ],
            'project' => [
                'not_started' => 'secondary',
                'in_progress' => 'primary',
                'on_hold'     => 'warning',
                'completed'   => 'success',
                'cancelled'   => 'danger'
            ]
        ];
        return $all[$type][$status] ?? $all['general'][$status] ?? 'secondary';
    }
}

if (!function_exists('getPriorityClass')) {
    function getPriorityClass($priority) {
        $c = [
            'low'    => 'success',
            'medium' => 'warning',
            'high'   => 'danger',
            'urgent' => 'dark'
        ];
        return $c[$priority] ?? 'secondary';
    }
}

if (!function_exists('getActionClass')) {
    function getActionClass($action) {
        if (str_contains($action, 'create')) return 'success';
        if (str_contains($action, 'update')) return 'warning';
        if (str_contains($action, 'delete')) return 'danger';
        if (str_contains($action, 'login'))  return 'info';
        return 'secondary';
    }
}

if (!function_exists('getActionTitle')) {
    function getActionTitle($action) {
        $actions = [
            'login'           => 'ورود',
            'logout'          => 'خروج',
            'create_customer' => 'ایجاد مشتری',
            'update_customer' => 'بروزرسانی مشتری',
            'delete_customer' => 'حذف مشتری',
            'create_lead'     => 'ایجاد لید',
            'update_lead'     => 'بروزرسانی لید',
            'delete_lead'     => 'حذف لید',
            'create_task'     => 'ایجاد وظیفه',
            'update_task'     => 'بروزرسانی وظیفه',
            'delete_task'     => 'حذف وظیفه',
            'create_sale'     => 'ایجاد فروش',
            'update_sale'     => 'بروزرسانی فروش',
            'delete_sale'     => 'حذف فروش',
            'create_product'  => 'ایجاد محصول',
            'update_product'  => 'بروزرسانی محصول',
            'delete_product'  => 'حذف محصول',
            'create_user'     => 'ایجاد کاربر',
            'update_user'     => 'بروزرسانی کاربر',
            'delete_user'     => 'حذف کاربر',
            'create_project'  => 'ایجاد پروژه',
            'update_project'  => 'بروزرسانی پروژه',
            'delete_project'  => 'حذف پروژه',
            'auth'            => 'احراز هویت'
        ];
        return $actions[$action] ?? $action;
    }
}

if (!function_exists('getTableTitle')) {
    function getTableTitle($table) {
        $tables = [
            'customers' => 'مشتریان',
            'leads'     => 'لیدها',
            'tasks'     => 'وظایف',
            'sales'     => 'فروش‌ها',
            'products'  => 'محصولات',
            'users'     => 'کاربران',
            'projects'  => 'پروژه‌ها'
        ];
        return $tables[$table] ?? $table;
    }
}

if (!function_exists('getRoleClass')) {
    function getRoleClass($role) {
        $c = [
            'admin'   => 'danger',
            'manager' => 'warning',
            'sales'   => 'success',
            'user'    => 'info'
        ];
        return $c[$role] ?? 'secondary';
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// PERMISSION & SECURITY
// ═══════════════════════════════════════════════════════════════════════════════

if (!function_exists('hasPermission')) {
    function hasPermission($permission) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Admin همیشه دسترسی کامل دارد
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            return true;
        }

        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        global $pdo;

        try {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM user_permissions up
                JOIN permissions p ON up.permission_id = p.id
                WHERE up.user_id = ? AND p.name = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $permission]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
}

if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit();
        }
    }
}

if (!function_exists('checkPermission')) {
    function checkPermission($permission) {
        if (!hasPermission($permission)) {
            http_response_code(403);
            die('<div style="font-family:Tahoma;text-align:center;padding:50px;direction:rtl;"><h2>⛔ دسترسی ممنوع</h2><p>شما به این بخش دسترسی ندارید.</p><a href="javascript:history.back()">بازگشت</a></div>');
        }
    }
}

if (!function_exists('sanitizeInput')) {
    function sanitizeInput($data) {
        if ($data === null) return '';
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('getRealIpAddr')) {
    function getRealIpAddr() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
}

if (!function_exists('base64UrlEncode')) {
    function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

if (!function_exists('base64UrlDecode')) {
    function base64UrlDecode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// PROJECT HELPERS
// ═══════════════════════════════════════════════════════════════════════════════

if (!function_exists('getProjectsCount')) {
    function getProjectsCount($status = null) {
        global $pdo;
        try {
            if ($status) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE status = ?");
                $stmt->execute([$status]);
            } else {
                $stmt = $pdo->query("SELECT COUNT(*) FROM projects");
            }
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }
}

if (!function_exists('getDelayedProjectsCount')) {
    function getDelayedProjectsCount() {
        global $pdo;
        try {
            return (int)$pdo->query("
                SELECT COUNT(*) FROM projects 
                WHERE deadline < CURDATE() 
                AND status NOT IN ('completed', 'cancelled')
            ")->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// ACTIVITY & NOTIFICATIONS
// ═══════════════════════════════════════════════════════════════════════════════

if (!function_exists('logActivity')) {
    function logActivity($user_id, $action, $entity, $entity_id = null, $description = null) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("
                INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $action,
                $entity,
                $entity_id,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
        } catch (Exception $e) {
            error_log('logActivity failed: ' . $e->getMessage());
        }
    }
}

if (!function_exists('sendNotification')) {
    function sendNotification($user_id, $title, $message, $type = 'info') {
        global $pdo;
        try {
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, title, message, type, is_read)
                VALUES (?, ?, ?, ?, 0)
            ");
            $stmt->execute([$user_id, $title, $message, $type]);
        } catch (Exception $e) {
            error_log('sendNotification failed: ' . $e->getMessage());
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// URL & REQUEST HELPERS
// ═══════════════════════════════════════════════════════════════════════════════

if (!function_exists('buildUrl')) {
    function buildUrl($base_url, $new_params = []) {
        $params = array_merge($_GET, $new_params);
        $query  = http_build_query($params);
        return $base_url . ($query ? '?' . $query : '');
    }
}

if (!function_exists('cleanUrl')) {
    function cleanUrl($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}
