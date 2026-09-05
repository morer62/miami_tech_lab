<?php
use App\Utils\Router;
use App\Utils\TemplateResponse;
use App\Repositories\Connection;

$router = new Router();
$router->get(function () {
    $data = require __DIR__ . '/data.php';
    $data['featuredArticles'] = [];
    try {
        $db = new Connection();
        $db->query("SELECT c.title,c.slug,c.excerpt,c.featured_image_url,cat.name category_name FROM cms_contents c LEFT JOIN cms_categories cat ON cat.id=c.id_cms_category LEFT JOIN tech_lab_featured_content f ON f.cms_content_id=c.id AND f.tenant_key='miamitechlab' AND f.is_active=1 WHERE c.id_owner=2 AND c.site_key='miamitechlab' AND c.status='PUBLISHED' AND COALESCE(c.content_type,IF(c.type='post','blog',c.type))='blog' ORDER BY (f.id IS NOT NULL) DESC,f.sort_order,COALESCE(c.published_at,c.created_at) DESC LIMIT 3");
        $data['featuredArticles'] = $db->fetchAll();
    } catch (\Throwable $ignored) {
        // Fall back to recency if the optional featured-content table is not yet
        // available in an environment that has already published blog articles.
        try {
            $db = new Connection();
            $db->query("SELECT c.title,c.slug,c.excerpt,c.featured_image_url,cat.name category_name FROM cms_contents c LEFT JOIN cms_categories cat ON cat.id=c.id_cms_category WHERE c.id_owner=2 AND c.site_key='miamitechlab' AND c.status='PUBLISHED' AND (c.type='post' OR c.content_type='blog') ORDER BY COALESCE(c.published_at,c.created_at) DESC LIMIT 3");
            $data['featuredArticles'] = $db->fetchAll();
        } catch (\Throwable $fallbackError) {
            error_log('Tech Lab featured article query failed: ' . $fallbackError->getMessage());
        }
    }
    return TemplateResponse::render(__DIR__ . '/index.twig', $data);
});
$router->run();
