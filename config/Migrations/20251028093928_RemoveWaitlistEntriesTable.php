<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RemoveWaitlistEntriesTable extends BaseMigration
{
    /**
     * Remove waitlist_entries table
     * 
     * Waitlist functionality is now handled directly in children table:
     * - children.schedule_id (which schedule the child is assigned to)
     * - children.waitlist_order (position in waitlist)
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('waitlist_entries')->drop()->save();
    }
}
