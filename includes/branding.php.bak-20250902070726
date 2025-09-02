<?php
/**
 * includes/branding.php
 * خواندن تنظیمات برند از جدول settings و چاپ CSS Variables
 */

declare(strict_types=1);

/** @param PDO $pdo */
function brand_setting(PDO $pdo, string $key, string $default): string {
    $stmt = $pdo->prepare("SELECT `value` FROM settings WHERE `key`=:k LIMIT 1");
    $stmt->execute([':k' => $key]);
    $val = $stmt->fetchColumn();
    return ($val !== false) ? (string)$val : $default;
}

/** @param PDO $pdo */
function brand_print_css_vars(PDO $pdo): void {
    $primary   = brand_setting($pdo, 'brand_primary_color',   '#00b0a4');
    $secondary = brand_setting($pdo, 'brand_secondary_color', '#098b82');
    $midnight  = brand_setting($pdo, 'brand_midnight_color',  '#181c24');
    $bg        = brand_setting($pdo, 'brand_bg_color',        '#f6f9fa');
    $text      = brand_setting($pdo, 'brand_text_color',      '#1b1f2b');

    echo '<style>:root{--rs-primary:' . htmlspecialchars($primary,   ENT_QUOTES, 'UTF-8') .
         ';--rs-primary-dark:'     . htmlspecialchars($secondary, ENT_QUOTES, 'UTF-8') .
         ';--rs-midnight:'         . htmlspecialchars($midnight,  ENT_QUOTES, 'UTF-8') .
         ';--rs-bg:'               . htmlspecialchars($bg,        ENT_QUOTES, 'UTF-8') .
         ';--rs-text:'             . htmlspecialchars($text,      ENT_QUOTES, 'UTF-8') .
         ';}</style>';
}
