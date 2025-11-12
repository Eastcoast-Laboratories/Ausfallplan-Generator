<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * EncryptedDeksFixture
 */
class EncryptedDeksFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'organization_id' => 1,
                'user_id' => 1,
                'wrapped_dek' => 'base64_encoded_wrapped_dek_for_user_1',
                'created' => '2024-01-01 10:00:00',
                'modified' => '2024-01-01 10:00:00',
            ],
        ];
        parent::init();
    }
}
