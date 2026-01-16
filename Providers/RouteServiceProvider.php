<?php

namespace Modules\SitemapFeed\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as BaseServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapAdminRoutes();
        $this->mapWebRoutes();
    }

    protected function mapAdminRoutes(): void
    {
        Route::middleware([
            'web',
            'auth:sanctum',
            config('jetstream.auth_session'),
            'verified',
        ])
            ->prefix('admin')
            ->group(module_path('SitemapFeed', '/Routes/admin.php'));
    }

    protected function mapWebRoutes(): void
    {
        Route::middleware(['web'])
            ->group(module_path('SitemapFeed', '/Routes/web.php'));
    }
}