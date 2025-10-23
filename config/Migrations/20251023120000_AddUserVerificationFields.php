<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Add email verification and user approval fields
 */
class AddUserVerificationFields extends BaseMigration
{
    public function up(): void
    {
        $this->execute("
            ALTER TABLE users ADD COLUMN email_verified INTEGER DEFAULT 0;
        ");
        
        $this->execute("
            ALTER TABLE users ADD COLUMN email_token VARCHAR(255) NULL;
        ");
        
        $this->execute("
            ALTER TABLE users ADD COLUMN status VARCHAR(50) DEFAULT 'pending';
        ");
        
        $this->execute("
            ALTER TABLE users ADD COLUMN approved_at DATETIME NULL;
        ");
        
        $this->execute("
            ALTER TABLE users ADD COLUMN approved_by INTEGER NULL;
        ");
        
        // Add foreign key for approved_by
        $this->execute("
            CREATE INDEX idx_users_approved_by ON users(approved_by);
        ");
    }

    public function down(): void
    {
        // SQLite doesn't support DROP COLUMN easily, so we skip rollback
        // In production with MySQL, this would drop the columns
    }
}
