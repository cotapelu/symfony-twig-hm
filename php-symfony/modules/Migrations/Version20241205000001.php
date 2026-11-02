<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241205000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create communication tables for conversations, messages, and user profiles';
    }

    public function up(Schema $schema): void
    {
        // Create conversations table
        $this->addSql('
            CREATE TABLE communication_conversations (
                id VARCHAR(36) NOT NULL,
                title VARCHAR(255) NOT NULL,
                type VARCHAR(20) NOT NULL,
                participant_ids TEXT NOT NULL, -- JSON stored as TEXT in SQLite
                creator_id VARCHAR(36) NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME DEFAULT NULL,
                is_active BOOLEAN DEFAULT 1 NOT NULL,
                is_archived BOOLEAN DEFAULT 0 NOT NULL,
                is_public BOOLEAN DEFAULT 0 NOT NULL,
                description TEXT DEFAULT NULL,
                avatar_url VARCHAR(500) DEFAULT NULL,
                PRIMARY KEY(id)
            )
        ');

        // Create messages table
        $this->addSql('
            CREATE TABLE communication_messages (
                id VARCHAR(36) NOT NULL,
                conversation_id VARCHAR(36) NOT NULL,
                sender_id VARCHAR(36) NOT NULL,
                content TEXT NOT NULL,
                type VARCHAR(20) NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME DEFAULT NULL,
                is_edited BOOLEAN DEFAULT 0 NOT NULL,
                reply_to_message_id VARCHAR(36) DEFAULT NULL,
                attachments TEXT DEFAULT NULL, -- JSON stored as TEXT
                reactions TEXT DEFAULT NULL, -- JSON stored as TEXT
                PRIMARY KEY(id),
                FOREIGN KEY (conversation_id) REFERENCES communication_conversations(id),
                FOREIGN KEY (sender_id) REFERENCES communication_users(id)
            )
        ');
        $this->addSql('CREATE INDEX IDX_CONV_ID ON communication_messages (conversation_id)');
        $this->addSql('CREATE INDEX IDX_SENDER_ID ON communication_messages (sender_id)');
        $this->addSql('CREATE INDEX IDX_CREATED_AT ON communication_messages (created_at)');

        // Create user profiles table
        $this->addSql('
            CREATE TABLE communication_user_profiles (
                user_id VARCHAR(36) NOT NULL,
                display_name VARCHAR(100) NOT NULL,
                avatar_url VARCHAR(500) DEFAULT NULL,
                status VARCHAR(20) NOT NULL,
                bio TEXT DEFAULT NULL,
                timezone VARCHAR(50) NOT NULL,
                last_seen DATETIME DEFAULT NULL,
                preferences TEXT NOT NULL, -- JSON stored as TEXT
                PRIMARY KEY(user_id)
            )
        ');

        // Note: SQLite doesn't enforce foreign key constraints by default
        // Foreign key constraints are kept for compatibility but won't be enforced
        // Uncomment the line below if you want to enable foreign keys in SQLite
        // $this->addSql('PRAGMA foreign_keys = ON;');
    }

    public function down(Schema $schema): void
    {
        // Drop tables (SQLite doesn't need to drop foreign keys separately)
        $this->addSql('DROP TABLE IF EXISTS communication_user_profiles');
        $this->addSql('DROP TABLE IF EXISTS communication_messages');
        $this->addSql('DROP TABLE IF EXISTS communication_conversations');
    }
}
