<?php
/**
 * مرحله 1: بررسی الزامات سیستم
 */

// بررسی نیازمندی‌ها
$php_ok = check_php_version();
$extensions_missing = check_extensions();
$permissions_issue = check_write_permissions();

// بررسی تنظیمات PHP
$upload_max = ini_get('upload_max_filesize');
$post_max = ini_get('post_max_size');
$memory_limit = ini_get('memory_limit');
$max_execution_time = ini_get('max_execution_time');

// وضعیت کلی
$all_ok = $php_ok && empty($extensions_missing) && empty($permissions_issue);
?>

<h2 class="section-title">🔍 بررسی الزامات سیستم</h2>

<p class="section-description">
    قبل از شروع نصب، باید مطمئن شویم که سرور شما تمام نیازمندی‌های CRM V2 را برآورده می‌کند.
</p>

<!-- نسخه PHP -->
<div class="checklist">
    <div class="checklist-item <?php echo $php_ok ? 'success' : 'error'; ?>">
        <div class="checklist-icon">
            <?php echo $php_ok ? '✅' : '❌'; ?>
        </div>
        <div class="checklist-content">
            <div class="checklist-title">نسخه PHP</div>
            <div class="checklist-description">
                نسخه فعلی: <strong><?php echo PHP_VERSION; ?></strong>
                <?php if (!$php_ok): ?>
                    <br><span style="color: #c62828;">حداقل نسخه 8.0.0 مورد نیاز است!</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- افزونه‌های PHP -->
    <div class="checklist-item <?php echo empty($extensions_missing) ? 'success' : 'error'; ?>">
        <div class="checklist-icon">
            <?php echo empty($extensions_missing) ? '✅' : '❌'; ?>
        </div>
        <div class="checklist-content">
            <div class="checklist-title">افزونه‌های PHP</div>
            <div class="checklist-description">
                <?php if (empty($extensions_missing)): ?>
                    تمام افزونه‌های مورد نیاز نصب شده‌اند.
                <?php else: ?>
                    افزونه‌های زیر موجود نیستند:
                    <strong style="color: #c62828;"><?php echo implode(', ', $extensions_missing); ?></strong>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- دسترسی نوشتن -->
    <div class="checklist-item <?php echo empty($permissions_issue) ? 'success' : 'error'; ?>">
        <div class="checklist-icon">
            <?php echo empty($permissions_issue) ? '✅' : '❌'; ?>
        </div>
        <div class="checklist-content">
            <div class="checklist-title">دسترسی نوشتن فایل</div>
            <div class="checklist-description">
                <?php if (empty($permissions_issue)): ?>
                    تمام پوشه‌ها قابل نوشتن هستند.
                <?php else: ?>
                    پوشه‌های زیر قابل نوشتن نیستند:<br>
                    <?php foreach ($permissions_issue as $path): ?>
                        <code><?php echo $path; ?></code><br>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- جدول تنظیمات PHP -->
<h3 style="margin-top: 30px; color: var(--gray-700);">⚙️ تنظیمات PHP</h3>
<table class="info-table">
    <thead>
        <tr>
            <th>تنظیم</th>
            <th>مقدار فعلی</th>
            <th>توصیه شده</th>
            <th>وضعیت</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>upload_max_filesize</td>
            <td><?php echo $upload_max; ?></td>
            <td>64M یا بیشتر</td>
            <td><?php echo (int)$upload_max >= 64 ? '✅' : '⚠️'; ?></td>
        </tr>
        <tr>
            <td>post_max_size</td>
            <td><?php echo $post_max; ?></td>
            <td>64M یا بیشتر</td>
            <td><?php echo (int)$post_max >= 64 ? '✅' : '⚠️'; ?></td>
        </tr>
        <tr>
            <td>memory_limit</td>
            <td><?php echo $memory_limit; ?></td>
            <td>256M یا بیشتر</td>
            <td><?php echo (int)$memory_limit >= 256 ? '✅' : '⚠️'; ?></td>
        </tr>
        <tr>
            <td>max_execution_time</td>
            <td><?php echo $max_execution_time; ?> ثانیه</td>
            <td>60 ثانیه یا بیشتر</td>
            <td><?php echo $max_execution_time >= 60 ? '✅' : '⚠️'; ?></td>
        </tr>
    </tbody>
</table>

<?php if (!$all_ok): ?>
<div class="alert alert-error" style="margin-top: 30px;">
    <strong>⚠️ خطا!</strong><br>
    لطفاً مشکلات بالا را رفع کنید و سپس این صفحه را رفرش کنید.
</div>
<?php endif; ?>

<!-- دکمه‌ها -->
<div class="btn-group">
    <a href="javascript:location.reload();" class="btn btn-secondary">
        🔄 بررسی مجدد
    </a>
    
    <?php if ($all_ok): ?>
    <a href="?step=2" class="btn btn-primary">
        بعدی: اطلاعات دیتابیس
        ⬅️
    </a>
    <?php else: ?>
    <button class="btn btn-primary" disabled style="opacity: 0.5; cursor: not-allowed;">
        ابتدا مشکلات را رفع کنید
    </button>
    <?php endif; ?>
</div>
