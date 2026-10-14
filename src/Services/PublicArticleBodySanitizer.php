<?php

namespace App\Services;

final class PublicArticleBodySanitizer
{
    public static function sanitize(string $html, string $articleTitle): string
    {
        $html = trim($html);
        if ($html === '' || trim($articleTitle) === '') {
            return $html;
        }

        $removedWrappers = 0;
        $headingPattern = '~^\s*<article\b[^>]*class=(?:"[^"]*\bcms-ai-article\b[^"]*"|\'[^\']*\bcms-ai-article\b[^\']*\')[^>]*>\s*<header\b[^>]*>\s*<h1\b[^>]*>(.*?)</h1>\s*</header>\s*~isu';

        while (preg_match($headingPattern, $html, $match) === 1) {
            if (self::normalizeText((string) $match[1]) !== self::normalizeText($articleTitle)) {
                break;
            }

            $html = (string) substr($html, strlen((string) $match[0]));
            $removedWrappers++;
            $html = self::stripResearchArtifacts($html);
        }

        // Generated drafts may contain one artifact block between two duplicated
        // article wrappers. Only remove it after a verified title wrapper was
        // removed, so legitimate body copy is never filtered by this phrase.
        if ($removedWrappers > 0) {
            $html = self::stripResearchArtifacts($html);
        }

        while ($removedWrappers > 0 && preg_match('~</article>\s*$~iu', $html) === 1) {
            $html = (string) preg_replace('~\s*</article>\s*$~iu', '', $html, 1);
            $removedWrappers--;
        }

        return trim($html);
    }

    private static function stripResearchArtifacts(string $html): string
    {
        $pattern = '~^\s*(?:Searched\s+the\s+web|[\x{E000}-\x{F8FF}])\s*~iu';
        while (preg_match($pattern, $html) === 1) {
            $html = (string) preg_replace($pattern, '', $html, 1);
        }

        return $html;
    }

    private static function normalizeText(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        return mb_strtolower(trim($text), 'UTF-8');
    }
}
