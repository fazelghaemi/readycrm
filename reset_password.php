<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php'; // For sanitizeInput
require_once __DIR__ . '/includes/auth.php'; // For updateUserPassword

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (empty($token)) {
    header('Location: login.php?error=notoken');
    exit();
}

try {
    $db = new Database();
    $pdo = $db->getConnection();
} catch (Exception $e) {
    die("Database connection error.");
}

// Verify the token
$token_hash = hash('sha256', $token);
$stmt = $pdo->prepare(
    "SELECT * FROM password_resets WHERE token_hash = ? AND is_used = FALSE AND expires_at > NOW()"
);
$stmt->execute([$token_hash]);
$reset_request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reset_request) {
    // Redirect with an error if token is invalid, used, or expired
    header('Location: login.php?error=invalidtoken');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($csrf_token)) {
        $error = 'Invalid request.';
    } elseif (empty($password) || $password !== $password_confirm) {
        $error = 'Passwords do not match or are empty.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        // Update the user's password
        // Assumes you have a function to get user by mobile and another to update password
        $mobile = $reset_request['mobile'];
        $user = getUserByMobile($pdo, $mobile); // You need to create this function

        if ($user) {
            $update_success = updateUserPassword($pdo, $user['id'], $password); // Assumes updateUserPassword in auth.php
            if ($update_success) {
                // Invalidate the token
                $pdo->prepare("UPDATE password_resets SET is_used = TRUE WHERE id = ?")->execute([$reset_request['id']]);
                // Redirect to login with a success message
                header('Location: login.php?reset=success');
                exit();
            } else {
                $error = 'Could not update password. Please try again.';
            }
        } else {
            $error = 'User associated with this mobile not found.';
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تنظیم رمز عبور جدید</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- Using the same styles as the login page -->
    <style>
        :root { --brand-primary: #00b0a4; --brand-danger: #ef4444; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Vazirmatn', sans-serif; }
        html, body { height: 100%; }
        body { background: #0a0a0a; display: flex; align-items: center; justify-content: center; }
        .login-container { background: rgba(30, 30, 30, 0.35); backdrop-filter: blur(25px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 25px; padding: 45px 40px; width: 100%; max-width: 420px; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4); z-index: 10; }
        .login-title { background: linear-gradient(135deg, #fff, #e0e0e0); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-size: 32px; font-weight: 700; text-align: center; margin-bottom: 35px; }
        .alerts { margin-bottom: 18px; font-size: 14px; padding: 12px 15px; border-radius: 12px; text-align: center; color: #fff; border: 1px solid; }
        .alert-danger { background: rgba(220, 38, 38, 0.3); border-color: rgba(220, 38, 38, 0.5); }
        .form-group { margin-bottom: 22px; }
        .form-label { display: block; color: rgba(255, 255, 255, 0.9); font-size: 15px; margin-bottom: 8px; }
        .form-input { width: 100%; padding: 16px 20px; background: rgba(20, 20, 20, 0.7); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 15px; color: white; font-size: 16px; }
        .login-btn { width: 100%; padding: 16px; background: #fff; border: none; border-radius: 15px; color: #0a0a0a; font-size: 18px; font-weight: 700; cursor: pointer; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="login-container">
        <h1 class="login-title">رمز عبور جدید</h1>
        <?php if(!empty($error)): ?>
            <div class="alerts alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <div class="form-group">
                <label for="password" class="form-label">رمز عبور جدید</label>
                <input type="password" id="password" name="password" class="form-input" required>
            </div>
            <div class="form-group">
                <label for="password_confirm" class="form-label">تکرار رمز عبور جدید</label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-input" required>
            </div>
            <button type="submit" class="login-btn">ذخیره رمز عبور</button>
        </form>
    </div>
</body>
</html>
