
<?php
declare(strict_types=1);
use RS\MSGWAY\Client;
if(session_status()===PHP_SESSION_NONE) session_start();
function otp_generate(int $length=5):string{ $length=max(4,min($length,8)); $d='0123456789'; $o=''; for($i=0;$i<$length;$i++){$o.=$d[random_int(0,9)];} return $o; }
function otp_can_resend(PDO $pdo,string $m,int $s):bool{ $st=$pdo->prepare("SELECT created_at FROM user_otps WHERE mobile=:m ORDER BY id DESC LIMIT 1"); $st->execute([':m'=>$m]); $last=$st->fetchColumn(); if(!$last) return true; return (time()-strtotime((string)$last))>=$s; }
function otp_send(PDO $pdo,Client $c,string $m,int $tpl,int $len,int $exp,int $resend):array{
  $m=preg_replace('/\D+/','',$m); if(!$m) return ['ok'=>false,'msg'=>'شماره موبایل نامعتبر است.'];
  if(!otp_can_resend($pdo,$m,$resend)) return ['ok'=>false,'msg'=>'لطفاً بعداً دوباره تلاش کنید (محدودیت ارسال مجدد).'];
  $code=otp_generate($len);
  try{ $r=$c->sendOTP($m,$tpl); $ref=$r['referenceID']??null; }catch(\Throwable $e){ return ['ok'=>false,'msg'=>'خطا در ارسال OTP: '.$e->getMessage()]; }
  $st=$pdo->prepare("INSERT INTO user_otps (mobile, code_hash, reference_id, expires_at, ip) VALUES (:m,:h,:r,:e,:ip)");
  $ok=$st->execute([':m'=>$m,':h'=>password_hash($code,PASSWORD_BCRYPT),':r'=>$ref,':e'=>date('Y-m-d H:i:s',time()+$exp),':ip'=>$_SERVER['REMOTE_ADDR']??null]);
  if(!$ok) return ['ok'=>false,'msg'=>'ثبت رکورد OTP با مشکل مواجه شد.'];
  $_SESSION['otp_mobile']=$m; $_SESSION['otp_sent_at']=time(); $_SESSION['otp_reference_id']=$ref;
  return ['ok'=>true,'msg'=>'کد تایید ارسال شد','reference_id'=>(string)$ref];
}
function otp_verify(PDO $pdo,Client $c,string $m,string $code):array{
  $m=preg_replace('/\D+/','',$m); $code=trim($code);
  try{ $v=$c->verifyOTP($code,$m); $s=$v['status']??null; if($s==='verified'||$s===true||$s===1){ $pdo->prepare("UPDATE user_otps SET status='VERIFIED' WHERE mobile=:m ORDER BY id DESC LIMIT 1")->execute([':m'=>$m]); return ['ok'=>true,'msg'=>'تایید شد']; } }catch(\Throwable $e){}
  $st=$pdo->prepare("SELECT id, code_hash, expires_at, attempts FROM user_otps WHERE mobile=:m AND status='PENDING' ORDER BY id DESC LIMIT 1"); $st->execute([':m'=>$m]); $row=$st->fetch(PDO::FETCH_ASSOC);
  if(!$row) return ['ok'=>false,'msg'=>'کد یافت نشد؛ دوباره درخواست دهید.'];
  if(time()>strtotime((string)$row['expires_at'])){ $pdo->prepare("UPDATE user_otps SET status='EXPIRED' WHERE id=:id")->execute([':id'=>$row['id']]); return ['ok'=>false,'msg'=>'کد منقضی شده است.']; }
  if(!password_verify($code,(string)$row['code_hash'])){ $a=(int)$row['attempts']+1; $pdo->prepare("UPDATE user_otps SET attempts=:a WHERE id=:id")->execute([':a'=>$a,':id'=>$row['id']]); if($a>=5){ $pdo->prepare("UPDATE user_otps SET status='BLOCKED' WHERE id=:id")->execute([':id'=>$row['id']]); return ['ok'=>false,'msg'=>'تعداد تلاش‌ها بیش از حد مجاز است.']; } return ['ok'=>false,'msg'=>'کد نادرست است.']; }
  $pdo->prepare("UPDATE user_otps SET status='VERIFIED' WHERE id=:id")->execute([':id'=>$row['id']]); return ['ok'=>true,'msg'=>'تایید شد'];
}
