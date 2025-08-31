<?php
require __DIR__.'/config/database.php';
header('Content-Type:text/plain; charset=utf-8');
echo "Trying as user: ".DB_USER." on DB: ".DB_NAME." host: ".DB_HOST."\n";
try {
  $stmt = $pdo->query("SELECT NOW() as now");
  echo "OK. DB Time: ".$stmt->fetch()['now']."\n";
} catch (Throwable $e) {
  echo "ERROR: ".$e->getMessage()."\n";
}
