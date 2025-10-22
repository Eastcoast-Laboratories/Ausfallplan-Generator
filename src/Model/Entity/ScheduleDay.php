<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ScheduleDay Entity
 *
 * @property int $id
 * @property int $schedule_id
 * @property string $title
 * @property int $position
 * @property int $capacity
 * @property int|null $start_child_id
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Schedule $schedule
 * @property \App\Model\Entity\Child|null $start_child
 * @property \App\Model\Entity\Assignment[] $assignments
 */
class ScheduleDay extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'schedule_id' => true,
        'title' => true,
        'position' => true,
        'capacity' => true,
        'start_child_id' => true,
        'created' => true,
        'modified' => true,
        'schedule' => true,
        'start_child' => true,
        'assignments' => true,
    ];
}
