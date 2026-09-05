<?php
use App\Repositories\Connection;
use App\Services\LoginService;
use App\Utils\LocationUtils;
use App\Utils\Router;
use App\Utils\TemplateResponse;
use App\Utils\CSRF;
$router=new Router();$db=new Connection();
$router->get(function()use($db){
 $user=LoginService::getSession();if(!$user||(int)$user->getLevel()!==1){http_response_code(403);return 'Forbidden';}
 $db->query("SELECT ve.id,ve.name,ve.start_date,v.name venue_name,te.status tech_status,te.capacity FROM venue_events ve JOIN venues v ON v.id=ve.venue_id AND v.user_id=2 LEFT JOIN tech_lab_events te ON te.venue_event_id=ve.id AND te.tenant_key='miamitechlab' WHERE ve.start_date>=NOW() ORDER BY ve.start_date LIMIT 100");$events=$db->fetchAll();
 $db->query("SELECT r.*,u.name,u.lastname,u.email FROM tech_lab_member_requests r JOIN users u ON u.id=r.user_id WHERE r.status<>'DRAFT' ORDER BY r.updated_at DESC LIMIT 100");$requests=$db->fetchAll();
 $db->query("SELECT m.*,u.name,u.lastname,u.email,e.status entitlement_status,e.expires_at FROM ecosystem_memberships m JOIN users u ON u.id=m.user_id LEFT JOIN ecosystem_entitlements e ON e.membership_id=m.id AND e.product_key='ophyra' WHERE m.tenant_key='miamitechlab' ORDER BY m.joined_at DESC LIMIT 200");$members=$db->fetchAll();
 $db->query("SELECT c.id,c.title,c.slug,IF(f.id IS NULL,0,f.is_active) is_featured,COALESCE(f.sort_order,100) sort_order FROM cms_contents c LEFT JOIN tech_lab_featured_content f ON f.cms_content_id=c.id AND f.tenant_key='miamitechlab' WHERE c.id_owner=2 AND c.site_key='miamitechlab' AND c.status='PUBLISHED' AND COALESCE(c.content_type,IF(c.type='post','blog',c.type))='blog' ORDER BY is_featured DESC,sort_order,COALESCE(c.published_at,c.created_at) DESC LIMIT 100");$articles=$db->fetchAll();
 return TemplateResponse::render(__DIR__.'/index.twig',compact('events','requests','members','articles'));
});
$router->post(function()use($db){
 $user=LoginService::getSession();if(!$user||(int)$user->getLevel()!==1){http_response_code(403);return;}CSRF::validateCSRF();$action=(string)($_POST['action']??'event');
 if($action==='request'){$status=(string)($_POST['status']??'IN_REVIEW');if(in_array($status,['SUBMITTED','IN_REVIEW','CONTACTED','CLOSED'],true)){$db->query("UPDATE tech_lab_member_requests r JOIN ecosystem_memberships m ON m.id=r.membership_id AND m.tenant_key='miamitechlab' SET r.status=:status,r.assigned_to=:user,r.updated_at=NOW() WHERE r.id=:id");foreach([':status'=>$status,':user'=>(int)$user->getId(),':id'=>(int)($_POST['request_id']??0)] as $k=>$v)$db->bind($k,$v);$db->execute();}}
 elseif($action==='featured'){$content=(int)($_POST['content_id']??0);$active=isset($_POST['is_active'])?1:0;$sort=max(1,(int)($_POST['sort_order']??100));$db->query("INSERT INTO tech_lab_featured_content(cms_content_id,tenant_key,sort_order,is_active) SELECT c.id,'miamitechlab',:sort,:active FROM cms_contents c WHERE c.id=:content AND c.id_owner=2 AND c.site_key='miamitechlab' ON DUPLICATE KEY UPDATE sort_order=VALUES(sort_order),is_active=VALUES(is_active)");foreach([':sort'=>$sort,':active'=>$active,':content'=>$content] as $k=>$v)$db->bind($k,$v);$db->execute();}
 else{$event=(int)($_POST['event_id']??0);$status=(string)($_POST['status']??'DRAFT');if(in_array($status,['DRAFT','PUBLISHED','CANCELLED'],true)){$db->query("INSERT INTO tech_lab_events(venue_event_id,tenant_key,capacity,status,created_by) SELECT ve.id,'miamitechlab',:capacity,:status,:user FROM venue_events ve JOIN venues v ON v.id=ve.venue_id AND v.user_id=2 WHERE ve.id=:event ON DUPLICATE KEY UPDATE capacity=VALUES(capacity),status=VALUES(status),updated_at=NOW()");foreach([':capacity'=>($_POST['capacity']??'')===''?null:(int)$_POST['capacity'],':status'=>$status,':user'=>(int)$user->getId(),':event'=>$event] as $k=>$v)$db->bind($k,$v);$db->execute();}}
 LocationUtils::redirectInternal('panel/miami-tech-lab/member-experience');
});
$router->run();
