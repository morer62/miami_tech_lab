<?php

namespace App\Commands;

use App\Services\SeoFilesGeneratorService;

final class GenerateSeoFiles extends BaseCommand
{
    public function getName(): string
    {
        return 'generate-seo-files';
    }

    public function handle(array $args): void
    {
        $result = (new SeoFilesGeneratorService())->generate('all');
        foreach (($result['results'] ?? []) as $type => $item) {
            echo sprintf(
                "%s: %s (%d entries)\n",
                $type,
                $item['status'] ?? 'unknown',
                (int) ($item['items_count'] ?? 0)
            );
        }

        if (($result['status'] ?? 'failed') !== 'success') {
            throw new \RuntimeException($result['message'] ?? 'SEO generation failed.');
        }
    }
}
