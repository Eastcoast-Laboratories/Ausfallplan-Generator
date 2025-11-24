<?php
declare(strict_types=1);

/**
 * CLI-specific bootstrap
 * 
 * Auto-clear cache after migrations
 */

use Cake\Cache\Cache;
use Cake\Event\EventManager;

// Listen for migrations completion
EventManager::instance()->on('Migrations.afterMigrate', function () {
    // Clear all caches after migration
    try {
        Cache::clear('_cake_core_');
        Cache::clear('_cake_model_');
        Cache::clear('default');
        
        // Clear cache directories
        $cacheDirs = [
            TMP . 'cache' . DS . 'models' . DS . '*',
            TMP . 'cache' . DS . 'persistent' . DS . '*',
        ];
        
        foreach ($cacheDirs as $pattern) {
            $files = glob($pattern);
            if ($files) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
            }
        }
        
        echo "✓ Cache cleared after migration\n";
    } catch (\Exception $e) {
        echo "⚠ Could not clear cache: " . $e->getMessage() . "\n";
    }
});
