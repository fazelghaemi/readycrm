<?php
/**
 * Manages OTP generation, storage, and verification.
 *
 * Required DB Table `otp_codes`:
 * CREATE TABLE `otp_codes` (
 * `id` INT AUTO_INCREMENT PRIMARY KEY,
 * `mobile` VARCHAR(20) NOT NULL,
 * `code_hash` VARCHAR(255) NOT NULL,
 * `status` ENUM('PENDING', 'VERIFIED', 'EXPIRED', 'FAILED') NOT NULL DEFAULT 'PENDING',
 * `expires_at` DATETIME NOT NULL,
 * `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
 * `attempts` TINYINT DEFAULT 0,
 * INDEX `mobile_status_index` (`mobile`, `status`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 * * Required DB Table `password_resets`:
 * CREATE TABLE `password_resets` (
 * `id` INT AUTO_INCREMENT PRIMARY KEY,
 * `mobile` VARCHAR(20) NOT NULL,
 * `token_hash` VARCHAR(255) NOT NULL,
 * `expires_at` DATETIME NOT NULL,
 * `is_used` BOOLEAN NOT NULL DEFAULT FALSE,
 * `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 */

function canSendOtp(PDO $pdo, string $mobile): bool
{
    return true;
    $stmt = $pdo->prepare("SELECT created_at FROM otp_codes WHERE mobile = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$mobile]);
    $last_sent = $stmt->fetchColumn();
    if (!$last_sent) {
        return true;
    }
    // Allow resend after 60 seconds
    return (time() - strtotime($last_sent)) > 60;
}

function generateAndStoreOtp(PDO $pdo, string $mobile): ?string
{
    $otp_code = (string)random_int(1000, 9999);
    $code_hash = password_hash($otp_code, PASSWORD_DEFAULT);
    $expires_at = date('Y-m-d H:i:s', time() + (5 * 60)); // 5 minutes validity
try{
    $stmt = $pdo->prepare(
        "INSERT INTO otp_codes (mobile, code_hash, expires_at) VALUES (?, ?, ?)"
    );
    $result = $stmt->execute([$mobile, $code_hash, $expires_at]);
}catch(Exception $e){
    var_dump($e);
    die("dd");
}
    return $otp_code;
    return $result ? $otp_code : null;
}

function verifyOtp(PDO $pdo, string $mobile, string $code): array
{
    $stmt = $pdo->prepare(
        "SELECT id, code_hash, expires_at, attempts FROM otp_codes 
         WHERE mobile = ? AND status = 'PENDING' ORDER BY id DESC LIMIT 1"
    );
    $stmt->execute([$mobile]);
    $otp_record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$otp_record) {
        return ['success' => false, 'message' => 'No pending verification code found. Please request a new one.'];
    }

    if (time() > strtotime($otp_record['expires_at'])) {
        $pdo->prepare("UPDATE otp_codes SET status = 'EXPIRED' WHERE id = ?")->execute([$otp_record['id']]);
        return ['success' => false, 'message' => 'The verification code has expired.'];
    }

    if ((int)$otp_record['attempts'] >= 5) {
        return ['success' => false, 'message' => 'Too many failed attempts. Please request a new code.'];
    }

    if (!password_verify($code, $otp_record['code_hash'])) {
        $pdo->prepare("UPDATE otp_codes SET attempts = attempts + 1 WHERE id = ?")->execute([$otp_record['id']]);
        return ['success' => false, 'message' => 'The entered code is incorrect.'];
    }

    // Success
    $pdo->prepare("UPDATE otp_codes SET status = 'VERIFIED' WHERE id = ?")->execute([$otp_record['id']]);
    return ['success' => true];
}

function createPasswordResetToken(PDO $pdo, string $mobile): ?string
{
    try {
        $token = bin2hex(random_bytes(32));
        $token_hash = hash('sha256', $token);
        $expires_at = date('Y-m-d H:i:s', time() + (15 * 60)); // 15 minutes validity

        $stmt = $pdo->prepare(
            "INSERT INTO password_resets (mobile, token_hash, expires_at) VALUES (?, ?, ?)"
        );
        $result = $stmt->execute([$mobile, $token_hash, $expires_at]);

        return $result ? $token : null;
    } catch (Exception $e) {
        error_log("Token generation failed: " . $e->getMessage());
        return null;
    }
}
