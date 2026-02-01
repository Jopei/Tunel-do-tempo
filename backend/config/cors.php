<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://otuneldotempo.com.br',
        'http://www.otuneldotempo.com.br',
        'https://otuneldotempo.com.br',
        'https://www.otuneldotempo.com.br',
        'http://localhost:5173',
    ],

    'allowed_headers' => [
        'Content-Type',
        'X-API-KEY',
        'Authorization',
        'Accept',
        'Origin',
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];

