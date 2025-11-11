<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Add client-side encryption support for multi-user organizations
 * 
 * This migration adds:
 * - encryption_enabled flag to organizations
 * - user encryption keys (public/private key pairs)
 * - encrypted_deks table for wrapped data encryption keys
 * - encrypted name fields to children table
 */
class AddEncryptionSupport extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        // Add encryption_enabled to organizations table
        $organizations = $this->table('organizations');
        $organizations
            ->addColumn('encryption_enabled', 'boolean', [
                'default' => true,
                'null' => false,
                'after' => 'is_active',
                'comment' => 'Enable client-side encryption for sensitive data',
            ])
            ->update();

        // Add encryption keys to users table
        $users = $this->table('users');
        $users
            ->addColumn('public_key', 'text', [
                'null' => true,
                'after' => 'password',
                'comment' => 'RSA/EC public key for encrypting DEKs',
            ])
            ->addColumn('encrypted_private_key', 'text', [
                'null' => true,
                'after' => 'public_key',
                'comment' => 'Private key encrypted with password-derived KEK',
            ])
            ->addColumn('key_salt', 'string', [
                'limit' => 255,
                'null' => true,
                'after' => 'encrypted_private_key',
                'comment' => 'Salt for password-based key derivation',
            ])
            ->update();

        // Create encrypted_deks table for wrapped data encryption keys
        $encryptedDeks = $this->table('encrypted_deks');
        $encryptedDeks
            ->addColumn('organization_id', 'integer', [
                'null' => false,
                'comment' => 'Organization this DEK belongs to',
            ])
            ->addColumn('user_id', 'integer', [
                'null' => false,
                'comment' => 'User who can decrypt this wrapped DEK',
            ])
            ->addColumn('wrapped_dek', 'text', [
                'null' => false,
                'comment' => 'DEK encrypted with user public key',
            ])
            ->addColumn('created', 'datetime', [
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true,
            ])
            ->addIndex(['organization_id'])
            ->addIndex(['user_id'])
            ->addIndex(['organization_id', 'user_id'], ['unique' => true])
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->create();

        // Add encrypted name fields to children table
        $children = $this->table('children');
        $children
            ->addColumn('name_encrypted', 'text', [
                'null' => true,
                'after' => 'name',
                'comment' => 'Encrypted name (AES-GCM ciphertext)',
            ])
            ->addColumn('name_iv', 'string', [
                'limit' => 255,
                'null' => true,
                'after' => 'name_encrypted',
                'comment' => 'Initialization vector for name encryption',
            ])
            ->addColumn('name_tag', 'string', [
                'limit' => 255,
                'null' => true,
                'after' => 'name_iv',
                'comment' => 'Authentication tag for name encryption',
            ])
            ->update();
    }
}
