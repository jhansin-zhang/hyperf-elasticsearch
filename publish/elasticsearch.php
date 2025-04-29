<?php

declare(strict_types=1);

return [
    'default' => [
        'hosts' => [env('ES_DEFAULT_HOST', 'http://127.0.0.1:9200')],
        'max_connections' => env('ES_MAX_CONNECTIONS', 10),
        'timeout' => env('ES_TIMEOUT', 2.0),
        'username' => env('ES_USERNAME', 'xxx'),
        'password' => env('ES_PASSWORD', 'xxx'),
    ]
];
