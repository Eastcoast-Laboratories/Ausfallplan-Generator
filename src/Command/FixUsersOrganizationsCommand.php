<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * Fix Users Organizations Command
 * 
 * Fixes the issue where users don't have organization_users entries
 * after migrating to the new user/organization structure.
 * 
 * Usage: bin/cake fix_users_organizations
 */
class FixUsersOrganizationsCommand extends Command
{
    /**
     * Hook method for defining this command's option parser.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);
        $parser->setDescription('Fix users without organization_users entries');

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
        $io->out('🔧 Fixing Users/Organizations Structure...');
        $io->out('');

        // Get tables
        $usersTable = $this->fetchTable('Users');
        $orgUsersTable = $this->fetchTable('OrganizationUsers');
        $orgsTable = $this->fetchTable('Organizations');

        // 1. Check if organization_users table exists
        try {
            $orgUsersTable->find()->limit(1)->first();
            $io->success('✅ organization_users table exists');
        } catch (\Exception $e) {
            $io->error('❌ organization_users table does NOT exist!');
            $io->error('Please run migrations first: bin/cake migrations migrate');
            return self::CODE_ERROR;
        }

        // 2. Find users without organization_users entries
        $io->out('🔍 Checking for users without organization entries...');
        
        $orphanUsers = $usersTable->find()
            ->leftJoinWith('OrganizationUsers')
            ->where(['OrganizationUsers.id IS' => null])
            ->all();

        $orphanCount = $orphanUsers->count();
        
        if ($orphanCount === 0) {
            $io->success('✅ All users have organization entries - nothing to fix!');
            return self::CODE_SUCCESS;
        }

        $io->warning("Found {$orphanCount} users without organization entries:");
        foreach ($orphanUsers as $user) {
            $io->out("   - {$user->email} (ID: {$user->id})");
        }
        $io->out('');

        // 3. Get first organization (or create one if none exists)
        $org = $orgsTable->find()->first();
        
        if (!$org) {
            $io->error('❌ No organizations found in database!');
            $io->out('Creating default organization...');
            
            $org = $orgsTable->newEntity([
                'name' => 'Default Organization',
            ]);
            
            if (!$orgsTable->save($org)) {
                $io->error('Failed to create organization!');
                return self::CODE_ERROR;
            }
            
            $io->success("✅ Created organization: {$org->name} (ID: {$org->id})");
        } else {
            $io->info("Using organization: {$org->name} (ID: {$org->id})");
        }
        $io->out('');

        // 4. Ask for confirmation
        $io->out("This will create organization_users entries for {$orphanCount} users.");
        $io->out("All users will be assigned to organization: {$org->name}");
        $io->out('');
        
        $confirm = $io->askChoice('Do you want to continue?', ['yes', 'no'], 'yes');
        
        if ($confirm !== 'yes') {
            $io->warning('Aborted by user');
            return self::CODE_SUCCESS;
        }

        // 5. Create organization_users entries
        $io->out('🔧 Creating organization_users entries...');
        $created = 0;
        $failed = 0;

        foreach ($orphanUsers as $user) {
            $orgUser = $orgUsersTable->newEntity([
                'user_id' => $user->id,
                'organization_id' => $org->id,
                'role' => $user->is_system_admin ? 'org_admin' : 'editor',
                'is_primary' => true,
                'joined_at' => new \DateTime(),
            ]);

            if ($orgUsersTable->save($orgUser)) {
                $created++;
                $io->verbose("   ✅ {$user->email}");
            } else {
                $failed++;
                $io->error("   ❌ {$user->email}");
            }
        }

        $io->out('');
        $io->success("✅ Created {$created} organization_users entries");
        
        if ($failed > 0) {
            $io->warning("⚠️  Failed to create {$failed} entries");
        }

        // 6. Verify fix
        $io->out('');
        $io->out('📋 Verification:');
        
        $allUsers = $usersTable->find()
            ->contain(['OrganizationUsers.Organizations'])
            ->all();

        foreach ($allUsers as $user) {
            $orgUser = $user->organization_users[0] ?? null;
            if ($orgUser) {
                $io->out("   ✅ {$user->email} → {$orgUser->organization->name} ({$orgUser->role})");
            } else {
                $io->error("   ❌ {$user->email} → NO ORGANIZATION!");
            }
        }

        $io->out('');
        $io->success('🎉 Fix completed!');
        $io->out('');
        $io->out('Next steps:');
        $io->out('1. Clear cache: bin/cake cache clear_all');
        $io->out('2. Test login and child creation');
        // eval 'docker compose -f docker/docker-compose.yml exec -T app bin/cake cache clear_all'

        return self::CODE_SUCCESS;
    }
}
