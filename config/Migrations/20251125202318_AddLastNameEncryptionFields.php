<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddLastNameEncryptionFields extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        // Add encrypted last_name fields to children table
        // Using raw SQL for MySQL compatibility (like AddEncryptionSupport migration)
        $this->execute("ALTER TABLE children ADD COLUMN last_name_encrypted TEXT NULL COMMENT 'Encrypted last_name (AES-GCM ciphertext)'");
        $this->execute("ALTER TABLE children ADD COLUMN last_name_iv VARCHAR(255) NULL COMMENT 'Initialization vector for last_name encryption'");
        $this->execute("ALTER TABLE children ADD COLUMN last_name_tag VARCHAR(255) NULL COMMENT 'Authentication tag for last_name encryption'");
    }
}
