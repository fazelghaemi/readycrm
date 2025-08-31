<?php
// msgway_otp_module/includes/settings.php
declare(strict_types=1);

/** @param PDO $pdo */
function setting_get(PDO $pdo, string $key, $default = '') {
    $stmt = $pdo->prepare("SELECT `value` FROM settings WHERE `key` = :k LIMIT 1");
    $stmt->execute([':k' => $key]);
    $val = $stmt->fetchColumn();
    return $val !== false ? $val : $default;
}

/** @param PDO $pdo */
function setting_set(PDO $pdo, string $key, $value): bool {
    $stmt = $pdo->prepare("INSERT INTO settings(`key`,`value`) VALUES(:k,:v)
        ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
    return $stmt->execute([':k' => $key, ':v' => $value]);
}
