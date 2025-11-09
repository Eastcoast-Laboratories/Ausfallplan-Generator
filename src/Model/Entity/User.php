<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\ORM\Entity;

/**
 * User Entity
 *
 * @property int $id
 * @property int|null $organization_id
 * @property string $email
 * @property string $password
 * @property string|null $public_key
 * @property string|null $encrypted_private_key
 * @property string|null $key_salt
 * @property string|null $role
 * @property bool $is_system_admin
 * @property bool $email_verified
 * @property string|null $email_token
 * @property string $status
 * @property \Cake\I18n\DateTime|null $approved_at
 * @property int|null $approved_by
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Organization $organization
 * @property \App\Model\Entity\Organization[] $organizations
 * @property \App\Model\Entity\OrganizationUser[] $organization_users
 * @property \App\Model\Entity\EncryptedDek[] $encrypted_deks
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
        'public_key' => true,
        'encrypted_private_key' => true,
        'key_salt' => true,
        'role' => true,
        'is_system_admin' => true,
        'email_verified' => true,
        'email_token' => true,
        'status' => true,
        'approved_at' => true,
        'approved_by' => true,
        'created' => true,
        'modified' => true,
        'organization' => true,
        'organizations' => true,
        'organization_users' => true,
        'encrypted_deks' => true,
    ];

    /**
     * Fields that are excluded from JSON versions of the entity.
     *
     * @var list<string>
     */
    protected array $_hidden = [
        'password',
        'email_token',
        'encrypted_private_key',
        'key_salt',
    ];

    /**
     * Virtual field for checking if user is system admin
     *
     * @return bool
     */
    protected function _getIsSystemAdmin(): bool
    {
        return (bool)($this->_fields['is_system_admin'] ?? false);
    }

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

    /**
     * Check if user has a specific role in an organization
     *
     * @param int $organizationId Organization ID
     * @param string|null $requiredRole Required role (null = just check membership)
     * @return bool
     */
    public function hasOrgRole(int $organizationId, ?string $requiredRole = null): bool
    {
        // System admins have access to everything
        if ($this->is_system_admin) {
            return true;
        }

        // Check if user is member of the organization
        if (!isset($this->organization_users) || empty($this->organization_users)) {
            return false;
        }

        $orgUser = collection($this->organization_users)
            ->firstMatch(['organization_id' => $organizationId]);

        if (!$orgUser) {
            return false;
        }

        // If no specific role required, just membership is enough
        if ($requiredRole === null) {
            return true;
        }

        // Check role hierarchy
        return OrganizationUser::getRoleHierarchy($orgUser->role) >= OrganizationUser::getRoleHierarchy($requiredRole);
    }

    /**
     * Get user's primary organization
     *
     * @return \App\Model\Entity\OrganizationUser|null
     */
    public function getPrimaryOrganization(): ?OrganizationUser
    {
        if (!isset($this->organization_users) || empty($this->organization_users)) {
            return null;
        }

        return collection($this->organization_users)
            ->firstMatch(['is_primary' => true]);
    }

    /**
     * Check if user is system admin
     *
     * @return bool
     */
    public function isSystemAdmin(): bool
    {
        return (bool)$this->is_system_admin;
    }
}
