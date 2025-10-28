<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddDisplayNameToChildren extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('children');
        $table->addColumn('display_name', 'string', [
            'default' => null,
            'limit' => 100,
            'null' => true,
            'after' => 'last_name',
            'comment' => 'Pre-formatted name for display in reports based on anonymization choice'
        ]);
        $table->update();
    }
}
