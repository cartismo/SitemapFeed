<?php

use Illuminate\Support\Facades\Route;
use Modules\SitemapFeed\Http\Controllers\Admin\SettingsController;

Route::prefix('modules/feeds/sitemap-feed')->name('admin.feeds.sitemap.')->group(function () {
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/generate', [SettingsController::class, 'generate'])->name('generate');
    Route::delete('/clear', [SettingsController::class, 'clear'])->name('clear');
});