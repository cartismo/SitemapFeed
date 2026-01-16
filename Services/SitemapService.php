<?php

namespace Modules\SitemapFeed\Services;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\InstalledModule;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Support\Facades\File;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\SitemapIndex;
use Spatie\Sitemap\Tags\Url;

class SitemapService
{
    protected ?array $settings = null;
    protected string $sitemapPath;

    public function __construct()
    {
        $this->sitemapPath = public_path('sitemaps');
    }

    /**
     * Get module settings
     */
    public function getSettings(): array
    {
        if ($this->settings === null) {
            $module = InstalledModule::where('slug', 'sitemap-feed')->first();

            $defaultSettings = [
                'enabled' => false,
                'include_products' => true,
                'include_categories' => true,
                'include_pages' => true,
                'include_blog' => false,
                'include_manufacturers' => false,
                'changefreq_products' => 'daily',
                'changefreq_categories' => 'weekly',
                'changefreq_pages' => 'monthly',
                'changefreq_blog' => 'weekly',
                'priority_homepage' => 1.0,
                'priority_products' => 0.8,
                'priority_categories' => 0.6,
                'priority_pages' => 0.5,
                'priority_blog' => 0.6,
                'max_urls_per_sitemap' => 50000,
                'ping_google' => true,
                'ping_bing' => false,
                'last_generated' => null,
            ];

            $this->settings = array_replace_recursive($defaultSettings, $module?->settings ?? []);
        }

        return $this->settings;
    }

    /**
     * Check if sitemap generation is enabled
     */
    public function isEnabled(): bool
    {
        return $this->getSettings()['enabled'] ?? false;
    }

    /**
     * Generate all sitemaps
     */
    public function generateAll(): array
    {
        $results = [
            'success' => true,
            'sitemaps' => [],
            'errors' => [],
        ];

        // Ensure sitemap directory exists
        if (!File::exists($this->sitemapPath)) {
            File::makeDirectory($this->sitemapPath, 0755, true);
        }

        $settings = $this->getSettings();
        $sitemapIndex = SitemapIndex::create();

        try {
            // Generate homepage sitemap
            $this->generateHomeSitemap();
            $sitemapIndex->add(url('/sitemaps/sitemap-home.xml'));
            $results['sitemaps'][] = 'sitemap-home.xml';

            // Generate products sitemap
            if ($settings['include_products']) {
                $productSitemaps = $this->generateProductsSitemap();
                foreach ($productSitemaps as $sitemap) {
                    $sitemapIndex->add(url('/sitemaps/' . $sitemap));
                    $results['sitemaps'][] = $sitemap;
                }
            }

            // Generate categories sitemap
            if ($settings['include_categories']) {
                $this->generateCategoriesSitemap();
                $sitemapIndex->add(url('/sitemaps/sitemap-categories.xml'));
                $results['sitemaps'][] = 'sitemap-categories.xml';
            }

            // Generate pages sitemap
            if ($settings['include_pages']) {
                $this->generatePagesSitemap();
                $sitemapIndex->add(url('/sitemaps/sitemap-pages.xml'));
                $results['sitemaps'][] = 'sitemap-pages.xml';
            }

            // Generate blog sitemap
            if ($settings['include_blog']) {
                $this->generateBlogSitemap();
                $sitemapIndex->add(url('/sitemaps/sitemap-blog.xml'));
                $results['sitemaps'][] = 'sitemap-blog.xml';
            }

            // Write sitemap index
            $sitemapIndex->writeToFile($this->sitemapPath . '/sitemap.xml');

            // Update last generated time
            $this->updateLastGenerated();

            // Ping search engines
            if ($settings['ping_google']) {
                $this->pingGoogle();
            }
            if ($settings['ping_bing']) {
                $this->pingBing();
            }

        } catch (\Exception $e) {
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();
            report($e);
        }

        return $results;
    }

    /**
     * Generate home/static pages sitemap
     */
    protected function generateHomeSitemap(): void
    {
        $settings = $this->getSettings();

        $sitemap = Sitemap::create()
            ->add(Url::create('/')
                ->setLastModificationDate(now())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                ->setPriority($settings['priority_homepage']));

        $sitemap->writeToFile($this->sitemapPath . '/sitemap-home.xml');
    }

    /**
     * Generate products sitemap (with chunking for large catalogs)
     */
    protected function generateProductsSitemap(): array
    {
        $settings = $this->getSettings();
        $maxUrls = $settings['max_urls_per_sitemap'];
        $sitemapFiles = [];

        $products = Product::where('is_active', true)
            ->with(['translations' => function ($query) {
                $query->select('product_id', 'locale', 'slug');
            }])
            ->select('id', 'updated_at')
            ->orderBy('id')
            ->get();

        $chunks = $products->chunk($maxUrls);

        foreach ($chunks as $index => $chunk) {
            $sitemap = Sitemap::create();

            foreach ($chunk as $product) {
                $slug = $product->translations->first()?->slug;
                if (!$slug) continue;

                $sitemap->add(Url::create("/product/{$slug}")
                    ->setLastModificationDate($product->updated_at)
                    ->setChangeFrequency($settings['changefreq_products'])
                    ->setPriority($settings['priority_products']));
            }

            $filename = $chunks->count() > 1
                ? "sitemap-products-{$index}.xml"
                : 'sitemap-products.xml';

            $sitemap->writeToFile($this->sitemapPath . '/' . $filename);
            $sitemapFiles[] = $filename;
        }

        return $sitemapFiles;
    }

