<?php
 
return [
    'auth_domain'              => env('AUTH_DOMAIN', null),
    'official_domain'          => env('OFFICIAL_DOMAIN', null),
    'nginx_vhost_path'         => env('NGINX_VHOST_PATH', null),
    'app_version'              => env('APP_VERSION', '1.0.0'),
    'request_concurrent_limit' => env('REQUEST_CONCURRENT_LIMIT', 100)
];
