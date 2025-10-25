<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * CheckAdmin command - Check if is_system_admin column exists and user status
 */
class CheckAdminCommand extends Command
{
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);
        $parser->setDescription('Check admin user and is_system_admin column');
        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $usersTable = $this->fetchTable('Users');
        
        // Check if column exists by trying to query it
        try {
            $io->info('Checking if is_system_admin column exists...');
            
            $user = $usersTable->find()
                ->where(['email' => 'admin@demo.kita'])
                ->first();
            
            if (!$user) {
                $io->error('User admin@demo.kita not found!');
                return self::CODE_ERROR;
            }
            
            $io->success('User found!');
            $io->out('ID: ' . $user->id);
            $io->out('Email: ' . $user->email);
            
            // Try to access is_system_admin
            if (property_exists($user, 'is_system_admin')) {
                $io->out('is_system_admin exists: ' . ($user->is_system_admin ? 'TRUE' : 'FALSE'));
            } else {
                $io->warning('is_system_admin property does NOT exist on User entity!');
                
                // Check database directly
                $connection = $usersTable->getConnection();
                $columns = $connection->execute('DESCRIBE users')->fetchAll('assoc');
                
                $io->out('');
                $io->out('Columns in users table:');
                foreach ($columns as $col) {
                    $io->out('  - ' . $col['Field'] . ' (' . $col['Type'] . ')');
                }
            }
            
            return self::CODE_SUCCESS;
            
        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            return self::CODE_ERROR;
        }
    }
}
