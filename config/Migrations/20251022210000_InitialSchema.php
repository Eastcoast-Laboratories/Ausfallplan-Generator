<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Initial Schema Migration
 * 
 * This migration creates the complete database schema from scratch.
 * All previous incremental migrations have been consolidated into this single migration.
 */
class InitialSchema extends BaseMigration
{
    /**
     * Create all tables
     */
    public function up(): void
    {
        // Organizations table
        $this->execute("
            CREATE TABLE IF NOT EXISTS organizations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                created DATETIME,
                modified DATETIME
            )
        ");

        // Users table
        $this->execute("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                organization_id INTEGER NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(50) DEFAULT 'viewer',
                created DATETIME,
                modified DATETIME,
                FOREIGN KEY (organization_id) REFERENCES organizations(id)
            )
        ");

        // Sibling Groups table
        $this->execute("
            CREATE TABLE IF NOT EXISTS sibling_groups (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                organization_id INTEGER NOT NULL,
                label VARCHAR(255),
                created DATETIME,
                modified DATETIME,
                FOREIGN KEY (organization_id) REFERENCES organizations(id)
            )
        ");

        // Children table
        $this->execute("
            CREATE TABLE IF NOT EXISTS children (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                organization_id INTEGER NOT NULL,
                name VARCHAR(255) NOT NULL,
                is_active BOOLEAN DEFAULT 1,
                is_integrative BOOLEAN DEFAULT 0,
                sibling_group_id INTEGER,
                created DATETIME,
                modified DATETIME,
                FOREIGN KEY (organization_id) REFERENCES organizations(id),
                FOREIGN KEY (sibling_group_id) REFERENCES sibling_groups(id)
            )
        ");

        // Schedules table
        $this->execute("
            CREATE TABLE IF NOT EXISTS schedules (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                organization_id INTEGER NOT NULL,
                title VARCHAR(255) NOT NULL,
                starts_on DATE,
                ends_on DATE,
                state VARCHAR(50) DEFAULT 'draft',
                capacity_per_day INTEGER DEFAULT 9,
                days_count INTEGER,
                created DATETIME,
                modified DATETIME,
                FOREIGN KEY (organization_id) REFERENCES organizations(id)
            )
        ");

        // Schedule Days table
        $this->execute("
            CREATE TABLE IF NOT EXISTS schedule_days (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                schedule_id INTEGER NOT NULL,
                title VARCHAR(255),
                position INTEGER,
                capacity INTEGER DEFAULT 9,
                created DATETIME,
                modified DATETIME,
                FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE
            )
        ");

        // Assignments table
        $this->execute("
            CREATE TABLE IF NOT EXISTS assignments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                schedule_day_id INTEGER NOT NULL,
                child_id INTEGER NOT NULL,
                weight INTEGER DEFAULT 1,
                source VARCHAR(50) DEFAULT 'manual',
                created DATETIME,
                modified DATETIME,
                FOREIGN KEY (schedule_day_id) REFERENCES schedule_days(id) ON DELETE CASCADE,
                FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE
            )
        ");

        // Waitlist Entries table
        $this->execute("
            CREATE TABLE IF NOT EXISTS waitlist_entries (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                schedule_id INTEGER NOT NULL,
                child_id INTEGER NOT NULL,
                priority INTEGER,
                created DATETIME,
                modified DATETIME,
                FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE,
                FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE,
                UNIQUE(schedule_id, child_id)
            )
        ");

        // Rules table
        $this->execute("
            CREATE TABLE IF NOT EXISTS rules (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                schedule_id INTEGER NOT NULL,
                type VARCHAR(50),
                parameters TEXT,
                created DATETIME,
                modified DATETIME,
                FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE
            )
        ");

        // Phinxlog table (for migrations tracking)
        $this->execute("
            CREATE TABLE IF NOT EXISTS phinxlog (
                version BIGINT NOT NULL,
                migration_name VARCHAR(100),
                start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                end_time TIMESTAMP,
                breakpoint BOOLEAN DEFAULT 0,
                PRIMARY KEY (version)
            )
        ");
    }

    /**
     * Drop all tables
     */
    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS rules");
        $this->execute("DROP TABLE IF EXISTS waitlist_entries");
        $this->execute("DROP TABLE IF EXISTS assignments");
        $this->execute("DROP TABLE IF EXISTS schedule_days");
        $this->execute("DROP TABLE IF EXISTS schedules");
        $this->execute("DROP TABLE IF EXISTS children");
        $this->execute("DROP TABLE IF EXISTS sibling_groups");
        $this->execute("DROP TABLE IF EXISTS users");
        $this->execute("DROP TABLE IF EXISTS organizations");
        $this->execute("DROP TABLE IF EXISTS phinxlog");
    }
}
