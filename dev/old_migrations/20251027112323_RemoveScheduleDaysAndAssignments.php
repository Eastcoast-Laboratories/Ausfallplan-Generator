<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RemoveScheduleDaysAndAssignments extends BaseMigration
{
    /**
     * Remove schedule_days and assignments tables
     * 
     * These tables were created based on a misunderstanding:
     * - schedule_days stored fixed dates, but Ausfallplan is timeless
     * - assignments linked children to specific days, but we only need sort order
     * - waitlist_entries.priority already handles child ordering
     * 
     * The report generation creates days dynamically with animal names,
     * not fixed dates from the database.
     *
     * @return void
     */
    public function change(): void
    {
        // Drop assignments first (has FK to schedule_days)
        $this->table('assignments')->drop()->save();
        
        // Drop schedule_days (has FK to schedules)
        $this->table('schedule_days')->drop()->save();
    }
}
