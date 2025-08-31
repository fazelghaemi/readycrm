
<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    ini_set('session.use_strict_mode','1'); ini_set('session.use_only_cookies','1'); ini_set('session.cookie_httponly','1');
    $isHttps=!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS'])!=='off'; if($isHttps) ini_set('session.cookie_secure','1');
    if(session_status()===PHP_SESSION_NONE){@session_start();}
    if(!headers_sent()){header('X-Frame-Options: SAMEORIGIN'); header('X-Content-Type-Options: nosniff'); header('Referrer-Policy: strict-origin-when-cross-origin');}
}
if(!function_exists('msgway_log')){
  function msgway_log(string $m,array $c=[]):void{
    $d=__DIR__.'/../storage/logs'; if(!is_dir($d)) @mkdir($d,0775,true);
    @file_put_contents($d.'/module.log', json_encode(['ts'=>date('c'),'msg'=>$m,'ctx'=>$c],JSON_UNESCAPED_UNICODE).PHP_EOL, FILE_APPEND);
  }
}
