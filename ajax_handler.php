<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

// --- Includes ---
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/sms_service.php';
require_once __DIR__ . '/includes/otp_manager.php';

// --- Basic Setup ---
$response = ['success' => false, 'message' => 'Invalid Request.'];
$action = $_POST['action'] ?? '';
$csrf_token = $_POST['csrf_token'] ?? '';

// --- CSRF Protection ---
// if (!function_exists('verifyCSRFToken') || !verifyCSRFToken($csrf_token)) {
//     $response['message'] = 'Invalid security token.';
//     echo json_encode($response);
//     exit();
// }

// --- Database Connection ---
try {
    $db = new Database();
    $pdo = $db->getConnection();
} catch (Exception $e) {
    error_log('Database connection failed in ajax_handler: ' . $e->getMessage());
    $response['message'] = 'Server error: Could not connect to the database.';
    echo json_encode($response);
    exit();
}

// --- Action Routing ---
if ($action === 'send_otp') {
    $mobile = $_POST['mobile'] ?? '';
    if (!preg_match('/^09[0-9]{9}$/', $mobile)) {
        $response['message'] = 'The mobile number format is incorrect.';
    } else {
        // Prevent sending OTP too frequently
        if (function_exists('canSendOtp') && !canSendOtp($pdo, $mobile)) {
            $response['message'] = 'Please wait before requesting another code.';
        } else {
            $otp_code = generateAndStoreOtp($pdo, $mobile);
            if ($otp_code) {
                $sms_result = sendOtpSms($pdo, $mobile, $otp_code);
                if ($sms_result['success']) {
                    $response = [
                        'success' => true,
                        'message' => 'Verification code sent successfully.',
                        'resend_after' => 60 // Cooldown in seconds
                    ];
                } else {
                    $response['message'] = $sms_result['message'];
                }
            } else {
                $response['message'] = 'Server error: Could not generate OTP.';
            }
        }
    }
} 
elseif ($action === 'verify_otp') {
    $mobile = $_POST['mobile'] ?? '';
    $code = $_POST['code'] ?? '';

    $verification_result = verifyOtp($pdo, $mobile, $code);

    if ($verification_result['success']) {
        // On successful OTP verification, create a password reset token
        $reset_token = createPasswordResetToken($pdo, $mobile);
        if ($reset_token) {
            $response = [
                'success' => true,
                'message' => 'Code verified!',
                'redirect_url' => 'reset_password.php?token=' . urlencode($reset_token)
            ];
        } else {
             $response['message'] = 'Server error: Could not create reset session.';
        }
    } else {
        $response['message'] = $verification_result['message'];
    }
}

echo json_encode($response);
exit();