    /**
     * Generate categories sitemap
     */
    protected function generateCategoriesSitemap(): void
    {
        $settings = $this->getSettings();

        $sitemap = Sitemap::create();

        $categories = Category::where('is_active', true)
            ->with(['translations' => function ($query) {
                $query->select('category_id', 'locale', 'slug');
            }])
            ->select('id', 'updated_at')
            ->orderBy('id')
            ->get();

        foreach ($categories as $category) {
            $slug = $category->translations->first()?->slug;
            if (!$slug) continue;

            $sitemap->add(Url::create("/category/{$slug}")
                ->setLastModificationDate($category->updated_at)
                ->setChangeFrequency($settings['changefreq_categories'])
                ->setPriority($settings['priority_categories']));
        }

        $sitemap->writeToFile($this->sitemapPath . '/sitemap-categories.xml');
    }

    /**
     * Generate pages sitemap
     */
    protected function generatePagesSitemap(): void
    {
        $settings = $this->getSettings();

        $sitemap = Sitemap::create();

        $pages = Page::where('is_active', true)
            ->with(['translations' => function ($query) {
                $query->select('page_id', 'locale', 'slug');
            }])
            ->select('id', 'updated_at')
            ->orderBy('id')
            ->get();

        foreach ($pages as $page) {
            $slug = $page->translations->first()?->slug;
            if (!$slug) continue;

            $sitemap->add(Url::create("/page/{$slug}")
                ->setLastModificationDate($page->updated_at)
                ->setChangeFrequency($settings['changefreq_pages'])
                ->setPriority($settings['priority_pages']));
        }

        $sitemap->writeToFile($this->sitemapPath . '/sitemap-pages.xml');
    }

    /**
     * Generate blog sitemap
     */
    protected function generateBlogSitemap(): void
    {
        $settings = $this->getSettings();

        $sitemap = Sitemap::create();

        // Add blog index
        $sitemap->add(Url::create('/blog')
            ->setLastModificationDate(now())
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
            ->setPriority($settings['priority_blog']));

        $posts = BlogPost::where('is_active', true)
            ->where('published_at', '<=', now())
            ->with(['translations' => function ($query) {
                $query->select('blog_post_id', 'locale', 'slug');
            }])
            ->select('id', 'updated_at')
            ->orderBy('id')
            ->get();

        foreach ($posts as $post) {
            $slug = $post->translations->first()?->slug;
            if (!$slug) continue;

            $sitemap->add(Url::create("/blog/{$slug}")
                ->setLastModificationDate($post->updated_at)
                ->setChangeFrequency($settings['changefreq_blog'])
                ->setPriority($settings['priority_blog']));
        }

        $sitemap->writeToFile($this->sitemapPath . '/sitemap-blog.xml');
    }

    /**
     * Get sitemap index URL
     */
    public function getSitemapUrl(): string
    {
        return url('/sitemaps/sitemap.xml');
    }

    /**
     * Check if sitemap exists
     */
    public function sitemapExists(): bool
    {
        return File::exists($this->sitemapPath . '/sitemap.xml');
    }

    /**
     * Get sitemap stats
     */
    public function getStats(): array
    {
        $stats = [
            'products' => Product::where('is_active', true)->count(),
            'categories' => Category::where('is_active', true)->count(),
            'pages' => Page::where('is_active', true)->count(),
            'blog_posts' => BlogPost::where('is_active', true)->where('published_at', '<=', now())->count(),
            'sitemap_exists' => $this->sitemapExists(),
            'last_generated' => $this->getSettings()['last_generated'],
        ];

        return $stats;
    }

    /**
     * Update last generated timestamp
     */
    protected function updateLastGenerated(): void
    {
        $module = InstalledModule::where('slug', 'sitemap-feed')->first();

        if ($module) {
            $settings = $module->settings ?? [];
            $settings['last_generated'] = now()->toIso8601String();
            $module->update(['settings' => $settings]);
        }
    }

    /**
     * Ping Google about sitemap update
     */
    protected function pingGoogle(): void
    {
        try {
            $sitemapUrl = urlencode($this->getSitemapUrl());
            file_get_contents("https://www.google.com/ping?sitemap={$sitemapUrl}");
        } catch (\Exception $e) {
            report($e);
        }
    }

    /**
     * Ping Bing about sitemap update
     */
    protected function pingBing(): void
    {
        try {
            $sitemapUrl = urlencode($this->getSitemapUrl());
            file_get_contents("https://www.bing.com/ping?sitemap={$sitemapUrl}");
        } catch (\Exception $e) {
            report($e);
        }
    }

    /**
     * Delete all generated sitemaps
     */
    public function clearSitemaps(): void
    {
        if (File::exists($this->sitemapPath)) {
            File::deleteDirectory($this->sitemapPath);
        }
    }
}