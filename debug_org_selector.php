<?php
// Quick debug script for organization selector
require 'vendor/autoload.php';
require 'config/bootstrap.php';

use Cake\Datasource\FactoryLocator;

// Get Organizations table
$orgsTable = FactoryLocator::get('Table')->get('Organizations');
$orgUsersTable = FactoryLocator::get('Table')->get('OrganizationUsers');

echo "=== DEBUG ORGANIZATION SELECTOR ===\n\n";

// Get all organizations
$allOrgs = $orgsTable->find()->all();
echo "Total organizations in DB: " . $allOrgs->count() . "\n";
foreach ($allOrgs as $org) {
    echo "  - ID: {$org->id}, Name: {$org->name}\n";
}

// Get organization_users for admin (user_id = 1)
echo "\nOrganizationUsers for admin (user_id=1):\n";
$orgUsers = $orgUsersTable->find()
    ->where(['user_id' => 1])
    ->contain(['Organizations'])
    ->all();

echo "Count: " . $orgUsers->count() . "\n";
foreach ($orgUsers as $ou) {
    echo "  - OrgUser ID: {$ou->id}, Org ID: {$ou->organization_id}, Org Name: ";
    echo $ou->organization ? $ou->organization->name : 'NULL';
    echo "\n";
}

// Test getUserOrganizations logic
echo "\nSimulating getUserOrganizations() for system admin:\n";
$userIsSystemAdmin = true;

if ($userIsSystemAdmin) {
    $orgsList = $orgsTable->find()->all()->toArray();
    echo "System admin sees ALL organizations: " . count($orgsList) . "\n";
    
    // Convert to id => name array
    $orgsArray = [];
    foreach ($orgsList as $org) {
        $orgsArray[$org->id] = $org->name;
    }
    
    echo "Organizations array for select:\n";
    print_r($orgsArray);
}
