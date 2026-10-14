<?php

declare(strict_types=1);

use App\Services\PublicArticleBodySanitizer;

require dirname(__DIR__) . '/vendor/autoload.php';

$title = 'Bilingual AI Customer Communication: Where Machine Translation Helps and Where It Hurts';
$fixture = '<article class="cms-ai-article"><header><h1>' . $title . '</h1></header>' . "\n"
    . 'Searched the web' . "\n"
    . "\u{E02A}\n"
    . 'Searched the web' . "\n"
    . '<article class="cms-ai-article"><header><h1>' . $title . '</h1></header>'
    . '<p>Keep this paragraph.</p><h2>Keep this section</h2></article></article>';

$clean = PublicArticleBodySanitizer::sanitize($fixture, $title);

$failures = [];
if (substr_count(strtolower($clean), '<h1') !== 0) $failures[] = 'duplicate H1 remained';
if (stripos($clean, 'Searched the web') !== false) $failures[] = 'research artifact remained';
if (substr_count(strtolower($clean), '<article') !== 0) $failures[] = 'duplicate article wrapper remained';
if (!str_contains($clean, '<p>Keep this paragraph.</p>')) $failures[] = 'article paragraph was removed';
if (!str_contains($clean, '<h2>Keep this section</h2>')) $failures[] = 'article section was removed';

$legitimate = '<p>Searched the web for a supplier.</p>';
if (PublicArticleBodySanitizer::sanitize($legitimate, $title) !== $legitimate) {
    $failures[] = 'legitimate body copy was altered';
}

if ($failures !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: duplicate generated heading/wrapper artifacts are removed without changing article content.\n";
