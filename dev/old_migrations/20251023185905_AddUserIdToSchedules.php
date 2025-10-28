<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddUserIdToSchedules extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('schedules');
        $table->addColumn('user_id', 'integer', [
            'null' => true,
            'default' => null,
        ])
        ->addIndex(['user_id'])
        ->update();
    }
}
