<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Child Entity
 *
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string|null $last_name
 * @property string|null $display_name
 * @property string|null $gender
 * @property \Cake\I18n\DateTime|null $birthdate
 * @property string|null $postal_code
 * @property bool $is_integrative
 * @property bool $is_active
 * @property int|null $sibling_group_id
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Organization $organization
 * @property \App\Model\Entity\SiblingGroup|null $sibling_group
 * @property \App\Model\Entity\Assignment[] $assignments
 * @property \App\Model\Entity\WaitlistEntry[] $waitlist_entries
 */
class Child extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'organization_id' => true,
        'name' => true,
        'last_name' => true,
        'display_name' => true,
        'gender' => true,
        'birthdate' => true,
        'postal_code' => true,
        'is_integrative' => true,
        'is_active' => true,
        'sibling_group_id' => true,
        'created' => true,
        'modified' => true,
        'organization' => true,
        'sibling_group' => true,
        'assignments' => true,
        'waitlist_entries' => true,
    ];
    
    /**
     * Get full name with fallback to display_name or name + last_name
     *
     * @return string
     */
    protected function _getFullName(): string
    {
        if (!empty($this->display_name)) {
            return $this->display_name;
        }
        
        return $this->name . ($this->last_name ? ' ' . $this->last_name : '');
    }
}
