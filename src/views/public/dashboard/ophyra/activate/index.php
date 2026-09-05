<?php
use App\Services\LoginService;
use App\Services\TechLabMembershipService;
use App\Utils\LocationUtils;
$user=LoginService::getSession();
if(!$user){LocationUtils::redirectInternal('login?return_url=%2Fdashboard%2Fophyra%2Factivate');}
try{header('Location: '.(new TechLabMembershipService())->activateOphyra((int)$user->getId()),true,302);exit;}catch(Throwable $e){http_response_code(422);echo htmlspecialchars($e->getMessage(),ENT_QUOTES,'UTF-8');}
