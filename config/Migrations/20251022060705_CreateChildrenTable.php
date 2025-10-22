<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateChildrenTable extends BaseMigration
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
        $table->addColumn('organization_id', 'integer', [
            'default' => null,
            'limit' => 11,
            'null' => false,
        ])
        ->addColumn('sibling_group_id', 'integer', [
            'default' => null,
            'limit' => 11,
            'null' => true,
        ])
        ->addColumn('name', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ])
        ->addColumn('is_integrative', 'boolean', [
            'default' => false,
            'null' => false,
        ])
        ->addColumn('priority', 'integer', [
            'default' => 0,
            'limit' => 11,
            'null' => false,
            'comment' => 'Higher number = higher priority for waitlist',
        ])
        ->addColumn('notes', 'text', [
            'default' => null,
            'null' => true,
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
        ->addIndex(['sibling_group_id'])
        ->addForeignKey('organization_id', 'organizations', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
        ->addForeignKey('sibling_group_id', 'sibling_groups', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
        ->create();
    }
}
