<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Initial database schema migration
 * 
 * This is the complete initial schema for the application.
 * All future changes should be in separate migration files.
 */
class Initial extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        // Organizations table
        $organizations = $this->table('organizations');
        $organizations
            ->addColumn('name', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('is_active', 'boolean', [
                'default' => true,
                'null' => false,
            ])
            ->addColumn('settings', 'text', [
                'null' => true,
            ])
            ->addColumn('contact_email', 'string', [
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('contact_phone', 'string', [
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true,
            ])
            ->create();

        // Users table
        $users = $this->table('users');
        $users
            ->addColumn('email', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('password', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('is_system_admin', 'boolean', [
                'default' => false,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true,
            ])
            ->addColumn('email_verified', 'integer', [
                'default' => 0,
                'null' => false,
            ])
            ->addColumn('email_token', 'string', [
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('status', 'string', [
                'limit' => 50,
                'default' => 'pending',
                'null' => false,
            ])
            ->addColumn('approved_at', 'datetime', [
                'null' => true,
            ])
            ->addColumn('approved_by', 'integer', [
                'null' => true,
            ])
            ->addIndex(['email'], ['unique' => true])
            ->addIndex(['approved_by'])
            ->create();

        // Organization Users table (junction table)
        $organizationUsers = $this->table('organization_users');
        $organizationUsers
            ->addColumn('organization_id', 'integer', [
                'null' => false,
            ])
            ->addColumn('user_id', 'integer', [
                'null' => false,
            ])
            ->addColumn('role', 'string', [
                'limit' => 50,
                'default' => 'viewer',
                'null' => false,
            ])
            ->addColumn('is_primary', 'boolean', [
                'default' => false,
                'null' => false,
            ])
            ->addColumn('joined_at', 'datetime', [
                'null' => false,
            ])
            ->addColumn('invited_by', 'integer', [
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true,
            ])
            ->addIndex(['organization_id'])
            ->addIndex(['user_id'])
            ->addIndex(['invited_by'])
            ->addIndex(['organization_id', 'user_id'], ['unique' => true])
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->addForeignKey('invited_by', 'users', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
            ])
            ->create();

        // Schedules table
        $schedules = $this->table('schedules');
        $schedules
            ->addColumn('organization_id', 'integer', [
                'null' => false,
            ])
            ->addColumn('title', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('starts_on', 'date', [
                'null' => true,
            ])
            ->addColumn('ends_on', 'date', [
                'null' => true,
            ])
            ->addColumn('state', 'string', [
                'limit' => 50,
                'default' => 'draft',
                'null' => true,
            ])
            ->addColumn('capacity_per_day', 'integer', [
                'default' => 9,
                'null' => true,
            ])
            ->addColumn('days_count', 'integer', [
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true,
            ])
            ->addColumn('user_id', 'integer', [
                'null' => true,
            ])
            ->addIndex(['organization_id'])
            ->addIndex(['user_id'])
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
            ])
            ->create();

        // Sibling Groups table
        $siblingGroups = $this->table('sibling_groups');
        $siblingGroups
            ->addColumn('organization_id', 'integer', [
                'null' => false,
            ])
            ->addColumn('label', 'string', [
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true,
            ])
            ->addIndex(['organization_id'])
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->create();

        // Children table
        $children = $this->table('children');
        $children
            ->addColumn('organization_id', 'integer', [
                'null' => false,
            ])
            ->addColumn('schedule_id', 'integer', [
                'null' => true,
                'comment' => 'Assigned schedule - null means on waitlist',
            ])
            ->addColumn('organization_order', 'integer', [
                'null' => true,
                'comment' => 'Sort order within organization',
            ])
            ->addColumn('waitlist_order', 'integer', [
                'null' => true,
                'comment' => 'Sort order in waitlist',
            ])
            ->addColumn('name', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('is_active', 'boolean', [
                'default' => true,
                'null' => true,
            ])
            ->addColumn('is_integrative', 'boolean', [
                'default' => false,
                'null' => true,
            ])
            ->addColumn('sibling_group_id', 'integer', [
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true,
            ])
            ->addColumn('gender', 'string', [
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('birthdate', 'date', [
                'null' => true,
            ])
            ->addColumn('postal_code', 'string', [
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('last_name', 'string', [
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('display_name', 'string', [
                'limit' => 100,
                'null' => true,
                'comment' => 'Pre-formatted name for display in reports based on anonymization choice',
            ])
            ->addIndex(['sibling_group_id'])
            ->addIndex(['schedule_id'])
            ->addIndex(['organization_id', 'organization_order'])
            ->addIndex(['organization_id', 'waitlist_order'])
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->addForeignKey('schedule_id', 'schedules', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
            ])
            ->addForeignKey('sibling_group_id', 'sibling_groups', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
            ])
            ->create();

        // Rules table
        $rules = $this->table('rules');
        $rules
            ->addColumn('schedule_id', 'integer', [
                'null' => false,
            ])
            ->addColumn('type', 'string', [
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('parameters', 'text', [
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true,
            ])
            ->addIndex(['schedule_id'])
            ->addForeignKey('schedule_id', 'schedules', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->create();

        // Password Resets table
        $passwordResets = $this->table('password_resets');
        $passwordResets
            ->addColumn('user_id', 'integer', [
                'null' => false,
            ])
            ->addColumn('reset_token', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('reset_code', 'string', [
                'limit' => 10,
                'null' => false,
            ])
            ->addColumn('expires_at', 'datetime', [
                'null' => false,
            ])
            ->addColumn('used_at', 'datetime', [
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'null' => true,
            ])
            ->addIndex(['user_id'])
            ->addIndex(['reset_token'], ['name' => 'idx_password_resets_token'])
            ->addIndex(['reset_code'], ['name' => 'idx_password_resets_code'])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->create();

        // Insert default data
        $this->insertDefaultData();
    }

    /**
     * Insert default organization and users
     */
    private function insertDefaultData(): void
    {
        // Default organization
        $this->table('organizations')->insert([
            [
                'id' => 1,
                'name' => 'Demo Kita',
                'is_active' => 1,
                'contact_email' => 'demo@kita.de',
                'contact_phone' => '0123-456789',
                'created' => date('Y-m-d H:i:s'),
                'modified' => date('Y-m-d H:i:s'),
            ]
        ])->saveData();

        // Default users (Password: 84fhr38hf43iahfuX_2)
        $passwordHash = '$2y$12$aa8WQuZBRhtVemDoA7DgTOxyryszPgabWRE1jvIZYMCX.k.cl2B7O';
        $now = date('Y-m-d H:i:s');
        
        $this->table('users')->insert([
            [
                'id' => 1,
                'email' => 'admin@demo.kita',
                'password' => $passwordHash,
                'is_system_admin' => 1,
                'created' => $now,
                'modified' => $now,
                'email_verified' => 1,
                'email_token' => null,
                'status' => 'active',
                'approved_at' => $now,
                'approved_by' => null,
            ],
            [
                'id' => 2,
                'email' => 'editor@demo.kita',
                'password' => $passwordHash,
                'is_system_admin' => 0,
                'created' => $now,
                'modified' => $now,
                'email_verified' => 1,
                'email_token' => null,
                'status' => 'active',
                'approved_at' => $now,
                'approved_by' => 1,
            ],
            [
                'id' => 3,
                'email' => 'viewer@demo.kita',
                'password' => $passwordHash,
                'is_system_admin' => 0,
                'created' => $now,
                'modified' => $now,
                'email_verified' => 1,
                'email_token' => null,
                'status' => 'active',
                'approved_at' => $now,
                'approved_by' => 1,
            ],
        ])->saveData();

        // Link users to organization
        $this->table('organization_users')->insert([
            [
                'id' => 1,
                'organization_id' => 1,
                'user_id' => 1,
                'role' => 'admin',
                'is_primary' => 1,
                'joined_at' => $now,
                'invited_by' => null,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'id' => 2,
                'organization_id' => 1,
                'user_id' => 2,
                'role' => 'editor',
                'is_primary' => 0,
                'joined_at' => $now,
                'invited_by' => 1,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'id' => 3,
                'organization_id' => 1,
                'user_id' => 3,
                'role' => 'viewer',
                'is_primary' => 0,
                'joined_at' => $now,
                'invited_by' => 1,
                'created' => $now,
                'modified' => $now,
            ],
        ])->saveData();
    }
}
