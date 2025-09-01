<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'sms_client.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$otp_length_display = 6;
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'msgway_otp_length'");
    $stmt->execute();
    $otp_length_display = $stmt->fetchColumn() ?: 6;
} catch (Exception $e) { /* از مقدار پیش‌فرض استفاده می‌شود */ }

$step = 1; 
$message = '';
$mobile_number_display = '';

// بخش اول: ارسال کد تایید
if (isset($_POST['send_otp'])) {
    $mobile = trim($_POST['mobile']);
    $mobile_number_display = htmlspecialchars($mobile);

    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'msgway_%'");
        $settings_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $otp_length = (int)($settings_data['msgway_otp_length'] ?? 6);
        $mobile_format_regex = $settings_data['msgway_mobile_format'] ?? '^09[0-9]{9}$';
    } catch (PDOException $e) {
        $otp_length = 6;
        $mobile_format_regex = '^09[0-9]{9}$';
        error_log("Database Error reading OTP settings: " . $e->getMessage());
    }
    $otp_length_display = $otp_length;

    if (!preg_match("/{$mobile_format_regex}/", $mobile)) {
        $message = '<p class="message error">فرمت شماره موبایل صحیح نیست.</p>';
    } else {
        $min = pow(10, $otp_length - 1);
        $max = pow(10, $otp_length) - 1;
        $otp_code = rand($min, $max);

        $_SESSION['otp_code'] = $otp_code;
        $_SESSION['otp_mobile'] = $mobile;
        $_SESSION['otp_time'] = time();
        
        $send_result = send_otp_message($mobile, $otp_code, $pdo);

        if ($send_result === "SUCCESS") {
            $message = '<p class="message success">کد تایید به شماره شما ارسال شد.</p>';
            $step = 2;
        } else {
            $message = '<p class="message error">' . htmlspecialchars($send_result) . '</p>';
        }
    }
}

// بخش دوم: تایید کد و ورود کاربر
if (isset($_POST['verify_code'])) {
    $entered_code = trim($_POST['otp_code']);
    $mobile_number_display = htmlspecialchars($_SESSION['otp_mobile'] ?? '');

    if (empty($_SESSION['otp_code']) || $entered_code != $_SESSION['otp_code']) {
        $message = '<p class="message error">کد وارد شده صحیح نیست.</p>';
        $step = 2;
    } elseif (time() - $_SESSION['otp_time'] > 120) {
        $message = '<p class="message error">کد منقضی شده است. لطفاً مجدداً درخواست کد دهید.</p>';
        $step = 1;
        unset($_SESSION['otp_code'], $_SESSION['otp_mobile'], $_SESSION['otp_time']);
    } else {
        try {
            $mobile_to_login = $_SESSION['otp_mobile'];
            $stmt = $pdo->prepare("SELECT * FROM users WHERE mobile = :mobile AND status = 'active' LIMIT 1");
            $stmt->execute(['mobile' => $mobile_to_login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_logged_in'] = true;
                $_SESSION['login_time'] = time();
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                
                $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW(), failed_login_attempts = 0, locked_until = NULL WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                logActivity($user['id'], 'login_otp', 'users', $user['id']);

                unset($_SESSION['otp_code'], $_SESSION['otp_mobile'], $_SESSION['otp_time']);

                header('Location: dashboard.php');
                exit();

            } else {
                $message = '<p class="message error">کاربری با این شماره موبایل یافت نشد یا حساب شما غیرفعال است.</p>';
                $step = 1;
                unset($_SESSION['otp_code'], $_SESSION['otp_mobile'], $_SESSION['otp_time']);
            }
        } catch (PDOException $e) {
            $message = '<p class="message error">خطای سیستمی رخ داد. لطفاً بعداً تلاش کنید.</p>';
            $step = 1;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
</html>