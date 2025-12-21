#!/usr/bin/env php
<?php
/**
 * Translation Verification Script
 * Checks that all German translations are properly defined
 */

// Load German translations
$translationsFile = __DIR__ . '/../resources/locales/de_DE/default.php';
$translations = include $translationsFile;

// Define all English strings that should be translated
$requiredTranslations = [
    // Navigation
    'Dashboard',
    'Children',
    'Sibling Groups',
    'Schedules',
    'Waitlist',
    
    // Login & Registration
    'Login',
    'Email',
    'Password',
    'Create new account',
    'Register New Account',
    'Organization',
    'Role',
    'Already have an account? Login',
    'Create Account',
    'Please enter your email and password to access your account.',
    
    // Children
    'New Child',
    'Add Child',
    'Edit Child',
    'Name',
    'Active',
    'Inactive',
    'Integrative Child',
    'Sibling Group',
    '(No Sibling Group)',
    'Status',
    'Integrative',
    'Created',
    'Actions',
    
    // Schedules
    'New Schedule',
    'Manage Children',
    'Edit',
    'Delete',
    'Title',
    'Starts On',
    'Ends On',
    'State',
    
    // Waitlist
    'Children on Waitlist',
    'Drag to reorder',
    'Child removed from waitlist.',
    'Select Schedule',
    '-- Select Schedule --',
    'Could not remove child from waitlist.',
    'Add to Waitlist',
    'Remove from Waitlist',
    'Priority',
    'Position',
    'No children on waitlist.',
    'All children are on the waitlist.',
    'Available Children',
    
    // Sibling Groups
    'New Sibling Group',
    'Add Sibling Group',
    
    // Common
    'Submit',
    'Save',
    'Cancel',
    'Yes',
    'No',
    
    // Dashboard
    'Welcome back!',
    'Quick Actions',
    'Recent Activity',
    'Total Schedules',
    'Active Schedules',
    'Waitlist Entries',
    'Create Schedule',
    'Import CSV',
    
    // User Menu
    'Settings',
    'My Account',
    'Logout',
    'Change Language',
    
    // Links
    'Back to Schedules',
    
    // Forms
    'Minimum 8 characters',
    'Select your organization',
];

echo "====================================\n";
echo "German Translation Verification\n";
echo "====================================\n\n";

$missing = [];
$found = [];

foreach ($requiredTranslations as $english) {
    if (isset($translations[$english])) {
        $german = $translations[$english];
        $found[] = "✅ '$english' => '$german'";
    } else {
        $missing[] = "❌ MISSING: '$english'";
    }
}

// Print results
echo "Found Translations: " . count($found) . "\n";
echo "Missing Translations: " . count($missing) . "\n\n";

if (!empty($missing)) {
    echo "============== MISSING ==============\n";
    foreach ($missing as $m) {
        echo "$m\n";
    }
    echo "\n";
}

if (!empty($found)) {
    echo "============== VERIFIED ==============\n";
    foreach ($found as $f) {
        echo "$f\n";
    }
}

echo "\n====================================\n";

if (empty($missing)) {
    echo "✅ All required translations are defined!\n";
    exit(0);
} else {
    echo "❌ " . count($missing) . " translations are missing!\n";
    exit(1);
}
