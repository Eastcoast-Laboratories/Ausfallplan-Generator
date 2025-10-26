#!/usr/bin/env php
<?php
// Get HTML output of waitlist page
$rootPath = '/var/www/html';
require $rootPath . '/vendor/autoload.php';

use Cake\Http\ServerRequestFactory;
use Cake\Http\Server;

// Set environment to testing
putenv('DEBUG=true');

// Start output buffering
ob_start();

// Simulate HTTP request
$_SERVER['REQUEST_URI'] = '/waitlist?schedule_id=3';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost:8080';

// Create a mock authenticated session
$_SESSION = [
    'Auth' => [
        'id' => 1,
        'email' => 'admin@example.com',
        'role' => 'admin',
        'organization_id' => 1
    ]
];

try {
    // This won't work properly without full bootstrap, so let's use curl instead
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost:8080/waitlist?schedule_id=3");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_COOKIE, "PHPSESSID=test"); // This won't work but worth a try
    
    $html = curl_exec($ch);
    curl_close($ch);
    
    // Search for the debug div
    if (preg_match('/<div[^>]*DEBUG: siblingNames Array[^>]*>(.+?)<\/div>/s', $html, $matches)) {
        echo "\n=== FOUND DEBUG DIV ===\n";
        echo html_entity_decode(strip_tags($matches[0]));
        echo "\n=== END DEBUG DIV ===\n";
    } else {
        echo "\n❌ NO DEBUG DIV FOUND\n";
    }
    
    // Search for sibling badges
    preg_match_all('/title="Geschwister: ([^"]*)"/', $html, $titles);
    echo "\n=== SIBLING TITLES ===\n";
    foreach ($titles[1] as $title) {
        echo "Title: " . html_entity_decode($title) . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

ob_end_flush();
