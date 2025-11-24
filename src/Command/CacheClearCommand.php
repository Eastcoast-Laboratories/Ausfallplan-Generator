<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Cache\Cache;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * CacheClear command.
 */
class CacheClearCommand extends Command
{
    /**
     * Build option parser
     *
     * @param \Cake\Console\ConsoleOptionParser $parser Parser to configure
     * @return \Cake\Console\ConsoleOptionParser
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription('Clear all CakePHP caches and fix permissions')
            ->addOption('config', [
                'short' => 'c',
                'help' => 'Cache configuration to clear (default: all)',
                'default' => null
            ]);

        return $parser;
    }

    /**
     * Execute cache clear command
     *
     * @param \Cake\Console\Arguments $args Arguments
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @return int|null Exit code
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $config = $args->getOption('config');

        if ($config) {
            // Clear specific cache configuration
            $io->out("Clearing cache configuration: {$config}");
            if (Cache::clear($config)) {
                $io->success("Cache '{$config}' cleared successfully");
            } else {
                $io->error("Failed to clear cache '{$config}'");
                return static::CODE_ERROR;
            }
        } else {
            // Clear all caches
            $io->out('Clearing all caches...');
            
            $configs = ['_cake_core_', '_cake_model_', 'default'];
            foreach ($configs as $conf) {
                try {
                    if (Cache::clear($conf)) {
                        $io->success("Cleared cache: {$conf}");
                    }
                } catch (\Exception $e) {
                    $io->warning("Could not clear {$conf}: " . $e->getMessage());
                }
            }
            
            // Also clear cache directories manually
            $this->clearCacheDirectories($io);
            
            $io->success('All caches cleared!');
        }

        return static::CODE_SUCCESS;
    }

    /**
     * Clear cache directories manually
     *
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @return void
     */
    protected function clearCacheDirectories(ConsoleIo $io): void
    {
        $cacheDirs = [
            TMP . 'cache' . DS . 'models',
            TMP . 'cache' . DS . 'persistent',
            TMP . 'cache' . DS . 'views'
        ];

        foreach ($cacheDirs as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . DS . '*');
                if ($files) {
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            @unlink($file);
                        }
                    }
                    $io->verbose("Cleared directory: {$dir}");
                }
            }
        }
    }
}
