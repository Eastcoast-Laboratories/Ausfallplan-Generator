<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * WaitlistEntry Entity
 *
 * @property int $id
 * @property int $schedule_id
 * @property int $child_id
 * @property int $priority
 * @property int $remaining
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Schedule $schedule
 * @property \App\Model\Entity\Child $child
 */
class WaitlistEntry extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'schedule_id' => true,
        'child_id' => true,
        'priority' => true,
        'remaining' => true,
        'created' => true,
        'modified' => true,
        'schedule' => true,
        'child' => true,
    ];
}
