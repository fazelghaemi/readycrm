
<?php
// msgway_otp_module/includes/otp.php
use RS\MSGWAY\Client;

if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Generate numeric OTP with desired length (default 5).
 */
function otp_generate(int $length = 5): string {
    $length = max(4, min($length, 8));
    $digits = '0123456789';
    $otp = '';
    for ($i=0; $i<$length; $i++) {
        $otp .= $digits[random_int(0, 9)];
    }
    return $otp;
}

/**
 * Enforce simple rate-limit: per mobile per resend_after seconds.
 */
function otp_can_resend(PDO $pdo, string $mobile, int $resendAfter): bool {
    $stmt = $pdo->prepare("SELECT created_at FROM user_otps WHERE mobile=:m ORDER BY id DESC LIMIT 1");
    $stmt->execute([':m' => $mobile]);
    $last = $stmt->fetchColumn();
    if (!$last) return true;
    $lastTs = strtotime($last);
    return (time() - $lastTs) >= $resendAfter;
}

/**
 * Create and send an OTP via MSGway.
 * Returns array [ok=>bool, msg, reference_id]
 */
function otp_send(PDO $pdo, Client $client, string $mobile, int $templateID, int $length, int $expiry, int $resendAfter): array {
    $mobile = preg_replace('/\D+/', '', $mobile);
    if (!$mobile) return ['ok'=>false,'msg'=>'شماره موبایل نامعتبر است.'];

    if (!otp_can_resend($pdo, $mobile, $resendAfter)) {
        return ['ok'=>false,'msg'=>"لطفاً بعداً دوباره تلاش کنید (محدودیت ارسال مجدد)."];
    }

    $code = otp_generate($length);
    try {
        $r = $client->sendOTP($mobile, $templateID);
        $referenceId = $r['referenceID'] ?? null;
    } catch (\Exception $e) {
        return ['ok'=>false,'msg'=>"خطا در ارسال OTP: ".$e->getMessage()];
    }

    // Persist hash (not plaintext)
    $stmt = $pdo->prepare("INSERT INTO user_otps (mobile, code_hash, reference_id, expires_at, ip) VALUES (:m, :h, :ref, :exp, :ip)");
    $ok = $stmt->execute([
        ':m' => $mobile,
        ':h' => password_hash($code, PASSWORD_BCRYPT),
        ':ref' => $referenceId,
        ':exp' => date('Y-m-d H:i:s', time() + $expiry),
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);

    if (!$ok) return ['ok'=>false,'msg'=>'ثبت رکورد OTP با مشکل مواجه شد.'];

    // Store ephemeral in session for UX (do NOT store code)
    $_SESSION['otp_mobile'] = $mobile;
    $_SESSION['otp_sent_at'] = time();
    $_SESSION['otp_reference_id'] = $referenceId;

    // IMPORTANT: SDK itself delivers the randomly generated code according to template.
    // We must update the record with our generated code? Actually MSGway generates & sends.
    // For verify we only need to call verifyOTP with (userInput, mobile).
    return ['ok'=>true,'msg'=>'کد تایید ارسال شد','reference_id'=>$referenceId];
}

/**
 * Verify a code. On success, mark as VERIFIED and return true.
 */
function otp_verify(PDO $pdo, Client $client, string $mobile, string $code): array {
    $mobile = preg_replace('/\D+/', '', $mobile);
    $code = trim($code);

    // 1) Call MSGway verify
    try {
        $ver = $client->verifyOTP($code, $mobile);
        $status = $ver['status'] ?? null;
        if ($status !== 'verified' && $status !== true && $status !== 1) {
            // Continue local check as fallback
        } else {
            // mark verified
            $stmt = $pdo->prepare("UPDATE user_otps SET status='VERIFIED' WHERE mobile=:m ORDER BY id DESC LIMIT 1");
            $stmt->execute([':m' => $mobile]);
            return ['ok'=>true,'msg'=>'تایید شد'];
        }
    } catch (\Exception $e) {
        // ignore, we fallback to local hash check
    }

    // 2) Local fallback check (last pending & not expired)
    $stmt = $pdo->prepare("SELECT id, code_hash, expires_at, attempts FROM user_otps WHERE mobile=:m AND status='PENDING' ORDER BY id DESC LIMIT 1");
    $stmt->execute([':m' => $mobile]);
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    if (!$row) return ['ok'=>false,'msg'=>'کد یافت نشد؛ دوباره درخواست دهید.'];

    if (time() > strtotime($row['expires_at'])) {
        $pdo->prepare("UPDATE user_otps SET status='EXPIRED' WHERE id=:id")->execute([':id'=>$row['id']]);
        return ['ok'=>false,'msg'=>'کد منقضی شده است.'];
    }

    if (!password_verify($code, $row['code_hash'])) {
        $attempts = (int)$row['attempts'] + 1;
        $pdo->prepare("UPDATE user_otps SET attempts=:a WHERE id=:id")->execute([':a'=>$attempts, ':id'=>$row['id']]);
        if ($attempts >= 5) {
            $pdo->prepare("UPDATE user_otps SET status='BLOCKED' WHERE id=:id")->execute([':id'=>$row['id']]);
            return ['ok'=>false,'msg'=>'تعداد تلاش‌ها بیش از حد مجاز است.'];
        }
        return ['ok'=>false,'msg'=>'کد نادرست است.'];
    }

    $pdo->prepare("UPDATE user_otps SET status='VERIFIED' WHERE id=:id")->execute([':id'=>$row['id']]);
    return ['ok'=>true,'msg'=>'تایید شد'];
}
