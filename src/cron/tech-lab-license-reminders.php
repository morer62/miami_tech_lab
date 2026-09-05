<?php
require_once dirname(__DIR__,2).'/vendor/autoload.php';
use App\Repositories\Connection;
use App\Services\EmailService;
use Dotenv\Dotenv;
$root=dirname(__DIR__,2);if(is_file($root.'/.env'))Dotenv::createImmutable($root)->safeLoad();
$db=new Connection();
$db->query("UPDATE ecosystem_entitlements e JOIN ecosystem_workspaces w ON w.membership_id=e.membership_id SET e.status='EXPIRED',w.status='READ_ONLY' WHERE e.product_key='ophyra' AND e.source_tenant='miamitechlab' AND e.status='ACTIVE' AND e.expires_at<NOW()");$db->execute();
foreach([90=>'notice_90_at',30=>'notice_30_at',7=>'notice_7_at'] as $days=>$column){
 $db->query("SELECT e.id,u.email,u.name,e.expires_at FROM ecosystem_entitlements e JOIN users u ON u.id=e.user_id WHERE e.product_key='ophyra' AND e.source_tenant='miamitechlab' AND e.status='ACTIVE' AND {$column} IS NULL AND DATE(e.expires_at)=DATE_ADD(CURDATE(),INTERVAL {$days} DAY)");$rows=$db->fetchAll();
 foreach($rows as $row){try{(new EmailService())->sendSimpleEmail((string)$row->email,"Your Tech Lab Miami Ophyra access has {$days} days remaining","<p>Hi ".htmlspecialchars((string)$row->name,ENT_QUOTES,'UTF-8').",</p><p>Your included Ophyra workspace access runs through <strong>".htmlspecialchars((string)$row->expires_at,ENT_QUOTES,'UTF-8')."</strong>. Your data will remain available in read-only mode if the license expires.</p><p><a href=\"https://techlabmiami.com/dashboard\">Open your member dashboard</a></p>");$db->query("UPDATE ecosystem_entitlements SET {$column}=NOW() WHERE id=:id AND {$column} IS NULL");$db->bind(':id',(int)$row->id);$db->execute();}catch(Throwable $e){error_log('Tech Lab license reminder failed: '.$e->getMessage());}}
}
echo "Tech Lab license lifecycle processed.\n";
