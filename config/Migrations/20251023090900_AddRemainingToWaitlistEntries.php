<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddRemainingToWaitlistEntries extends BaseMigration
{
    /**
     * Add 'remaining' column to waitlist_entries table
     * 
     * This column tracks how many more times a child should be placed
     * from the waitlist into the schedule.
     */
    public function change(): void
    {
        $table = $this->table('waitlist_entries');
        
        $table->addColumn('remaining', 'integer', [
            'default' => 1,
            'null' => false,
            'comment' => 'Number of remaining placements needed for this child',
            'after' => 'priority'
        ]);
        
        $table->update();
    }
}
