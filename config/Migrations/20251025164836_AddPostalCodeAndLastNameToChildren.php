<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddPostalCodeAndLastNameToChildren extends BaseMigration
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
        $table = $this->table('children');
        $table->addColumn('postal_code', 'string', [
            'default' => null,
            'limit' => 10,
            'null' => true,
        ]);
        $table->addColumn('last_name', 'string', [
            'default' => null,
            'limit' => 100,
            'null' => true,
        ]);
        $table->update();
    }
}
