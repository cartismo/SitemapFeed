<?php

namespace Modules\SitemapFeed\Console;

use Illuminate\Console\Command;
use Modules\SitemapFeed\Services\SitemapService;

class GenerateSitemapCommand extends Command
{
    protected $signature = 'sitemap:generate {--force : Generate even if disabled}';

    protected $description = 'Generate XML sitemaps for the website';

    public function handle(SitemapService $sitemapService): int
    {
        if (!$sitemapService->isEnabled() && !$this->option('force')) {
            $this->warn('Sitemap generation is disabled. Use --force to generate anyway.');
            return self::SUCCESS;
        }

        $this->info('Generating sitemaps...');

        $result = $sitemapService->generateAll();

        if ($result['success']) {
            $this->info('Sitemaps generated successfully!');
            $this->table(['Sitemap Files'], array_map(fn($s) => [$s], $result['sitemaps']));
            $this->newLine();
            $this->info('Sitemap URL: ' . $sitemapService->getSitemapUrl());
            return self::SUCCESS;
        }

        $this->error('Failed to generate sitemaps:');
        foreach ($result['errors'] as $error) {
            $this->error('  - ' . $error);
        }

        return self::FAILURE;
    }
}