#!/usr/bin/env php
<?php
// Create test user for Playwright E2E tests

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/config/bootstrap.php';

use Cake\Datasource\ConnectionManager;
use Authentication\PasswordHasher\DefaultPasswordHasher;

$connection = ConnectionManager::get('default');

// Check if organization exists
$org = $connection->execute('SELECT id FROM organizations WHERE name = ?', ['Test Organization'])->fetch();

if (!$org) {
    echo "Creating organization...\n";
    $connection->execute('INSERT INTO organizations (name, created, modified) VALUES (?, NOW(), NOW())', ['Test Organization']);
    $orgId = $connection->execute('SELECT LAST_INSERT_ID() as id')->fetch()['id'];
} else {
    $orgId = $org['id'];
    echo "Organization exists (ID: $orgId)\n";
}

// Check if user exists
$user = $connection->execute('SELECT id FROM users WHERE email = ?', ['sysadmin@fairnestplan.z11.de'])->fetch();

if (!$user) {
    echo "Creating test user...\n";
    $hasher = new DefaultPasswordHasher();
    $hashedPassword = $hasher->hash('84hbfUb_3dsf');
    
    $connection->execute(
        'INSERT INTO users (organization_id, email, password, role, created, modified) VALUES (?, ?, ?, ?, NOW(), NOW())',
        [$orgId, 'sysadmin@fairnestplan.z11.de', $hashedPassword, 'admin']
    );
    
    echo "✅ Test user created successfully!\n";
} else {
    echo "✅ Test user already exists!\n";
}

echo "\nLogin credentials:\n";
echo "  Email: sysadmin@fairnestplan.z11.de\n";
echo "  Password: 84hbfUb_3dsf\n";
