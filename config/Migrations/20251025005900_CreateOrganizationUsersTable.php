<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateOrganizationUsersTable migration
 * 
 * Creates join table for many-to-many relationship between users and organizations
 * with role per organization.
 */
class CreateOrganizationUsersTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        // Create organization_users join table
        $table = $this->table('organization_users');
        $table
            ->addColumn('organization_id', 'integer', [
                'null' => false,
            ])
            ->addColumn('user_id', 'integer', [
                'null' => false,
            ])
            ->addColumn('role', 'string', [
                'limit' => 50,
                'null' => false,
                'default' => 'viewer',
            ])
            ->addColumn('is_primary', 'boolean', [
                'null' => false,
                'default' => false,
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
            ->addIndex(['organization_id', 'user_id'], ['unique' => true])
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
            ])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
            ])
            ->addForeignKey('invited_by', 'users', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION',
            ])
            ->create();

        // Migrate existing data from users table to organization_users
        $this->execute("
            INSERT INTO organization_users (organization_id, user_id, role, is_primary, joined_at, created, modified)
            SELECT 
                organization_id,
                id as user_id,
                CASE 
                    WHEN role = 'admin' THEN 'org_admin'
                    WHEN role = 'editor' THEN 'editor'
                    ELSE 'viewer'
                END as role,
                TRUE as is_primary,
                created as joined_at,
                created,
                modified
            FROM users
            WHERE organization_id IS NOT NULL
        ");

        // Add is_system_admin column to users table
        $usersTable = $this->table('users');
        $usersTable
            ->addColumn('is_system_admin', 'boolean', [
                'null' => false,
                'default' => false,
                'after' => 'password',
            ])
            ->update();

        // Mark current admins as system admins
        $this->execute("
            UPDATE users 
            SET is_system_admin = TRUE 
            WHERE role = 'admin'
        ");

        // Remove role and organization_id from users table
        // Note: We keep these for now and remove in a separate migration for safety
        // This allows rollback if needed
    }

    /**
     * Migrate Down.
     */
    public function down(): void
    {
        // Remove is_system_admin from users
        $this->table('users')
            ->removeColumn('is_system_admin')
            ->update();

        // Drop organization_users table
        $this->table('organization_users')->drop()->save();
    }
}
