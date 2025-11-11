<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddKeyIvToUsers extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('users');
        $table->addColumn('key_iv', 'text', [
            'default' => null,
            'null' => true,
            'after' => 'key_salt',
            'comment' => 'IV (Initialization Vector) used for encrypting the private key with AES-GCM'
        ]);
        $table->update();
    }
}
