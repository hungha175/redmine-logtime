<?php

return [
    'url' => env('REDMINE_URL', 'https://redmine.gotit.vn'),
    'api_key' => env('REDMINE_API_KEY', ''),
    'cookie_file' => storage_path('app/redmine_cookie.txt'),
    'ssl_verify' => env('REDMINE_SSL_VERIFY', false),
    'timeout' => (int) env('REDMINE_TIMEOUT', 15),
    'cache_ttl_issues' => (int) env('REDMINE_CACHE_ISSUES', 120),
    'cache_ttl_activities' => (int) env('REDMINE_CACHE_ACTIVITIES', 600),
    'cache_ttl_spent' => (int) env('REDMINE_CACHE_SPENT', 60),
];
