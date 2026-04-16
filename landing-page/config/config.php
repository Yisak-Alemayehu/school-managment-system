<?php
/**
 * Eduelevate Configuration
 */
return [
    'app' => [
        'name'     => 'Eduelevate',
        'version'  => '1.0.0',
        'env'      => getenv('APP_ENV') ?: 'production',
        'debug'    => (getenv('APP_ENV') ?: 'production') === 'development',
        'timezone' => 'Africa/Addis_Ababa',
        'url'      => getenv('EDUELEVATE_URL') ?: '',
    ],
    'db' => [
        'host'     => getenv('DB_HOST') ?: 'localhost',
        'port'     => getenv('DB_PORT') ?: '3306',
        'name'     => getenv('EDUELEVATE_DB_NAME') ?: 'eduelevate',
        'user'     => getenv('DB_USER') ?: 'root',
        'pass'     => getenv('DB_PASS') ?: '0000',
        'charset'  => 'utf8mb4',
    ],
    'session' => [
        'name'     => 'eduelevate_session',
        'lifetime' => 7200,
    ],
    'upload' => [
        'max_size'      => 5 * 1024 * 1024, // 5MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'],
        'path'          => __DIR__ . '/../uploads',
    ],
    'security' => [
        'csrf_token_name' => 'csrf_token',
        'bcrypt_cost'     => 12,
    ],
];
