<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddDaysCountToSchedules extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up(): void
    {
        $this->execute('ALTER TABLE schedules ADD COLUMN days_count INTEGER NULL DEFAULT NULL');
    }

    /**
     * Migrate Down.
     */
    public function down(): void
    {
        $this->execute('ALTER TABLE schedules DROP COLUMN days_count');
    }
}
