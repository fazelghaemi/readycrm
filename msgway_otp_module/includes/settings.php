
<?php
declare(strict_types=1);
if(!function_exists('setting_get')){
  function setting_get(PDO $pdo,string $key,$default=''){
    $st=$pdo->prepare("SELECT `value` FROM settings WHERE `key`=:k LIMIT 1"); $st->execute([':k'=>$key]);
    $v=$st->fetchColumn(); return $v!==false ? $v : $default;
  }
}
if(!function_exists('setting_set')){
  function setting_set(PDO $pdo,string $key,$value):bool{
    $st=$pdo->prepare("INSERT INTO settings(`key`,`value`) VALUES(:k,:v) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
    return $st->execute([':k'=>$key,':v'=>$value]);
  }
}
