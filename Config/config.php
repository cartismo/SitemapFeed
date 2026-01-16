<?php

return [
    'name' => 'SitemapFeed',

    'settings' => [
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
    ],
];