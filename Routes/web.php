<?php

use Illuminate\Support\Facades\Route;
use Modules\SitemapFeed\Http\Controllers\SitemapController;

/*
|--------------------------------------------------------------------------
| SitemapFeed Web Routes
|--------------------------------------------------------------------------
*/

// Main sitemap index
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');

// Individual sitemaps (served from /sitemaps/ directory)
Route::get('/sitemaps/{filename}', [SitemapController::class, 'show'])
    ->where('filename', '.*\.xml')
    ->name('sitemap.show');