
<?php
declare(strict_types=1); header('Content-Type:text/html; charset=utf-8');
function flash($ok,$t){echo '<div style="padding:8px 12px;border-radius:10px;margin:6px 0;'.($ok?'background:#e7fff6;color:#064e3b;border:1px solid #b6f3df':'background:#ffeaea;color:#7f1d1d;border:1px solid #ffc9c9').'">'.$t.'</div>';}
flash(version_compare(PHP_VERSION,'7.4','>='),'PHP '+PHP_VERSION.' (>=7.4)');
$pdo=null; $db=__DIR__.'/../config/database.php'; if(is_file($db)){ require_once $db; if(isset($pdo)&&$pdo instanceof PDO) flash(true,'اتصال دیتابیس برقرار است'); else flash(false,'اتصال دیتابیس برقرار نیست'); } else { flash(false,'فایل دیتابیس یافت نشد: ../config/database.php'); }
$hasSettings=false; $hasOtps=false; if($pdo instanceof PDO){ try{ $hasSettings=(bool)$pdo->query("SHOW TABLES LIKE 'settings'")->fetchColumn(); $hasOtps=(bool)$pdo->query("SHOW TABLES LIKE 'user_otps'")->fetchColumn(); }catch(Throwable $e){} }
flash($hasSettings,'settings '+($hasSettings?'OK':'نیست — install.sql را ایمپورت کنید')); flash($hasOtps,'user_otps '+($hasOtps?'OK':'نیست — install.sql را ایمپورت کنید'));
require_once __DIR__.'/includes/msgway_client.php'; $apiKey=''; if($pdo instanceof PDO && $hasSettings){ $st=$pdo->prepare("SELECT `value` FROM settings WHERE `key`='msgway_api_key' LIMIT 1"); $st->execute(); $apiKey=(string)($st->fetchColumn()?:''); }
flash($apiKey!=='','API Key '+($apiKey?'تنظیم شده':'خالی است — از admin_msgway_settings.php تنظیم کنید'));
try{ $c=new RS\MSGWAY\Client($apiKey?:'dummy'); $ok=method_exists($c,'sendOTP')&&method_exists($c,'verifyOTP')&&method_exists($c,'getStatus'); flash($ok,'SDK/Loader OK'); }catch(Throwable $e){ flash(false,'SDK/Loader: '+$e->getMessage()); }
echo '<hr><div style="opacity:.7;font:13px tahoma">آیتم‌های قرمز را برطرف کنید و صفحه را رفرش کنید.</div>';
