<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * EncryptedDek Entity
 *
 * Stores wrapped (encrypted) Data Encryption Keys for each user in an organization.
 * Each user's DEK is encrypted with their public key and can only be decrypted
 * with their private key (which is encrypted with their password).
 *
 * @property int $id
 * @property int $organization_id
 * @property int $user_id
 * @property string $wrapped_dek
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Organization $organization
 * @property \App\Model\Entity\User $user
 */
class EncryptedDek extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'organization_id' => true,
        'user_id' => true,
        'wrapped_dek' => true,
        'created' => true,
        'modified' => true,
        'organization' => true,
        'user' => true,
    ];
}
