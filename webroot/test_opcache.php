<?php
// Test if OPcache is active
echo "OPcache Status:\n";
echo "Enabled: " . (ini_get('opcache.enable') ? 'YES' : 'NO') . "\n";
echo "Revalidate Freq: " . ini_get('opcache.revalidate_freq') . "\n";
echo "Validate Timestamps: " . ini_get('opcache.validate_timestamps') . "\n";

// Clear OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "\nOPcache CLEARED!\n";
} else {
    echo "\nOPcache reset NOT available\n";
}

// Check controller file
$controllerFile = '/var/www/html/src/Controller/WaitlistController.php';
if (file_exists($controllerFile)) {
    echo "\nController file exists: YES\n";
    echo "Modified: " . date('Y-m-d H:i:s', filemtime($controllerFile)) . "\n";
    
    // Check if it contains our debug code
    $content = file_get_contents($controllerFile);
    if (strpos($content, 'sibling_debug.log') !== false) {
        echo "Contains debug code: YES ✓\n";
    } else {
        echo "Contains debug code: NO ✗\n";
    }
} else {
    echo "\nController file exists: NO\n";
}
