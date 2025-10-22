<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * CreateInitialSchema migration.
 */
class CreateInitialSchema extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        // Organizations table
        $this->table('organizations')
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('locale', 'string', ['limit' => 10, 'default' => 'de_DE'])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->create();

        // Users table
        $this->table('users')
            ->addColumn('organization_id', 'integer', ['null' => false])
            ->addColumn('email', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('password', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('role', 'string', ['limit' => 20, 'default' => 'viewer'])
            ->addColumn('email_verified_at', 'datetime', ['null' => true])
            ->addColumn('last_login_at', 'datetime', ['null' => true])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['organization_id', 'email'], ['unique' => true])
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->create();

        // Sibling groups table
        $this->table('sibling_groups')
            ->addColumn('organization_id', 'integer', ['null' => false])
            ->addColumn('label', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->create();

        // Children table
        $this->table('children')
            ->addColumn('organization_id', 'integer', ['null' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('is_integrative', 'boolean', ['default' => false])
            ->addColumn('is_active', 'boolean', ['default' => true])
            ->addColumn('sibling_group_id', 'integer', ['null' => true])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['organization_id', 'name'], ['unique' => true])
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->addForeignKey('sibling_group_id', 'sibling_groups', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE'
            ])
            ->create();

        // Schedules table
        $this->table('schedules')
            ->addColumn('organization_id', 'integer', ['null' => false])
            ->addColumn('title', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('starts_on', 'date', ['null' => false])
            ->addColumn('ends_on', 'date', ['null' => false])
            ->addColumn('state', 'string', ['limit' => 20, 'default' => 'draft'])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->create();

        // Schedule days table
        $this->table('schedule_days')
            ->addColumn('schedule_id', 'integer', ['null' => false])
            ->addColumn('title', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('position', 'integer', ['default' => 0])
            ->addColumn('capacity', 'integer', ['default' => 9])
            ->addColumn('start_child_id', 'integer', ['null' => true])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addForeignKey('schedule_id', 'schedules', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->create();

        // Assignments table
        $this->table('assignments')
            ->addColumn('schedule_day_id', 'integer', ['null' => false])
            ->addColumn('child_id', 'integer', ['null' => false])
            ->addColumn('weight', 'integer', ['default' => 1])
            ->addColumn('source', 'string', ['limit' => 20, 'default' => 'manual'])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['schedule_day_id', 'child_id'], ['unique' => true])
            ->addForeignKey('schedule_day_id', 'schedule_days', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->addForeignKey('child_id', 'children', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->create();

        // Waitlist entries table
        $this->table('waitlist_entries')
            ->addColumn('schedule_id', 'integer', ['null' => false])
            ->addColumn('child_id', 'integer', ['null' => false])
            ->addColumn('priority', 'integer', ['default' => 0])
            ->addColumn('remaining', 'integer', ['default' => 1])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['schedule_id', 'child_id'], ['unique' => true])
            ->addForeignKey('schedule_id', 'schedules', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->addForeignKey('child_id', 'children', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->create();

        // Rules table
        $this->table('rules')
            ->addColumn('schedule_id', 'integer', ['null' => false])
            ->addColumn('key', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('value', 'text', ['null' => false])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['schedule_id', 'key'], ['unique' => true])
            ->addForeignKey('schedule_id', 'schedules', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->create();

        // Add foreign key for start_child_id in schedule_days (deferred as it references children)
        $this->table('schedule_days')
            ->addForeignKey('start_child_id', 'children', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE'
            ])
            ->update();
    }
}
