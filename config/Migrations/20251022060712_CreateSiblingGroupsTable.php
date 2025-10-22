<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateSiblingGroupsTable extends BaseMigration
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
        $table = $this->table('sibling_groups');
        $table->addColumn('organization_id', 'integer', [
            'default' => null,
            'limit' => 11,
            'null' => false,
        ])
        ->addColumn('name', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
            'comment' => 'E.g. "Schmidt Family"',
        ])
        ->addColumn('created', 'datetime', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('modified', 'datetime', [
            'default' => null,
            'null' => false,
        ])
        ->addIndex(['organization_id'])
        ->addForeignKey('organization_id', 'organizations', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
        ->create();
    }
}
