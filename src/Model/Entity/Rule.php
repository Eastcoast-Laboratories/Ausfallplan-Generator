<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Rule Entity
 *
 * @property int $id
 * @property int $schedule_id
 * @property string $key
 * @property string $value
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Schedule $schedule
 */
class Rule extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'schedule_id' => true,
        'key' => true,
        'value' => true,
        'created' => true,
        'modified' => true,
        'schedule' => true,
    ];
}
