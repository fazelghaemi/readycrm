<?php
/**
 * مرحله 5: اتمام نصب
 */

if (!isset($_SESSION['admin_created']) || !$_SESSION['admin_created']) {
    header('Location: ?step=4');
    exit;
}

// ایجاد فایل قفل
$lock_created = create_install_lock();

// پاکسازی session
session_destroy();
?>

<h2 class="section-title">🎉 نصب با موفقیت انجام شد!</h2>

<div class="alert alert-success" style="font-size: 1.1rem; padding: 25px;">
    <strong>✅ تبریک!</strong><br>
    سیستم CRM نسخه 2.0 با موفقیت نصب شد و آماده استفاده است.
</div>

<div style="background: var(--gray-50); padding: 25px; border-radius: var(--radius-md); margin: 30px 0;">
    <h3 style="color: var(--brand-primary); margin-bottom: 20px;">📝 اطلاعات مهم:</h3>
    
    <div style="background: white; padding: 20px; border-radius: var(--radius-md); margin-bottom: 15px;">
        <strong style="color: var(--gray-700);">🔗 آدرس ورود:</strong><br>
        <code style="font-size: 1.1rem; color: var(--brand-primary);">
            <?php echo rtrim(dirname($_SERVER['PHP_SELF']), '/install'); ?>/public/login.php
        </code>
    </div>
    
    <div style="background: white; padding: 20px; border-radius: var(--radius-md);">
        <strong style="color: var(--gray-700);">👤 کاربر مدیر شما ایجاد شد</strong><br>
        <span style="color: var(--gray-600);">می‌توانید با اطلاعاتی که وارد کردید وارد سیستم شوید.</span>
    </div>
</div>

<div class="alert alert-warning">
    <strong>🔒 امنیت مهم:</strong><br>
    برای افزایش امنیت، لطفاً موارد زیر را انجام دهید:
    <ol style="margin: 10px 0 0 20px; line-height: 2;">
        <li><strong>حذف پوشه install:</strong> پوشه <code>/install</code> را به طور کامل از سرور حذف کنید.</li>
        <li><strong>بررسی دسترسی‌ها:</strong> مطمئن شوید پوشه <code>/private</code> از خارج قابل دسترسی نیست.</li>
        <li><strong>تنظیمات .htaccess:</strong> فایل‌های <code>.htaccess</code> را بررسی کنید.</li>
        <li><strong>تغییر رمز:</strong> اگر در محیط عمومی نصب کردید، رمز عبور را تغییر دهید.</li>
    </ol>
</div>

<div style="background: var(--brand-light); padding: 20px; border-radius: var(--radius-md); margin: 30px 0;">
    <h4 style="color: var(--brand-primary-dark); margin-bottom: 15px;">🚀 مراحل بعدی:</h4>
    <ul style="line-height: 2; color: var(--gray-700);">
        <li>ورود به سیستم با کاربر مدیر</li>
        <li>تنظیمات اولیه سیستم از بخش Settings</li>
        <li>اتصال به WooCommerce (اختیاری)</li>
        <li>اتصال به GapGPT برای قابلیت‌های AI (اختیاری)</li>
        <li>ایجاد کاربران و تخصیص نقش‌ها</li>
    </ul>
</div>

<?php if ($lock_created): ?>
<div class="alert alert-info">
    <strong>✅ قفل نصب ایجاد شد</strong><br>
    فایل <code>.install.lock</code> برای جلوگیری از نصب مجدد ایجاد شده است.
</div>
<?php endif; ?>

<div class="btn-group" style="justify-content: center;">
    <a href="../public/login.php" class="btn btn-primary" style="font-size: 1.1rem; padding: 15px 40px;">
 سیستم CRM
    </a>
</div>

<div style="text-align: center; margin-top: 40px; padding: 20px; color: var(--gray-600);">
    <p style="margin-bottom: 10px;">💚 از اینکه CRM V2 را انتخاب کردید متشکریم!</p>
    <p>
        برای پشتیبانی: 
        <a href="https://readystudio.ir target="_blank" style="color: var(--brand-primary); text-decoration: none;">
            readystudio.ir
        </a>
    </p>
</div>
