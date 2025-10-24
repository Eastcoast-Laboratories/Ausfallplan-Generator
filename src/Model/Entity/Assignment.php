<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Assignment Entity
 *
 * @property int $id
 * @property int $schedule_day_id
 * @property int $child_id
 * @property int $weight
 * @property string $source
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\ScheduleDay $schedule_day
 * @property \App\Model\Entity\Child $child
 */
class Assignment extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'schedule_day_id' => true,
        'child_id' => true,
        'weight' => true,
        'source' => true,
        'sort_order' => true,
        'created' => true,
        'modified' => true,
        'schedule_day' => true,
        'child' => true,
    ];
}
