<?php
use App\Services\LoginService;
use App\Services\TechLabMembershipService;
use App\Utils\LocationUtils;
use App\Utils\Router;
use App\Utils\TemplateResponse;
$router=new Router();
$router->get(function(){
    $user=LoginService::getSession();if(!$user)LocationUtils::redirectInternal('login');
    $service=new TechLabMembershipService();if($service->membershipFor((int)$user->getId()))LocationUtils::redirectInternal('dashboard');
    return TemplateResponse::render(__DIR__.'/index.twig',['user'=>$user]);
});
$router->post(function(){
    $user=LoginService::getSession();if(!$user)LocationUtils::redirectInternal('login');
    (new TechLabMembershipService())->enroll((int)$user->getId());LocationUtils::redirectInternal('dashboard');
});
$router->run();
