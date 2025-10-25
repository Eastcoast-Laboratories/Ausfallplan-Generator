<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * PasswordResetsFixture
 */
class PasswordResetsFixture extends TestFixture
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
                'user_id' => 1,
                'reset_token' => 'test-token-123456789abcdef',
                'reset_code' => '123456',
                'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
                'used_at' => null,
                'created' => '2024-10-22 10:00:00',
            ],
            [
                'id' => 2,
                'user_id' => 2,
                'reset_token' => 'expired-token-987654321fedcba',
                'reset_code' => '654321',
                'expires_at' => date('Y-m-d H:i:s', strtotime('-1 hour')), // Expired
                'used_at' => null,
                'created' => '2024-10-22 09:00:00',
            ],
            [
                'id' => 3,
                'user_id' => 3,
                'reset_token' => 'used-token-111222333444555',
                'reset_code' => '999888',
                'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
                'used_at' => '2024-10-22 11:00:00', // Already used
                'created' => '2024-10-22 10:30:00',
            ],
        ];
        parent::init();
    }
}
