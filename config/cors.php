<?php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    // Cho phép origin của bạn (hoặc tất cả để dev local)
    'allowed_origins' => ['*'], // hoặc ['http://localhost:8080', 'http://192.168.68.176:8080']
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
