<?php
// msgway_otp_module/includes/otp.php
declare(strict_types=1);

use RS\MSGWAY\Client;

if (session_status() === PHP_SESSION_NONE) session_start();

function otp_generate(int $length = 5): string {
    $length = max(4, min($length, 8));
    $digits = '0123456789';
    $otp = '';
    for ($i = 0; $i < $length; $i++) {
        $otp .= $digits[random_int(0, 9)];
    }
    return $otp;
}

function otp_can_resend(PDO $pdo, string $mobile, int $resendAfter): bool {
    $stmt = $pdo->prepare("SELECT created_at FROM user_otps WHERE mobile=:m ORDER BY id DESC LIMIT 1");
    $stmt->execute([':m' => $mobile]);
    $last = $stmt->fetchColumn();
    if (!$last) return true;
    return (time() - strtotime((string)$last)) >= $resendAfter;
}

/**
 * @return array{ok:bool,msg:string,reference_id?:string}
 */
function otp_send(PDO $pdo, Client $client, string $mobile, int $templateID, int $length, int $expiry, int $resendAfter): array {
    $mobile = preg_replace('/\D+/', '', $mobile);
    if (!$mobile) return ['ok' => false, 'msg' => 'شماره موبایل نامعتبر است.'];

    if (!otp_can_resend($pdo, $mobile, $resendAfter)) {
        return ['ok' => false, 'msg' => 'لطفاً بعداً دوباره تلاش کنید (محدودیت ارسال مجدد).'];
    }

    // OTP واقعی توسط پیامک/الگو ارسال می‌شود؛ کد محلی فقط برای fallback ذخیره می‌شد.
    $code = otp_generate($length);

    try {
        $r = $client->sendOTP($mobile, $templateID);
        $referenceId = $r['referenceID'] ?? null;
    } catch (\Throwable $e) {
        return ['ok' => false, 'msg' => 'خطا در ارسال OTP: ' . $e->getMessage()];
    }

    $stmt = $pdo->prepare("INSERT INTO user_otps (mobile, code_hash, reference_id, expires_at, ip)
                           VALUES (:m, :h, :ref, :exp, :ip)");
    $ok = $stmt->execute([
        ':m'   => $mobile,
        ':h'   => password_hash($code, PASSWORD_BCRYPT),
        ':ref' => $referenceId,
        ':exp' => date('Y-m-d H:i:s', time() + $expiry),
        ':ip'  => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
    if (!$ok) return ['ok' => false, 'msg' => 'ثبت رکورد OTP با مشکل مواجه شد.'];

    $_SESSION['otp_mobile']       = $mobile;
    $_SESSION['otp_sent_at']      = time();
    $_SESSION['otp_reference_id'] = $referenceId;

    return ['ok' => true, 'msg' => 'کد تایید ارسال شد', 'reference_id' => (string)$referenceId];
}

/**
 * @return array{ok:bool,msg:string}
 */
function otp_verify(PDO $pdo, Client $client, string $mobile, string $code): array {
    $mobile = preg_replace('/\D+/', '', $mobile);
    $code   = trim($code);

    try {
        $ver = $client->verifyOTP($code, $mobile);
        $status = $ver['status'] ?? null;
        if ($status === 'verified' || $status === true || $status === 1) {
            $pdo->prepare("UPDATE user_otps SET status='VERIFIED' WHERE mobile=:m ORDER BY id DESC LIMIT 1")
                ->execute([':m' => $mobile]);
            return ['ok' => true, 'msg' => 'تایید شد'];
        }
    } catch (\Throwable $e) {
        // fallback محلی
    }

    $stmt = $pdo->prepare("SELECT id, code_hash, expires_at, attempts
                           FROM user_otps WHERE mobile=:m AND status='PENDING'
                           ORDER BY id DESC LIMIT 1");
    $stmt->execute([':m' => $mobile]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return ['ok' => false, 'msg' => 'کد یافت نشد؛ دوباره درخواست دهید.'];

    if (time() > strtotime((string)$row['expires_at'])) {
        $pdo->prepare("UPDATE user_otps SET status='EXPIRED' WHERE id=:id")->execute([':id' => $row['id']]);
        return ['ok' => false, 'msg' => 'کد منقضی شده است.'];
    }

    if (!password_verify($code, (string)$row['code_hash'])) {
        $attempts = (int)$row['attempts'] + 1;
        $pdo->prepare("UPDATE user_otps SET attempts=:a WHERE id=:id")->execute([':a' => $attempts, ':id' => $row['id']]);
        if ($attempts >= 5) {
            $pdo->prepare("UPDATE user_otps SET status='BLOCKED' WHERE id=:id")->execute([':id' => $row['id']]);
            return ['ok' => false, 'msg' => 'تعداد تلاش‌ها بیش از حد مجاز است.'];
        }
        return ['ok' => false, 'msg' => 'کد نادرست است.'];
    }

    $pdo->prepare("UPDATE user_otps SET status='VERIFIED' WHERE id=:id")->execute([':id' => $row['id']]);
    return ['ok' => true, 'msg' => 'تایید شد'];
}
