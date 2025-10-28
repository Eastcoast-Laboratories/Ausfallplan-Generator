<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Add email verification and user approval fields
 * 
 * FIXED VERSION - Uses Table API instead of raw SQL
 */
class AddUserVerificationFields extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('users');
        
        $table->addColumn('email_verified', 'integer', [
            'default' => 0,
            'null' => false,
        ])
        ->addColumn('email_token', 'string', [
            'limit' => 255,
            'null' => true,
            'default' => null,
        ])
        ->addColumn('status', 'string', [
            'limit' => 50,
            'default' => 'pending',
            'null' => false,
        ])
        ->addColumn('approved_at', 'datetime', [
            'null' => true,
            'default' => null,
        ])
        ->addColumn('approved_by', 'integer', [
            'null' => true,
            'default' => null,
        ])
        ->addIndex(['approved_by'])
        ->update();
    }
}
