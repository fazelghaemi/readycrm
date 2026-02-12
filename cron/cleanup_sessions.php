<?php
/**
 * Session Cleanup Cron Job
 * Run: */5 * * * * php /path/to/cron/cleanup_sessions.php
 */

require_once __DIR__ . '/../private/config.php';

// پاکسازی سشن‌های قدیمی
session_start();
session_gc();

echo "Session cleanup completed at " . date('Y-m-d H:i:s') . "\n";
