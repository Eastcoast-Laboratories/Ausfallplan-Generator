<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddIsActiveToUsers extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('users');
        $table->addColumn('is_active', 'boolean', [
            'default' => null,
            'null' => true,
            'after' => 'is_system_admin',
        ]);
        $table->update();

        $this->execute("UPDATE users SET is_active = CASE WHEN status = 'active' THEN 1 ELSE 0 END");

        $table->changeColumn('is_active', 'boolean', [
            'default' => true,
            'null' => false,
            'after' => 'is_system_admin',
        ]);
        $table->update();
    }
}
