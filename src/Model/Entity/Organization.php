<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Organization Entity
 *
 * @property int $id
 * @property string $name
 * @property string $locale
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\User[] $users
 * @property \App\Model\Entity\Child[] $children
 * @property \App\Model\Entity\Schedule[] $schedules
 * @property \App\Model\Entity\SiblingGroup[] $sibling_groups
 */
class Organization extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'name' => true,
        'locale' => true,
        'is_active' => true,
        'contact_email' => true,
        'contact_phone' => true,
        'created' => true,
        'modified' => true,
        'users' => true,
        'children' => true,
        'schedules' => true,
        'sibling_groups' => true,
    ];
}
