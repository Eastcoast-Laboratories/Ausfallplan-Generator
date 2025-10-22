<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class MakeEndsOnNullable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Make ends_on column nullable so schedules can run indefinitely
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('schedules');
        $table->changeColumn('ends_on', 'date', [
            'default' => null,
            'null' => true,
        ]);
        $table->update();
    }
}
