<?php

namespace App\Services;

use App\Utils\SiteContext;

class PublicSeoService
{
    private const GOOGLE_BUSINESS_PROFILE_URL = 'https://share.google/dQqX7hhKBHLVaZaqQ';

    private static function siteUrl(): string { return rtrim(SiteContext::publicBaseUrl(), '/'); }
    private static function siteName(): string { return SiteContext::siteName(); }
    private static function logoUrl(): string { return self::siteUrl() . '/assets/miami-tech-lab/mark.png'; }
    private static function localBusinessId(): string { return self::siteUrl() . '/#organization'; }
    private static function websiteId(): string { return self::siteUrl() . '/#website'; }

    public static function locationSeo(object $page): array
    {
        $canonical = self::canonical($page->canonical_url ?? null, '/locations/' . trim((string)($page->slug ?? ''), '/') . '/');
        $title = self::firstFilled([
            $page->meta_title ?? null,
            $page->hero_title ?? null,
            ($page->title ?? '') ? $page->title . ' | Tech Lab Miami' : null,
        ]);
        $description = self::firstFilled([
            $page->meta_description ?? null,
            $page->excerpt ?? null,
            ($page->city ?? '') ? 'Practical AI consulting, business automation, software guidance and technology education for organizations in ' . $page->city . '.' : null,
        ]);

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => self::robots(($page->status ?? '') === 'PUBLISHED' && (int)($page->is_indexable ?? 1) === 1),
            'og_type' => 'website',
            'og_image' => self::absoluteUrl(self::firstFilled([$page->og_image ?? null, $page->hero_image ?? null, self::logoUrl()])),
            'og_image_alt' => self::clean($page->title ?? self::siteName()),
        ];
    }

    public static function locationSchema(object $page, array $faqs = []): array
    {
        $canonical = self::canonical($page->canonical_url ?? null, '/locations/' . trim((string)($page->slug ?? ''), '/') . '/');
        $title = self::firstFilled([$page->meta_title ?? null, $page->hero_title ?? null, $page->title ?? null]);
        $description = self::firstFilled([$page->meta_description ?? null, $page->excerpt ?? null]);
        $city = self::clean($page->city ?? '');
        $state = self::clean($page->state ?? '');
        $areaName = trim($city . ($state ? ', ' . $state : ''));

        $graph = [
            self::organizationNode(),
            self::websiteNode(),
            [
                '@type' => 'WebPage',
                '@id' => $canonical . '#webpage',
                'url' => $canonical,
                'name' => $title,
                'description' => $description,
                'isPartOf' => ['@id' => self::websiteId()],
                'about' => ['@id' => $canonical . '#service'],
                'breadcrumb' => ['@id' => $canonical . '#breadcrumb'],
                'inLanguage' => 'en-US',
            ],
            [
                '@type' => 'Service',
                '@id' => $canonical . '#service',
                'name' => self::firstFilled([
                    $page->primary_keyword ?? null,
                    $city ? 'Technology and AI Services in ' . $city : 'Technology and AI Services',
                ]),
                'provider' => ['@id' => self::localBusinessId()],
                'areaServed' => $city ? [[
                    '@type' => 'City',
                    'name' => $areaName ?: $city,
                ]] : [[
                    '@type' => 'AdministrativeArea',
                    'name' => 'South Florida',
                ]],
                'serviceType' => 'AI consulting, business automation, software strategy and technology education',
                'url' => $canonical,
            ],
            self::breadcrumbNode($canonical, [
                ['name' => 'Home', 'url' => self::siteUrl() . '/'],
                ['name' => 'Locations', 'url' => self::siteUrl() . '/locations/'],
                ['name' => $city ?: ($page->title ?? 'Location'), 'url' => $canonical],
            ]),
        ];

        $faqNode = self::faqNode($canonical, $faqs);
        if ($faqNode) {
            $graph[] = $faqNode;
        }

        return self::schema($graph);
    }

    public static function homepageSchema(array $seo = []): array
    {
        $canonical = self::siteUrl() . '/';
        return self::schema([
            self::organizationNode(),
            self::websiteNode(),
            self::webPageNode(
                $canonical,
                self::firstFilled([$seo['title'] ?? null, 'Tech Lab Miami | AI, Software and Business Innovation']),
                self::firstFilled([
                    $seo['description'] ?? null,
                    'Tech Lab Miami connects South Florida entrepreneurs and small businesses with practical AI, software, automation, education and a trusted technology community.',
                ])
            ),
        ]);
    }

    public static function productSchema(object $product, array $faqs = []): array
    {
        $canonical = self::siteUrl() . '/product/' . trim((string)($product->slug ?? ''), '/') . '/';
        $name = self::clean($product->name ?? 'Tech Lab Miami Product');
        $description = self::firstFilled([
            $product->short_description ?? null,
            $product->description ?? null,
            $name,
        ]);
        $image = self::absoluteUrl(self::firstFilled([$product->main_image ?? null, self::logoUrl()]));

        $productNode = [
            '@type' => 'Product',
            '@id' => $canonical . '#product',
            'name' => $name,
            'url' => $canonical,
            'description' => $description,
            'image' => [$image],
            'brand' => ['@id' => self::localBusinessId()],
            'seller' => ['@id' => self::localBusinessId()],
            'category' => self::productCategoryName($product),
            'offers' => self::productOffersNode($product, $canonical),
        ];

        $sku = self::clean($product->sku ?? '');
        if ($sku !== '') {
            $productNode['sku'] = $sku;
        }

        $graph = [
            self::organizationNode(),
            self::websiteNode(),
            [
                '@type' => 'WebPage',
                '@id' => $canonical . '#webpage',
                'url' => $canonical,
                'name' => $name . ' | Tech Lab Miami Store',
                'description' => $description,
                'isPartOf' => ['@id' => self::websiteId()],
                'about' => ['@id' => $canonical . '#product'],
                'breadcrumb' => ['@id' => $canonical . '#breadcrumb'],
                'inLanguage' => 'en-US',
            ],
            $productNode,
            self::breadcrumbNode($canonical, [
                ['name' => 'Home', 'url' => self::siteUrl() . '/'],
                ['name' => 'Store', 'url' => self::siteUrl() . '/store-categories/'],
                ['name' => self::productCategoryName($product), 'url' => self::productCategoryUrl($product)],
                ['name' => $name, 'url' => $canonical],
            ]),
        ];

        $faqNode = self::faqNode($canonical, $faqs);
        if ($faqNode) {
            $graph[] = $faqNode;
        }

        return self::schema($graph);
    }

    public static function contentSeo(object $content, object $route, string $fallbackType = 'page'): array
    {
        $canonical = self::canonical($content->canonical_url ?? null, $route->route ?? ('/' . trim((string)($content->slug ?? ''), '/') . '/'));
        $titleSuffix = $fallbackType === 'post' ? ' | Tech Lab Miami Blog' : ' | Tech Lab Miami';

        return [
            'title' => self::firstFilled([
                $content->meta_title ?? null,
                ($content->title ?? '') ? $content->title . $titleSuffix : null,
            ]),
            'description' => self::firstFilled([
                $content->meta_description ?? null,
                $content->excerpt ?? null,
                $fallbackType === 'post' ? 'Tech Lab Miami blog article.' : 'Tech Lab Miami public page.',
            ]),
            'canonical' => $canonical,
            'robots' => self::robots(($content->status ?? '') === 'PUBLISHED', $content->robots ?? null),
            'og_type' => $fallbackType === 'post' ? 'article' : 'website',
            'og_image' => self::absoluteUrl(self::firstFilled([$content->featured_image_url ?? null, self::logoUrl()])),
            'og_image_alt' => self::clean($content->title ?? self::siteName()),
        ];
    }

    public static function blogSchema(object $post, object $route, ?object $category = null): array
    {
        $canonical = self::canonical($post->canonical_url ?? null, $route->route ?? ('/' . trim((string)($post->slug ?? ''), '/') . '/'));
        $graph = [
            self::organizationNode(),
            self::websiteNode(),
            [
                '@type' => 'WebPage',
                '@id' => $canonical . '#webpage',
                'url' => $canonical,
                'name' => self::firstFilled([$post->meta_title ?? null, $post->title ?? null]),
                'description' => self::firstFilled([$post->meta_description ?? null, $post->excerpt ?? null]),
                'isPartOf' => ['@id' => self::websiteId()],
                'about' => ['@id' => self::localBusinessId()],
                'breadcrumb' => ['@id' => $canonical . '#breadcrumb'],
                'inLanguage' => 'en-US',
            ],
            [
                '@type' => 'BlogPosting',
                '@id' => $canonical . '#blogposting',
                'headline' => self::clean($post->title ?? ''),
                'description' => self::firstFilled([$post->meta_description ?? null, $post->excerpt ?? null]),
                'datePublished' => self::dateIso($post->published_at ?? $post->created_at ?? null),
                'dateModified' => self::dateIso($post->updated_at ?? $post->published_at ?? null),
                'mainEntityOfPage' => ['@id' => $canonical . '#webpage'],
                'author' => [
                    '@type' => 'Organization',
                    '@id' => self::localBusinessId(),
                    'name' => self::siteName(),
                ],
                'publisher' => ['@id' => self::localBusinessId()],
            ],
            self::breadcrumbNode($canonical, [
                ['name' => 'Home', 'url' => self::siteUrl() . '/'],
                ['name' => 'Blog', 'url' => self::siteUrl() . '/blog/'],
                ['name' => $category->name ?? 'Article', 'url' => $category ? self::siteUrl() . '/blog/' . trim((string)$category->slug, '/') . '/' : $canonical],
                ['name' => $post->title ?? 'Article', 'url' => $canonical],
            ]),
        ];

        if (!empty($post->featured_image_url)) {
            $graph[] = self::imageNode($canonical . '#primaryimage', $post->featured_image_url, $post->title ?? '');
            $graph[3]['image'] = ['@id' => $canonical . '#primaryimage'];
        }

        return self::schema($graph);
    }

    public static function pageSchema(object $page, object $route, array $contentJson = []): array
    {
        $canonical = self::canonical($page->canonical_url ?? null, $route->route ?? ('/' . trim((string)($page->slug ?? ''), '/') . '/'));
        $graph = [
            self::organizationNode(),
            self::websiteNode(),
            [
                '@type' => 'WebPage',
                '@id' => $canonical . '#webpage',
                'url' => $canonical,
                'name' => self::firstFilled([$page->meta_title ?? null, $page->title ?? null]),
                'description' => self::firstFilled([$page->meta_description ?? null, $page->excerpt ?? null]),
                'isPartOf' => ['@id' => self::websiteId()],
                'about' => ['@id' => self::localBusinessId()],
                'breadcrumb' => ['@id' => $canonical . '#breadcrumb'],
                'inLanguage' => 'en-US',
            ],
            self::breadcrumbNode($canonical, [
                ['name' => 'Home', 'url' => self::siteUrl() . '/'],
                ['name' => $page->title ?? 'Page', 'url' => $canonical],
            ]),
        ];

        if (self::looksLikeServicePage($page, $contentJson)) {
            $graph[] = [
                '@type' => 'Service',
                '@id' => $canonical . '#service',
                'name' => self::clean($page->title ?? 'Tech Lab Miami Service'),
                'provider' => ['@id' => self::localBusinessId()],
                'areaServed' => self::defaultAreaServed(false),
                'serviceType' => self::clean($page->title ?? 'Technology consulting and education'),
                'url' => $canonical,
            ];
        }

        $faqNode = self::faqNode($canonical, self::extractFaqs($contentJson));
        if ($faqNode) {
            $graph[] = $faqNode;
        }

        return self::schema($graph);
    }

    public static function forumTopicSeo(object $topic): array
    {
        $canonical = self::absoluteUrl('/forums/' . trim((string)($topic->slug ?? ('topic-' . $topic->id)), '/') . '/');
        $description = self::firstFilled([
            $topic->seo_description ?? null,
            $topic->excerpt ?? null,
            substr(self::clean($topic->content ?? ''), 0, 155),
        ]);

        return [
            'title' => self::firstFilled([
                $topic->seo_title ?? null,
                ($topic->title ?? '') ? $topic->title . ' | Tech Lab Miami Community' : null,
            ]),
            'description' => $description,
            'canonical' => $canonical,
            'robots' => (($topic->status ?? 'PUBLISHED') === 'PUBLISHED' && (int)($topic->is_approved ?? 1) === 1)
                ? 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'
                : 'noindex,follow',
            'og_type' => 'article',
            'og_image' => self::logoUrl(),
            'og_image_alt' => self::siteName() . ' community discussion',
        ];
    }

    public static function forumTopicSchema(object $topic): array
    {
        $canonical = self::absoluteUrl('/forums/' . trim((string)($topic->slug ?? ('topic-' . $topic->id)), '/') . '/');

        return self::schema([
            self::organizationNode(),
            self::websiteNode(),
            [
                '@type' => 'WebPage',
                '@id' => $canonical . '#webpage',
                'url' => $canonical,
                'name' => self::firstFilled([$topic->seo_title ?? null, $topic->title ?? null]),
                'description' => self::firstFilled([$topic->seo_description ?? null, $topic->excerpt ?? null]),
                'isPartOf' => ['@id' => self::websiteId()],
                'about' => ['@id' => self::localBusinessId()],
                'breadcrumb' => ['@id' => $canonical . '#breadcrumb'],
                'inLanguage' => 'en-US',
            ],
            [
                '@type' => 'DiscussionForumPosting',
                '@id' => $canonical . '#discussion',
                'headline' => self::clean($topic->title ?? ''),
                'text' => self::clean($topic->content ?? ''),
                'datePublished' => self::dateIso($topic->published_at ?? $topic->created_at ?? null),
                'dateModified' => self::dateIso($topic->updated_at ?? $topic->published_at ?? null),
                'author' => [
                    '@type' => 'Person',
                    'name' => self::clean(trim(($topic->user_name ?? '') . ' ' . ($topic->user_lastname ?? ''))) ?: self::siteName(),
                ],
                'publisher' => ['@id' => self::localBusinessId()],
                'mainEntityOfPage' => ['@id' => $canonical . '#webpage'],
            ],
            self::breadcrumbNode($canonical, [
                ['name' => 'Home', 'url' => self::siteUrl() . '/'],
                ['name' => 'Forums', 'url' => self::siteUrl() . '/forums/'],
                ['name' => $topic->title ?? 'Discussion', 'url' => $canonical],
            ]),
        ]);
    }

    public static function forumListSeo(): array
    {
        return [
            'title' => 'Tech Lab Miami Community Forums | Event Planning Q&A',
            'description' => 'Public Tech Lab Miami community discussions for event planning ideas, venues, rentals, quinceañeras, weddings and corporate events in South Florida.',
            'canonical' => self::siteUrl() . '/forums/',
            'robots' => 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1',
            'og_type' => 'website',
            'og_image' => self::logoUrl(),
            'og_image_alt' => self::siteName() . ' community forums',
        ];
    }

    public static function defaultInternalLinks(): array
    {
        return [
            ['label' => 'Home', 'url' => self::siteUrl() . '/'],
            ['label' => 'AI Consulting', 'url' => self::siteUrl() . '/services/ai-consulting/'],
            ['label' => 'Business Automation', 'url' => self::siteUrl() . '/services/business-automation/'],
            ['label' => 'Resources', 'url' => self::siteUrl() . '/resources/'],
            ['label' => 'Locations', 'url' => self::siteUrl() . '/locations/'],
        ];
    }

    public static function defaultSchema(string $canonical, array $seo = [], string $templateChild = ''): array
    {
        $canonical = self::absoluteUrl($canonical);
        $title = self::firstFilled([$seo['title'] ?? null, self::siteName()]);
        $description = self::firstFilled([
            $seo['description'] ?? null,
            'Tech Lab Miami is a South Florida technology community and studio focused on practical AI, software, automation, education and business innovation.',
        ]);

        $graph = [
            self::organizationNode(),
            self::websiteNode(),
            self::webPageNode($canonical, $title, $description),
        ];

        if ($canonical !== self::siteUrl() . '/') {
            $graph[] = self::breadcrumbNode($canonical, self::breadcrumbsFromCanonical($canonical, $title));
        }

        if ($canonical !== self::siteUrl() . '/' && self::pathLooksLikeService(self::pathFromCanonical($canonical), $title . ' ' . $description . ' ' . $templateChild)) {
            $graph[] = self::serviceNode($canonical, $title, $description);
        }

        return self::schema($graph);
    }

    private static function schema(array $graph): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph' => array_values(array_filter($graph)),
        ];
    }

    private static function organizationNode(): array
    {
        return [
            '@type' => 'Organization',
            '@id' => self::localBusinessId(),
            'name' => 'Tech Lab Miami',
            'url' => self::siteUrl() . '/',
            'logo' => ['@type' => 'ImageObject', 'url' => self::logoUrl()],
            'image' => self::logoUrl(),
            'description' => 'Tech Lab Miami is a South Florida technology community and studio focused on practical AI, software, automation, education and business innovation.',
            'areaServed' => self::defaultAreaServed(true),
            'knowsAbout' => ['Artificial intelligence', 'Business automation', 'Software development', 'Technology education', 'South Florida technology'],
            'founder' => [
                ['@type' => 'Person', 'name' => 'Jonathan Moreno'],
                ['@type' => 'Person', 'name' => 'Lucas Alvarado'],
            ],
        ];

    }

    private static function locationServiceNode(string $canonical, object $page, string $areaName): array
    {
        $city = self::clean($page->city ?? '');
        $state = self::clean($page->state ?? '');
        $nameArea = $areaName !== '' ? $areaName : self::firstFilled([$city, $page->title ?? null, 'South Florida']);

        return [
            '@type' => 'Service',
            '@id' => $canonical . '#service',
            'name' => 'Technology and AI services in ' . $nameArea,
            'url' => $canonical,
            'description' => self::firstFilled([
                $page->meta_description ?? null,
                $page->excerpt ?? null,
                'Tech Lab Miami provides practical AI guidance, business automation, software strategy and technology education in ' . $nameArea . '.',
            ]),
            'provider' => ['@id' => self::localBusinessId()],
            'areaServed' => [[
                '@type' => $city !== '' ? 'City' : 'AdministrativeArea',
                'name' => $nameArea,
                'addressRegion' => $state ?: 'FL',
            ]],
            'image' => self::absoluteUrl(self::firstFilled([$page->hero_image ?? null, $page->og_image ?? null, self::logoUrl()])),
        ];
    }

    private static function websiteNode(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => self::websiteId(),
            'url' => self::siteUrl() . '/',
            'name' => self::siteName(),
            'publisher' => ['@id' => self::localBusinessId()],
            'inLanguage' => 'en-US',
        ];
    }

    private static function webPageNode(string $canonical, string $title, string $description): array
    {
        $node = [
            '@type' => 'WebPage',
            '@id' => $canonical . '#webpage',
            'url' => $canonical,
            'name' => $title,
            'description' => $description,
            'isPartOf' => ['@id' => self::websiteId()],
            'about' => ['@id' => self::localBusinessId()],
            'inLanguage' => 'en-US',
        ];

        if ($canonical !== self::siteUrl() . '/') {
            $node['breadcrumb'] = ['@id' => $canonical . '#breadcrumb'];
        }

        return $node;
    }

    private static function serviceNode(string $canonical, string $title, string $description, ?string $areaName = null): array
    {
        return [
            '@type' => 'Service',
            '@id' => $canonical . '#service',
            'name' => $title,
            'url' => $canonical,
            'description' => $description,
            'provider' => ['@id' => self::localBusinessId()],
            'areaServed' => $areaName
                ? [['@type' => 'Place', 'name' => $areaName]]
                : self::defaultAreaServed(false),
            'serviceType' => self::serviceTypeFromText($title),
        ];
    }

    private static function productOffersNode(object $product, string $canonical): array
    {
        $productType = strtoupper((string)($product->product_type ?? 'FIXED'));
        $availability = ((int)($product->stock_quantity ?? 0) > 0 || $productType === 'VARIABLE')
            ? 'https://schema.org/InStock'
            : 'https://schema.org/OutOfStock';

        if ($productType === 'VARIABLE') {
            $lowPrice = self::moneyValue($product->min_price ?? $product->display_price ?? $product->price ?? 0);
            $highPrice = self::moneyValue($product->max_price ?? $product->display_price ?? $product->price ?? $lowPrice);
            $offers = [];

            foreach (($product->variations ?? []) as $variation) {
                $variationPrice = self::moneyValue($variation->effective_price ?? $variation->price ?? $lowPrice);
                if ((float)$variationPrice <= 0) {
                    continue;
                }

                $offers[] = [
                    '@type' => 'Offer',
                    'name' => self::clean($variation->name ?? ($product->name ?? 'Product option')),
                    'url' => $canonical,
                    'priceCurrency' => 'USD',
                    'price' => $variationPrice,
                    'availability' => 'https://schema.org/InStock',
                    'itemCondition' => 'https://schema.org/NewCondition',
                    'seller' => ['@id' => self::localBusinessId()],
                ];
            }

            return array_filter([
                '@type' => 'AggregateOffer',
                'url' => $canonical,
                'priceCurrency' => 'USD',
                'lowPrice' => $lowPrice,
                'highPrice' => $highPrice,
                'offerCount' => max(1, count($offers)),
                'availability' => $availability,
                'seller' => ['@id' => self::localBusinessId()],
                'offers' => $offers ?: null,
            ]);
        }

        return [
            '@type' => 'Offer',
            'url' => $canonical,
            'priceCurrency' => 'USD',
            'price' => self::moneyValue($product->display_price ?? $product->promo_price ?? $product->price ?? 0),
            'availability' => $availability,
            'itemCondition' => 'https://schema.org/NewCondition',
            'seller' => ['@id' => self::localBusinessId()],
        ];
    }

    private static function productCategoryName(object $product): string
    {
        $categories = $product->categories ?? [];
        if (is_array($categories) && !empty($categories)) {
            $category = reset($categories);
            return self::firstFilled([$category->name ?? null, 'Tech Lab Miami Services']);
        }

        return 'Tech Lab Miami Services';
    }

    private static function productCategoryUrl(object $product): string
    {
        $categories = $product->categories ?? [];
        if (is_array($categories) && !empty($categories)) {
            $category = reset($categories);
            $slug = trim((string)($category->slug ?? ''), '/');
            if ($slug !== '') {
                return self::siteUrl() . '/product-category/' . $slug . '/';
            }
        }

        return self::siteUrl() . '/store-categories/';
    }

    private static function moneyValue($value): string
    {
        $amount = max(0, (float)$value);
        return number_format($amount, 2, '.', '');
    }

    private static function breadcrumbNode(string $canonical, array $items): array
    {
        $elements = [];
        $seen = [];
        foreach (array_values($items) as $index => $item) {
            $name = self::clean($item['name'] ?? '');
            $url = self::absoluteUrl($item['url'] ?? $canonical);
            if ($name === '' || isset($seen[$url])) {
                continue;
            }
            $seen[$url] = true;
            $elements[] = [
                '@type' => 'ListItem',
                'position' => count($elements) + 1,
                'name' => $name,
                'item' => $url,
            ];
        }

        return [
            '@type' => 'BreadcrumbList',
            '@id' => $canonical . '#breadcrumb',
            'itemListElement' => $elements,
        ];
    }

    private static function faqNode(string $canonical, array $faqs): ?array
    {
        $entities = [];
        foreach ($faqs as $faq) {
            $question = self::clean($faq['question'] ?? '');
            $answer = self::clean($faq['answer'] ?? '');
            if ($question === '' || $answer === '') {
                continue;
            }
            $entities[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $answer,
                ],
            ];
        }

        if (!$entities) {
            return null;
        }

        return [
            '@type' => 'FAQPage',
            '@id' => $canonical . '#faq',
            'mainEntity' => $entities,
        ];
    }

    private static function imageNode(string $id, string $url, string $caption = ''): array
    {
        return [
            '@type' => 'ImageObject',
            '@id' => $id,
            'url' => self::absoluteUrl($url),
            'caption' => self::clean($caption),
        ];
    }

    private static function extractFaqs(array $contentJson): array
    {
        $faqs = [];
        foreach (($contentJson['blocks'] ?? []) as $block) {
            if (($block['type'] ?? '') === 'faq' && !empty($block['items']) && is_array($block['items'])) {
                $faqs = array_merge($faqs, $block['items']);
            }
        }

        return $faqs;
    }

    private static function looksLikeServicePage(object $page, array $contentJson): bool
    {
        $haystack = strtolower(($page->title ?? '') . ' ' . ($page->slug ?? '') . ' ' . ($page->excerpt ?? ''));
        foreach (self::serviceNeedles() as $needle) {
            if (strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        foreach (($contentJson['blocks'] ?? []) as $block) {
            if (($block['type'] ?? '') === 'service') {
                return true;
            }
        }

        return false;
    }

    private static function canonical(?string $stored, string $fallbackPath): string
    {
        $stored = self::validCanonical($stored);
        return self::absoluteUrl(self::firstFilled([$stored, $fallbackPath]));
    }

    private static function robots(bool $indexable, ?string $stored = null): string
    {
        if ($stored && stripos($stored, 'noindex') !== false) {
            return 'noindex,follow';
        }

        return $indexable ? 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1' : 'noindex,follow';
    }

    private static function firstFilled(array $values): string
    {
        foreach ($values as $value) {
            $value = self::clean($value);
            if ($value !== '') {
                return $value;
            }
        }

        return self::siteName();
    }

    private static function clean($value): string
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags((string)$value)) ?? '');
    }

    private static function absoluteUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return self::siteUrl() . '/';
        }

        if (preg_match('#^https?://#i', $url)) {
            $path = parse_url($url, PHP_URL_PATH) ?: '/';
            return self::siteUrl() . self::normalizePath($path);
        }

        return self::siteUrl() . self::normalizePath($url);
    }

    private static function validCanonical(?string $url): ?string
    {
        $url = trim((string)$url);
        if ($url === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $url)) {
            $host = strtolower((string)(parse_url($url, PHP_URL_HOST) ?: ''));
            $siteHost = strtolower((string)(parse_url(self::siteUrl(), PHP_URL_HOST) ?: ''));
            if ($host !== $siteHost && $host !== 'www.' . $siteHost) {
                return null;
            }
        }

        return $url;
    }

    private static function normalizePath(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?: '/';
        $path = '/' . ltrim($path, '/');
        $path = preg_replace('#/+#', '/', $path) ?: '/';

        if ($path !== '/') {
            $path = rtrim($path, '/') . '/';
        }

        return $path;
    }

    private static function pathFromCanonical(string $canonical): string
    {
        return self::normalizePath(parse_url($canonical, PHP_URL_PATH) ?: '/');
    }

    private static function breadcrumbsFromCanonical(string $canonical, string $title): array
    {
        $path = trim(self::pathFromCanonical($canonical), '/');
        $items = [['name' => 'Home', 'url' => self::siteUrl() . '/']];

        if ($path === '') {
            return $items;
        }

        $segments = explode('/', $path);
        $running = '';
        foreach ($segments as $index => $segment) {
            $running .= '/' . $segment;
            $isLast = $index === count($segments) - 1;
            $items[] = [
                'name' => $isLast ? $title : self::titleFromSlug($segment),
                'url' => self::siteUrl() . self::normalizePath($running),
            ];
        }

        return $items;
    }

    private static function titleFromSlug(string $slug): string
    {
        $special = [
            'blog' => 'Blog',
            'locations' => 'Locations',
            'software' => 'Software',
            'resources' => 'Resources',
            'services' => 'Services',
        ];

        return $special[$slug] ?? ucwords(str_replace(['-', '_'], ' ', $slug));
    }

    private static function pathLooksLikeService(string $path, string $text): bool
    {
        $haystack = strtolower($path . ' ' . $text);
        if (str_contains($haystack, '/blog/')) {
            return false;
        }

        foreach (self::serviceNeedles() as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private static function serviceNeedles(): array
    {
        return [
            'service',
            'technology',
            'artificial intelligence',
            ' ai ',
            'automation',
            'software',
            'cybersecurity',
            'data',
            'digital transformation',
            'consulting',
            'workshop',
            'training',
        ];
    }

    private static function serviceTypeFromText(string $text): string
    {
        $text = strtolower($text);
        foreach ([
            'artificial intelligence' => 'AI consulting',
            ' ai ' => 'AI consulting',
            'automation' => 'Business automation',
            'software' => 'Software strategy and development',
            'cybersecurity' => 'Cybersecurity guidance',
            'data' => 'Data and analytics consulting',
            'workshop' => 'Technology education',
            'training' => 'Technology education',
        ] as $needle => $type) {
            if (str_contains($text, $needle)) {
                return $type;
            }
        }

        return 'Technology consulting';
    }

    private static function defaultAreaServed(bool $includeSouthFlorida): array
    {
        $areas = [
            ['@type' => 'AdministrativeArea', 'name' => 'Miami-Dade County'],
            ['@type' => 'AdministrativeArea', 'name' => 'Broward County'],
            ['@type' => 'AdministrativeArea', 'name' => 'Palm Beach County'],
        ];

        if ($includeSouthFlorida) {
            $areas[] = ['@type' => 'Place', 'name' => 'South Florida'];
        }

        return $areas;
    }

    private static function dateIso($date): ?string
    {
        if (!$date) {
            return null;
        }

        $timestamp = strtotime((string)$date);
        return $timestamp ? date('c', $timestamp) : null;
    }
}
