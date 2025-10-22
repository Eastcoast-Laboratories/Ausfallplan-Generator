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
            return self::CODE_SUCCESS;
        }

        // Erstelle Admin User
        $io->info('Creating admin user...');
        $user = $usersTable->newEntity([
            'organization_id' => $org->id,
            'email' => 'admin@demo.kita',
            'password' => 'asbdasdaddd',
            'role' => 'admin',
            'email_verified_at' => date('Y-m-d H:i:s')
        ]);

        if ($usersTable->save($user)) {
            $io->success('Admin User created successfully!');
            $io->out('');
            $io->out('Login credentials:');
            $io->out('  Email:    admin@demo.kita');
            $io->out('  Password: asbdasdaddd');
            $io->out('  Role:     admin');
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
