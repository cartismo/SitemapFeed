<?php

namespace Modules\SitemapFeed\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstalledModule;
use App\Traits\HasMultiStoreModuleSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\SitemapFeed\Services\SitemapService;

class SettingsController extends Controller
{
    use HasMultiStoreModuleSettings;

    public function __construct(
        protected SitemapService $sitemapService
    ) {}

    protected function getModuleSlug(): string
    {
        return 'sitemap-feed';
    }

    protected function getDefaultSettings(): array
    {
        return [
            'enabled' => false,
            'include_products' => true,
            'include_categories' => true,
            'include_pages' => true,
            'include_blog' => false,
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
    }

    public function index(): Response
    {
        $data = $this->getMultiStoreData();
        $stats = $this->sitemapService->getStats();

        $data['stats'] = $stats;
        $data['changefreqOptions'] = [
            ['value' => 'always', 'label' => 'Always'],
            ['value' => 'hourly', 'label' => 'Hourly'],
            ['value' => 'daily', 'label' => 'Daily'],
            ['value' => 'weekly', 'label' => 'Weekly'],
            ['value' => 'monthly', 'label' => 'Monthly'],
            ['value' => 'yearly', 'label' => 'Yearly'],
            ['value' => 'never', 'label' => 'Never'],
        ];
        $data['sitemapUrl'] = $this->sitemapService->getSitemapUrl();

        return Inertia::render('SitemapFeed::Admin/Settings', $data);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'is_enabled' => 'boolean',
            'settings.enabled' => 'boolean',
            'settings.include_products' => 'boolean',
            'settings.include_categories' => 'boolean',
            'settings.include_pages' => 'boolean',
            'settings.include_blog' => 'boolean',
            'settings.changefreq_products' => 'required|string|in:always,hourly,daily,weekly,monthly,yearly,never',
            'settings.changefreq_categories' => 'required|string|in:always,hourly,daily,weekly,monthly,yearly,never',
            'settings.changefreq_pages' => 'required|string|in:always,hourly,daily,weekly,monthly,yearly,never',
            'settings.changefreq_blog' => 'required|string|in:always,hourly,daily,weekly,monthly,yearly,never',
            'settings.priority_homepage' => 'required|numeric|min:0|max:1',
            'settings.priority_products' => 'required|numeric|min:0|max:1',
            'settings.priority_categories' => 'required|numeric|min:0|max:1',
            'settings.priority_pages' => 'required|numeric|min:0|max:1',
            'settings.priority_blog' => 'required|numeric|min:0|max:1',
            'settings.max_urls_per_sitemap' => 'required|integer|min:1000|max:50000',
            'settings.ping_google' => 'boolean',
            'settings.ping_bing' => 'boolean',
        ]);

        return $this->saveStoreSettings($request);
    }

    public function generate(): RedirectResponse
    {
        $result = $this->sitemapService->generateAll();

        if ($result['success']) {
            $count = count($result['sitemaps']);
            return back()->with('success', "Sitemap generated successfully! Created {$count} sitemap file(s).");
        }

        return back()->with('error', 'Failed to generate sitemap: ' . implode(', ', $result['errors']));
    }

    public function clear(): RedirectResponse
    {
        $this->sitemapService->clearSitemaps();

        // Clear last_generated
        $module = InstalledModule::where('slug', 'sitemap-feed')->first();
        if ($module) {
            $settings = $module->settings ?? [];
            $settings['last_generated'] = null;
            $module->update(['settings' => $settings]);
        }

        return back()->with('success', 'Sitemaps cleared successfully.');
    }
}