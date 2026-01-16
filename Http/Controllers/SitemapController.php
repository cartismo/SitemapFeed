<?php

namespace Modules\SitemapFeed\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Modules\SitemapFeed\Services\SitemapService;

class SitemapController extends Controller
{
    public function __construct(
        protected SitemapService $sitemapService
    ) {}

    /**
     * Serve the main sitemap index
     */
    public function index(): Response
    {
        return $this->serveSitemap('sitemap.xml');
    }

    /**
     * Serve a specific sitemap file
     */
    public function show(string $filename): Response
    {
        // Sanitize filename to prevent directory traversal
        $filename = basename($filename);

        if (!str_ends_with($filename, '.xml')) {
            $filename .= '.xml';
        }

        return $this->serveSitemap($filename);
    }

    /**
     * Serve a sitemap file
     */
    protected function serveSitemap(string $filename): Response
    {
        $path = public_path('sitemaps/' . $filename);

        if (!File::exists($path)) {
            // Try to generate sitemap if it doesn't exist
            if ($filename === 'sitemap.xml' && $this->sitemapService->isEnabled()) {
                $this->sitemapService->generateAll();

                if (File::exists($path)) {
                    return $this->xmlResponse(File::get($path));
                }
            }

            abort(404, 'Sitemap not found');
        }

        return $this->xmlResponse(File::get($path));
    }

    /**
     * Return XML response with proper headers
     */
    protected function xmlResponse(string $content): Response
    {
        return response($content, 200, [
            'Content-Type' => 'application/xml',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}