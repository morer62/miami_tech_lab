<?php

use App\Utils\Router;
use App\Utils\TemplateResponse;
$router = new Router();

$router->get(function () {
    $GLOBALS['miami_tech_route'] = '';
    return TemplateResponse::render(dirname(__DIR__, 2) . '/pages/miami-tech-hub/index.twig', require dirname(__DIR__, 2) . '/pages/miami-tech-hub/data.php');
});

try {
    $router->run();
} catch (Exception $e) {
    echo $e->getMessage();
}
