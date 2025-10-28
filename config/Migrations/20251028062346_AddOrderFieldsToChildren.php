<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Add order and schedule tracking fields to children table
 * 
 * New fields:
 * - schedule_id: Current schedule the child is assigned to (nullable)
 * - organization_order: Sort order within organization (nullable, for general org-wide sorting)
 * - waitlist_order: Sort order in waitlist (nullable, used when child is on a waitlist)
 */
class AddOrderFieldsToChildren extends BaseMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('children');
        
        // Add schedule_id - links child to current schedule (nullable - not all children are assigned)
        $table->addColumn('schedule_id', 'integer', [
            'default' => null,
            'null' => true,
            'after' => 'organization_id'
        ]);
        
        // Add organization_order - general sort order within organization
        $table->addColumn('organization_order', 'integer', [
            'default' => null,
            'null' => true,
            'comment' => 'Sort order within organization',
            'after' => 'schedule_id'
        ]);
        
        // Add waitlist_order - sort order when child is on waitlist
        $table->addColumn('waitlist_order', 'integer', [
            'default' => null,
            'null' => true,
            'comment' => 'Sort order in waitlist',
            'after' => 'organization_order'
        ]);
        
        // Add foreign key for schedule_id
        $table->addForeignKey(
            'schedule_id',
            'schedules',
            'id',
            [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE'
            ]
        );
        
        // Add index for faster lookups
        $table->addIndex(['schedule_id']);
        $table->addIndex(['organization_id', 'organization_order']);
        $table->addIndex(['organization_id', 'waitlist_order']);
        
        $table->update();
    }
}
