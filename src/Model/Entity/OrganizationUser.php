<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * OrganizationUser Entity
 *
 * @property int $id
 * @property int $organization_id
 * @property int $user_id
 * @property string $role
 * @property bool $is_primary
 * @property \Cake\I18n\DateTime $joined_at
 * @property int|null $invited_by
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Organization $organization
 * @property \App\Model\Entity\User $user
 * @property \App\Model\Entity\User $inviter
 */
class OrganizationUser extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'organization_id' => true,
        'user_id' => true,
        'role' => true,
        'is_primary' => true,
        'joined_at' => true,
        'invited_by' => true,
        'created' => true,
        'modified' => true,
        'organization' => true,
        'user' => true,
        'inviter' => true,
    ];

    /**
     * Role constants
     */
    public const ROLE_ORG_ADMIN = 'org_admin';
    public const ROLE_EDITOR = 'editor';
    public const ROLE_VIEWER = 'viewer';

    /**
     * Get all available roles
     *
     * @return array
     */
    public static function getRoles(): array
    {
        return [
            self::ROLE_ORG_ADMIN => __('Organization Admin'),
            self::ROLE_EDITOR => __('Editor'),
            self::ROLE_VIEWER => __('Viewer'),
        ];
    }

    /**
     * Get role hierarchy value for comparison
     *
     * @param string $role Role name
     * @return int Hierarchy value (higher = more permissions)
     */
    public static function getRoleHierarchy(string $role): int
    {
        return match ($role) {
            self::ROLE_ORG_ADMIN => 3,
            self::ROLE_EDITOR => 2,
            self::ROLE_VIEWER => 1,
            default => 0,
        };
    }
}
