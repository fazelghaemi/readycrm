
<?php
// msgway_otp_module/includes/csrf.php
if (session_status() === PHP_SESSION_NONE) session_start();

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="'.htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8').'">';
}

function csrf_verify(): bool {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf_token'])) {
        http_response_code(400);
        echo 'درخواست نامعتبر (CSRF).';
        return false;
    }
    return true;
}
