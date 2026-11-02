<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241205000009 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize participantIds JSON field to proper many-to-many relationship';
    }

    public function up(Schema $schema): void
    {
        // Create the new conversation_participants table
        $this->addSql('
            CREATE TABLE communication_conversation_participants (
                id VARCHAR(36) NOT NULL,
                conversation_id VARCHAR(36) NOT NULL,
                user_id VARCHAR(36) NOT NULL,
                joined_at DATETIME NOT NULL,
                role VARCHAR(20) DEFAULT \'member\' NOT NULL,
                is_active TINYINT(1) DEFAULT 1 NOT NULL,
                INDEX IDX_CONVERSATION (conversation_id),
                INDEX IDX_USER (user_id),
                UNIQUE KEY unique_conversation_user (conversation_id, user_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');

        // Migrate data from JSON field to new table
        $this->addSql('
            INSERT INTO communication_conversation_participants (id, conversation_id, user_id, joined_at, role, is_active)
            SELECT
                CONCAT(\'cp_\', MD5(RAND())) as id,
                c.id as conversation_id,
                JSON_UNQUOTE(JSON_EXTRACT(pt.value, \'$\')) as user_id,
                c.created_at as joined_at,
                \'member\' as role,
                1 as is_active
            FROM communication_conversations c
            CROSS JOIN JSON_TABLE(
                c.participant_ids,
                \'$[*]\' COLUMNS (value JSON PATH \'$\')
            ) pt
            WHERE JSON_VALID(c.participant_ids) = 1
        ');

        // Add foreign key constraints (optional - depends on your FK strategy)
        // $this->addSql('ALTER TABLE communication_conversation_participants ADD CONSTRAINT FK_CONVERSATION FOREIGN KEY (conversation_id) REFERENCES communication_conversations(id)');
        // $this->addSql('ALTER TABLE communication_conversation_participants ADD CONSTRAINT FK_USER FOREIGN KEY (user_id) REFERENCES communication_users(id)');

        // Update Conversation entity to use relationships instead of JSON
        $this->addSql('ALTER TABLE communication_conversations DROP COLUMN participant_ids');
    }

    public function down(Schema $schema): void
    {
        // Recreate participant_ids JSON column
        $this->addSql('ALTER TABLE communication_conversations ADD participant_ids JSON DEFAULT NULL');

        // Migrate data back from participants table to JSON
        $this->addSql('
            UPDATE communication_conversations c
            SET participant_ids = (
                SELECT CONCAT(\'[\', GROUP_CONCAT(
                    JSON_QUOTE(cp.user_id) ORDER BY cp.joined_at
                ), \']\')
                FROM communication_conversation_participants cp
                WHERE cp.conversation_id = c.id AND cp.is_active = 1
            )
        ');

        // Drop the participants table
        $this->addSql('DROP TABLE communication_conversation_participants');
    }
}
