#!/usr/bin/env php
<?php
require '/var/www/html/config/bootstrap.php';

use Cake\ORM\TableRegistry;

$orgsTable = TableRegistry::getTableLocator()->get('Organizations');
$usersTable = TableRegistry::getTableLocator()->get('Users');

// Create test org
$org = $orgsTable->find()->where(['name' => 'Test Kita E2E'])->first();
if (!$org) {
    $org = $orgsTable->newEntity(['name' => 'Test Kita E2E']);
    $orgsTable->save($org);
    echo "✓ Test organization created\n";
} else {
    echo "✓ Test organization exists\n";
}

// Create admin
$admin = $usersTable->find()->where(['email' => 'admin@test.com'])->first();
if (!$admin) {
    $admin = $usersTable->newEntity([
        'organization_id' => $org->id,
        'email' => 'admin@test.com',
        'password' => '84hbfUb_3dsf',
        'role' => 'admin',
        'status' => 'active',
        'email_verified' => true,
    ]);
    $usersTable->save($admin);
    echo "✓ Admin created: admin@test.com / 84hbfUb_3dsf\n";
} else {
    echo "✓ Admin exists\n";
}

// Create editor
$editor = $usersTable->find()->where(['email' => 'editor@test.com'])->first();
if (!$editor) {
    $editor = $usersTable->newEntity([
        'organization_id' => $org->id,
        'email' => 'editor@test.com',
        'password' => '84hbfUb_3dsf',
        'role' => 'editor',
        'status' => 'active',
        'email_verified' => true,
    ]);
    $usersTable->save($editor);
    echo "✓ Editor created: editor@test.com / 84hbfUb_3dsf\n";
} else {
    echo "✓ Editor exists\n";
}

// Create viewer
$viewer = $usersTable->find()->where(['email' => 'viewer@test.com'])->first();
if (!$viewer) {
    $viewer = $usersTable->newEntity([
        'organization_id' => $org->id,
        'email' => 'viewer@test.com',
        'password' => '84hbfUb_3dsf',
        'role' => 'viewer',
        'status' => 'active',
        'email_verified' => true,
    ]);
    $usersTable->save($viewer);
    echo "✓ Viewer created: viewer@test.com / 84hbfUb_3dsf\n";
} else {
    echo "✓ Viewer exists\n";
}

echo "\n🎉 Test users ready!\n";
echo "\nTest credentials:\n";
echo "  Admin:  admin@test.com  / 84hbfUb_3dsf\n";
echo "  Editor: editor@test.com / 84hbfUb_3dsf\n";
echo "  Viewer: viewer@test.com / 84hbfUb_3dsf\n";
