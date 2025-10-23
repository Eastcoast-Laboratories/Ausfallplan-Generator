#!/bin/bash
# Setup test users for E2E tests

echo "🔧 Setting up test users for E2E tests..."

docker compose -f docker/docker-compose.yml exec -T app php -r "
require '/var/www/html/vendor/autoload.php';

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;

// Get connection
\$connection = ConnectionManager::get('default');

// Create organizations
\$orgsTable = TableRegistry::getTableLocator()->get('Organizations');
\$org1 = \$orgsTable->find()->where(['name' => 'Test Kita'])->first();
if (!\$org1) {
    \$org1 = \$orgsTable->newEntity(['name' => 'Test Kita']);
    \$orgsTable->save(\$org1);
    echo \"✓ Organization 'Test Kita' created\n\";
}

// Create test users
\$usersTable = TableRegistry::getTableLocator()->get('Users');

// Admin user
\$admin = \$usersTable->find()->where(['email' => 'admin@test.com'])->first();
if (!\$admin) {
    \$admin = \$usersTable->newEntity([
        'organization_id' => \$org1->id,
        'email' => 'admin@test.com',
        'password' => 'password123',
        'role' => 'admin',
        'status' => 'active',
        'email_verified' => true,
    ]);
    \$usersTable->save(\$admin);
    echo \"✓ Admin user created (admin@test.com / password123)\n\";
} else {
    echo \"✓ Admin user already exists\n\";
}

// Editor user
\$editor = \$usersTable->find()->where(['email' => 'editor@test.com'])->first();
if (!\$editor) {
    \$editor = \$usersTable->newEntity([
        'organization_id' => \$org1->id,
        'email' => 'editor@test.com',
        'password' => 'password123',
        'role' => 'editor',
        'status' => 'active',
        'email_verified' => true,
    ]);
    \$usersTable->save(\$editor);
    echo \"✓ Editor user created (editor@test.com / password123)\n\";
} else {
    echo \"✓ Editor user already exists\n\";
}

// Viewer user
\$viewer = \$usersTable->find()->where(['email' => 'viewer@test.com'])->first();
if (!\$viewer) {
    \$viewer = \$usersTable->newEntity([
        'organization_id' => \$org1->id,
        'email' => 'viewer@test.com',
        'password' => 'password123',
        'role' => 'viewer',
        'status' => 'active',
        'email_verified' => true,
    ]);
    \$usersTable->save(\$viewer);
    echo \"✓ Viewer user created (viewer@test.com / password123)\n\";
} else {
    echo \"✓ Viewer user already exists\n\";
}

echo \"\n🎉 Test users ready!\n\";
"

echo ""
echo "✅ Test users created:"
echo "   Admin:  admin@test.com  / password123"
echo "   Editor: editor@test.com / password123"
echo "   Viewer: viewer@test.com / password123"
