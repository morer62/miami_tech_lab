<?php

$memberUser = \App\Services\LoginService::getSession();
if (!$memberUser) {
    \App\Utils\LocationUtils::redirectInternal('login?return_url=' . rawurlencode((string) ($_SERVER['REQUEST_URI'] ?? '/dashboard')));
}
(new \App\Services\TechLabMembershipService())->ensureMembership((int) $memberUser->getId());

use App\Utils\Router;
use App\Utils\TemplateResponse;

$router = new Router();

$router->get(function () {
    return TemplateResponse::render(__DIR__ . "/index.twig");
});

try {
    $router->run();
} catch (Exception $e) {
    echo $e->getMessage();
}
