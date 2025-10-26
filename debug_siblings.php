#!/usr/bin/env php
<?php
// Debug script to check sibling query
$rootPath = '/var/www/html';
require $rootPath . '/vendor/autoload.php';

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;

// Bootstrap CakePHP
$bootstrap = $rootPath . '/config/bootstrap.php';
require $bootstrap;

echo "=== DEBUGGING SIBLING NAMES ===\n\n";

// Get Children table
$childrenTable = TableRegistry::getTableLocator()->get('Children');

// Test query for sibling_group_id = 2
echo "Testing sibling_group_id = 2:\n";
$siblings = $childrenTable->find()
    ->where([
        'sibling_group_id' => 2,
        'id !=' => 49 // Exclude N. Storch
    ])
    ->orderBy(['name' => 'ASC'])
    ->all();

echo "Found " . $siblings->count() . " siblings\n";
foreach ($siblings as $sib) {
    echo "  - {$sib->name} (ID: {$sib->id})\n";
}

echo "\n";

// Test with sibling_group_id = 4
echo "Testing sibling_group_id = 4:\n";
$siblings2 = $childrenTable->find()
    ->where([
        'sibling_group_id' => 4,
        'id !=' => 52 // Exclude Amadeus
    ])
    ->orderBy(['name' => 'ASC'])
    ->all();

echo "Found " . $siblings2->count() . " siblings\n";
foreach ($siblings2 as $sib) {
    echo "  - {$sib->name} (ID: {$sib->id})\n";
}

echo "\n";

// Now test the waitlist query
echo "=== TESTING WAITLIST QUERY ===\n\n";
$waitlistTable = TableRegistry::getTableLocator()->get('WaitlistEntries');
$waitlistEntries = $waitlistTable->find()
    ->where(['WaitlistEntries.schedule_id' => 3])
    ->contain(['Children', 'Schedules'])
    ->orderBy(['WaitlistEntries.priority' => 'ASC'])
    ->all();

echo "Found " . $waitlistEntries->count() . " waitlist entries\n\n";

foreach ($waitlistEntries as $entry) {
    echo "Entry ID: {$entry->id}\n";
    echo "  Child: {$entry->child->name} (ID: {$entry->child->id})\n";
    echo "  Sibling Group ID: " . ($entry->child->sibling_group_id ?? 'NULL') . "\n";
    
    if ($entry->child->sibling_group_id) {
        // Try to load siblings
        $sibs = $childrenTable->find()
            ->where([
                'sibling_group_id' => $entry->child->sibling_group_id,
                'id !=' => $entry->child->id
            ])
            ->orderBy(['name' => 'ASC'])
            ->all();
        
        echo "  Siblings found: " . $sibs->count() . "\n";
        $names = [];
        foreach ($sibs as $s) {
            $names[] = $s->name;
            echo "    - {$s->name} (ID: {$s->id})\n";
        }
        echo "  Names array: " . json_encode($names) . "\n";
    }
    echo "\n";
}
