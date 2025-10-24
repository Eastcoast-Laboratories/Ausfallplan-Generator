<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Phase 3: Remove old user fields
 * 
 * Removes users.role and users.organization_id as these are now
 * handled by the organization_users join table
 */
class RemoveOldUserFields extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     */
    public function change(): void
    {
        $table = $this->table('users');
        
        // Remove old role column (now in organization_users)
        $table->removeColumn('role');
        
        // Remove old organization_id column (now in organization_users)
        $table->removeColumn('organization_id');
        
        $table->update();
    }
}
