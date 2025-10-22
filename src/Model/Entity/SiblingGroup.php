<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * SiblingGroup Entity
 *
 * @property int $id
 * @property int $organization_id
 * @property string|null $label
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Organization $organization
 * @property \App\Model\Entity\Child[] $children
 */
class SiblingGroup extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'organization_id' => true,
        'label' => true,
        'created' => true,
        'modified' => true,
        'organization' => true,
        'children' => true,
    ];
}
