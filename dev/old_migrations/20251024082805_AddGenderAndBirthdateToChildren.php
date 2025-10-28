<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddGenderAndBirthdateToChildren extends BaseMigration
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
        $table->addColumn('gender', 'string', [
            'default' => null,
            'limit' => 10,
            'null' => true,
        ]);
        $table->addColumn('birthdate', 'date', [
            'default' => null,
            'null' => true,
        ]);
        $table->update();
    }
}
