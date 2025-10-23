<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Create password_resets table for password recovery
 */
class CreatePasswordResetsTable extends BaseMigration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS password_resets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                reset_token VARCHAR(255) NOT NULL,
                reset_code VARCHAR(10) NOT NULL,
                expires_at DATETIME NOT NULL,
                used_at DATETIME NULL,
                created DATETIME,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        
        $this->execute("
            CREATE INDEX idx_password_resets_token ON password_resets(reset_token);
        ");
        
        $this->execute("
            CREATE INDEX idx_password_resets_code ON password_resets(reset_code);
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS password_resets");
    }
}
