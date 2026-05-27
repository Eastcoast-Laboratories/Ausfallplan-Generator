<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class SetUsersIsActiveDefaultFalse extends BaseMigration
{
    public function change(): void
    {
        $this->table('users')
            ->changeColumn('is_active', 'boolean', [
                'default' => false,
                'null' => false,
                'after' => 'is_system_admin',
            ])
            ->update();
    }
}
