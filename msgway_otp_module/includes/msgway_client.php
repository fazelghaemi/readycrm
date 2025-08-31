
<?php
declare(strict_types=1);
namespace RS\MSGWAY; use Exception;
class Client{
  private string $apiKey; private $sdk=null;
  public function __construct(string $apiKey){
    $this->apiKey=$apiKey;
    foreach([__DIR__.'/../vendor/autoload.php',__DIR__.'/../../vendor/autoload.php',$_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php'] as $al){ if(is_file($al)){@require_once $al;} }
    if(class_exists('\\MessageWay\\Api\\MessageWayAPI')){ $this->sdk=new \MessageWay\Api\MessageWayAPI($this->apiKey); return; }
    foreach([__DIR__.'/MessageWayPHP/src/MessageWayAPI.php', $_SERVER['DOCUMENT_ROOT'].'/msgway_otp_module/includes/MessageWayPHP/src/MessageWayAPI.php'] as $m){
      if(is_file($m)){ require_once $m; if(class_exists('\\MessageWay\\Api\\MessageWayAPI')){ $this->sdk=new \MessageWay\Api\MessageWayAPI($this->apiKey); return; } }
    }
    throw new Exception('MSGway PHP SDK پیدا نشد. پوشهٔ MessageWayPHP را در msgway_otp_module/includes قرار دهید.');
  }
  public function sendOTP(string $mobile,int $templateID):array{ return $this->sdk->sendViaSMS($mobile,$templateID); }
  public function verifyOTP(string $otp,string $mobile):array{ return $this->sdk->verifyOTP($otp,$mobile); }
  public function getStatus(string $referenceID):array{ return $this->sdk->getStatus($referenceID); }
  public function getBalance(){ return $this->sdk->getBalance(); }
}
