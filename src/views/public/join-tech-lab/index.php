<?php
use App\Services\LoginService;
use App\Services\TechLabMembershipService;
use App\Utils\LocationUtils;
use App\Utils\Router;
$router=new Router();
$router->get(function(){
    $user=LoginService::getSession();if(!$user)LocationUtils::redirectInternal('login');
    (new TechLabMembershipService())->ensureMembership((int)$user->getId());
    LocationUtils::redirectInternal('dashboard');
});
$router->post(function(){
    $user=LoginService::getSession();if(!$user)LocationUtils::redirectInternal('login');
    (new TechLabMembershipService())->ensureMembership((int)$user->getId());LocationUtils::redirectInternal('dashboard');
});
$router->run();
