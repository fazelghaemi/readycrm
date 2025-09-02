<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/auth.php';

// اگر کاربر لاگین نکرده است، به صفحه لاگین هدایت شود
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// اگر کاربر لاگین کرده است، به داشبورد هدایت شود
header('Location: dashboard.php');
exit();
?>

<?php define('READYCRM_UI', true); ?>
<?php require __DIR__ . '/includes/header.php'; ?>
<div class="main-layout">
  <?php require __DIR__ . '/includes/sidebar.php'; ?>
  <div class="main-content">
    <?php require __DIR__ . '/includes/topbar.php'; ?>
    <?php require __DIR__ . '/pages/dashboard.php'; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
