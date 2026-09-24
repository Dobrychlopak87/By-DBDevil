<?php

return [
    'site' => [
        'url' => 'https://example.com/subdirectory',
        'base_path' => '/subdirectory',
        'environment' => 'production',
        'canonical_host' => 'example.com',
        'contact_email' => 'admin@example.com',
        'asset_version' => '20260922-example',
    ],
    'paths' => [
        'session' => '/absolute/path/outside/public/session',
        'cache' => '/absolute/path/outside/public/storage/cache',
        'logs' => '/absolute/path/outside/public/storage/logs',
        'uploads' => '/absolute/path/outside/public/assets/uploads',
    ],
    'limits' => [
        'max_upload_bytes' => 5242880,
    ],
    'db' => [
        'host' => 'localhost',
        'user' => 'database_user',
        'pass' => 'database_password',
        'name' => 'database_name',
    ],
    'chat_database' => [
        'dsn' => 'mysql:host=localhost;dbname=chat_database;charset=utf8mb4',
        'username' => 'chat_database_user',
        'password' => 'chat_database_password',
    ],
    'pulse_rate_hash_secret' => 'replace-with-a-random-secret-of-at-least-32-characters',
];
