<?php

use App\Services\LoginService;
use App\Services\TechLabMembershipService;
use App\Utils\JsonResponse;
use App\Utils\LocationUtils;
use App\Utils\Router;
use App\Utils\TemplateResponse;

$router=new Router();
$router->get(function(){
    $user=LoginService::getSession();
    if(!$user){LocationUtils::redirectInternal('login?return_url=%2Fdashboard');}
    $service=new TechLabMembershipService();
    if(!$service->membershipFor((int)$user->getId())){LocationUtils::redirectInternal('join-tech-lab');}
    return TemplateResponse::render(__DIR__.'/index.twig',['user'=>$user]+$service->dashboardData((int)$user->getId()));
});
$router->post(function(){
    $user=LoginService::getSession();
    if(!$user)return JsonResponse::createResponse(['success'=>false,'message'=>'Authentication required.'],401);
    $service=new TechLabMembershipService();
    try{
        $action=(string)($_POST['action']??'');
        if($action==='rsvp')$service->rsvp((int)$user->getId(),(int)($_POST['event_id']??0));
        elseif($action==='save_request')$service->saveRequest((int)$user->getId(),(string)($_POST['request_type']??''),(int)($_POST['step']??1),json_decode((string)($_POST['payload']??'{}'),true)?:[],false);
        elseif($action==='submit_request')$service->saveRequest((int)$user->getId(),(string)($_POST['request_type']??''),(int)($_POST['step']??1),json_decode((string)($_POST['payload']??'{}'),true)?:[],true);
        elseif($action==='save_tool')$service->saveToolResult((int)$user->getId(),(string)($_POST['tool_key']??''),json_decode((string)($_POST['input']??'{}'),true)?:[],json_decode((string)($_POST['result']??'{}'),true)?:[]);
        else throw new RuntimeException('Unknown action.');
        return JsonResponse::createResponse(['success'=>true]);
    }catch(Throwable $e){return JsonResponse::createResponse(['success'=>false,'message'=>$e->getMessage()],422);}
});
$router->run();
