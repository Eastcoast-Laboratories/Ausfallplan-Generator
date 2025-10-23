<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * PasswordReset Entity
 *
 * @property int $id
 * @property int $user_id
 * @property string $reset_token
 * @property string $reset_code
 * @property \Cake\I18n\DateTime $expires_at
 * @property \Cake\I18n\DateTime|null $used_at
 * @property \Cake\I18n\DateTime $created
 *
 * @property \App\Model\Entity\User $user
 */
class PasswordReset extends Entity
{
    protected array $_accessible = [
        'user_id' => true,
        'reset_token' => true,
        'reset_code' => true,
        'expires_at' => true,
        'used_at' => true,
        'created' => true,
        'user' => true,
    ];

    protected array $_hidden = [
        'reset_token',
    ];
}
