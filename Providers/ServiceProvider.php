<?php

namespace Modules\SitemapFeed\Providers;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Modules\SitemapFeed\Services\SitemapService;

class ServiceProvider extends BaseServiceProvider
{
    protected string $moduleName = 'SitemapFeed';

    protected string $moduleNameLower = 'sitemapfeed';

    public function boot(): void
    {
        $this->registerConfig();
        $this->registerCommands();
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(SitemapService::class, function ($app) {
            return new SitemapService();
        });
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'),
            $this->moduleNameLower
        );
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\SitemapFeed\Console\GenerateSitemapCommand::class,
            ]);
        }
    }

    public function provides(): array
    {
        return [
            SitemapService::class,
        ];
    }
}