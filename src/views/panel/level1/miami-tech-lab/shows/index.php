<?php
use App\Repositories\MiamiTechShowsRepository;
use App\Services\LoginService;
use App\Utils\LocationUtils;
use App\Utils\MessageUtil;
use App\Utils\Router;
use App\Utils\TemplateResponse;
use App\Utils\UserContext;

$router = new Router();
$router->get(function () {
    $warning = null;
    try { $data = (new MiamiTechShowsRepository())->adminData(); }
    catch (Throwable $e) { $data=['shows'=>[],'episodes'=>[],'guests'=>[],'recordings'=>[]]; $warning='Install db/20260815_miami_tech_lab_shows.sql before creating records.'; }
    return TemplateResponse::render(__DIR__.'/index.twig',[...UserContext::get(),...$data,'warning'=>$warning]);
});
$router->post(function () {
    try {
        $user=LoginService::getSession();
        (new MiamiTechShowsRepository())->save(trim((string)($_POST['record_type']??'')),$_POST,$user->getId());
        MessageUtil::setMessage('Miami Tech Lab content saved.','success');
    } catch (Throwable $e) { MessageUtil::setMessage('Unable to save: '.$e->getMessage(),'error'); }
    LocationUtils::redirectInternal('panel/miami-tech-lab/shows');
});
$router->run();
