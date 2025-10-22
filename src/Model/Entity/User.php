<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\ORM\Entity;

/**
 * User Entity
 *
 * @property int $id
 * @property int $organization_id
 * @property string $email
 * @property string $password
 * @property string $role
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Organization $organization
 */
class User extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'organization_id' => true,
        'email' => true,
        'password' => true,
        'role' => true,
        'created' => true,
        'modified' => true,
        'organization' => true,
    ];

    /**
     * Fields that are excluded from JSON versions of the entity.
     *
     * @var list<string>
     */
    protected array $_hidden = [
        'password',
    ];

    /**
     * Hash password before saving
     *
     * @param string $value Password to hash
     * @return string|null
     */
    protected function _setPassword(string $value): ?string
    {
        if (strlen($value) > 0) {
            $hasher = new DefaultPasswordHasher();
            return $hasher->hash($value);
        }
        return null;
    }
}
