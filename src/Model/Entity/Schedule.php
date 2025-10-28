<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Schedule Entity
 *
 * @property int $id
 * @property int $organization_id
 * @property string $title
 * @property \Cake\I18n\Date $starts_on
 * @property \Cake\I18n\Date $ends_on
 * @property string $state
 * @property int|null $capacity_per_day
 * @property int|null $days_count
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Organization $organization
 * @property \App\Model\Entity\Rule[] $rules
 */
class Schedule extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'organization_id' => true,
        'title' => true,
        'starts_on' => true,
        'ends_on' => true,
        'state' => true,
        'capacity_per_day' => true,
        'days_count' => true,
        'created' => true,
        'modified' => true,
        'organization' => true,
        'rules' => true,
    ];
}
