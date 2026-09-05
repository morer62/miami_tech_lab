<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$loader = new Twig\Loader\FilesystemLoader(dirname(__DIR__) . '/src/views');
$twig = new Twig\Environment($loader);
foreach (['path', 'asset_for', 'trans', 'get_csrf', 'csrf_token', 'contain_permission', 'getTreeRoutes'] as $name) {
    $twig->addFunction(new Twig\TwigFunction($name, static fn (): string => ''));
}
foreach (['truncate', 'html_to_text', 'json_decode'] as $name) {
    $twig->addFilter(new Twig\TwigFilter($name, static fn ($value) => $value));
}

$templates = [
    'public/dashboard/index.twig',
    'public/join-tech-lab/index.twig',
    'panel/level1/miami-tech-lab/member-experience/index.twig',
    'public/pages/miami-tech-hub/index.twig',
];

foreach ($templates as $template) {
    $twig->load($template);
    echo 'PASS: ' . $template . "\n";
}
