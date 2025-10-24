<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * CreateAdmin command.
 */
class CreateAdminCommand extends Command
{
    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/5/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);
        $parser->setDescription('Create an admin user');

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $usersTable = $this->fetchTable('Users');
        $organizationsTable = $this->fetchTable('Organizations');

        // Erstelle oder hole Organisation
        $org = $organizationsTable->find()->first();
        if (!$org) {
            $io->info('Creating organization...');
            $org = $organizationsTable->newEntity([
                'name' => 'Demo Kita'
            ]);
            if (!$organizationsTable->save($org)) {
                $io->error('Failed to create organization!');
                return self::CODE_ERROR;
            }
        }

        // Prüfe ob Admin bereits existiert
        $existingUser = $usersTable->findByEmail('admin@demo.kita')->first();
        if ($existingUser) {
            $io->warning('Admin user already exists!');
            $io->out('Email: admin@demo.kita');
            $io->out('is_system_admin: ' . ($existingUser->is_system_admin ? 'Yes' : 'No'));
            
            // Update to system admin if not already
            $needsUpdate = false;
            if (!$existingUser->is_system_admin) {
                $existingUser->is_system_admin = true;
                $needsUpdate = true;
                $io->info('Updated is_system_admin = true');
            }
            if (!$existingUser->email_verified) {
                $existingUser->email_verified = true;
                $needsUpdate = true;
                $io->info('Updated email_verified = true');
            }
            if ($existingUser->status !== 'active') {
                $existingUser->status = 'active';
                $needsUpdate = true;
                $io->info('Updated status = active');
            }
            if ($needsUpdate) {
                $usersTable->save($existingUser);
                $io->success('Admin user updated successfully!');
            }
            return self::CODE_SUCCESS;
        }

        // Erstelle Admin User
        $io->info('Creating admin user...');
        $user = $usersTable->newEntity([
            'email' => 'admin@demo.kita',
            'password' => 'asbdasdaddd',
            'is_system_admin' => true, // System-wide admin access
            'status' => 'active',
            'email_verified' => true
        ]);

        if ($usersTable->save($user)) {
            // Create organization_users entry for primary organization
            $orgUsersTable = $this->fetchTable('OrganizationUsers');
            $orgUser = $orgUsersTable->newEntity([
                'organization_id' => $org->id,
                'user_id' => $user->id,
                'role' => 'org_admin',
                'is_primary' => true,
                'joined_at' => new \DateTime()
            ]);
            $orgUsersTable->save($orgUser);
            
            $io->success('System Admin User created successfully!');
            $io->out('');
            $io->out('Login credentials:');
            $io->out('  Email:    admin@demo.kita');
            $io->out('  Password: asbdasdaddd');
            $io->out('  Type:     System Admin (is_system_admin = true)');
            $io->out('');
            return self::CODE_SUCCESS;
        } else {
            $io->error('Failed to create admin user!');
            $io->out('Errors:');
            foreach ($user->getErrors() as $field => $errors) {
                foreach ($errors as $error) {
                    $io->err("  - {$field}: {$error}");
                }
            }
            return self::CODE_ERROR;
        }
    }
}
