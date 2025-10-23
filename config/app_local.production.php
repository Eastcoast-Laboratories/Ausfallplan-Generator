<?php
use function Cake\Core\env;

// Load .env file
if (!env('APP_NAME') && file_exists(dirname(__DIR__) . '/.env')) {
    $dotenv = new \josegonzalez\Dotenv\Loader([dirname(__DIR__) . '/.env']);
    $dotenv->parse()
        ->putenv()
        ->toEnv()
        ->toServer();
}

return [
    'debug' => filter_var(env('DEBUG', false), FILTER_VALIDATE_BOOLEAN),

    'Security' => [
        'salt' => env('SECURITY_SALT', '__SALT__'),
    ],

    'Datasources' => [
        'default' => [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Mysql',
            'host' => 'localhost',
            'username' => 'ausfallplan_generator',
            'password' => 'i1aeLZFUmoo7mWdy',
            'database' => 'ausfallplan_generator',
            'encoding' => 'utf8mb4',
            'timezone' => 'UTC',
            'cacheMetadata' => true,
            'quoteIdentifiers' => false,
            'persistent' => false,
        ],
        'test' => [
            'host' => 'localhost',
            'username' => 'my_app',
            'password' => 'secret',
            'database' => 'test_myapp',
            'url' => env('DATABASE_TEST_URL', 'sqlite://127.0.0.1/tmp/tests.sqlite'),
        ],
    ],
];
