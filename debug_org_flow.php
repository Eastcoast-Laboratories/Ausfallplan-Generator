<?php
// Debug the complete flow
require 'vendor/autoload.php';
require 'config/bootstrap.php';

use Cake\Datasource\FactoryLocator;
use Cake\Collection\Collection;

echo "=== TESTING ORGANIZATION FLOW ===\n\n";

// Get Organizations table
$orgsTable = FactoryLocator::get('Table')->get('Organizations');

// Simulate system admin getUserOrganizations()
echo "1. getUserOrganizations() for system admin:\n";
$orgEntities = $orgsTable->find()->all()->toArray();
echo "   - Count: " . count($orgEntities) . "\n";
echo "   - First org class: " . (count($orgEntities) > 0 ? get_class($orgEntities[0]) : 'N/A') . "\n";

if (count($orgEntities) > 0) {
    echo "   - First org id: " . $orgEntities[0]->id . "\n";
    echo "   - First org name: " . $orgEntities[0]->name . "\n";
}

// Test collection()->combine()
echo "\n2. Testing collection()->combine():\n";
$collection = new Collection($orgEntities);
$organizations = $collection->combine('id', 'name')->toArray();
echo "   - Result count: " . count($organizations) . "\n";
echo "   - Result:\n";
print_r($organizations);

// Test canSelectOrganization
echo "\n3. Testing canSelectOrganization:\n";
$is_system_admin = true;
$canSelectOrganization = $is_system_admin || count($organizations) > 1;
echo "   - is_system_admin: " . ($is_system_admin ? 'YES' : 'NO') . "\n";
echo "   - count(organizations): " . count($organizations) . "\n";
echo "   - canSelectOrganization: " . ($canSelectOrganization ? 'YES' : 'NO') . "\n";
