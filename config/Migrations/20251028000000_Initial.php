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
    }
}
