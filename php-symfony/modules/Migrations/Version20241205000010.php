<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241205000010 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add indexes for JSON fields and improve query performance';
    }

    public function up(Schema $schema): void
    {
        // For SQLite, we need to use expressions for JSON indexing
        // Add index for conversation participants JSON queries
        $this->addSql('CREATE INDEX idx_conversation_participants ON communication_conversations (participant_ids)');

        // Add index for message reactions JSON field
        $this->addSql('CREATE INDEX idx_message_reactions ON communication_messages (reactions)');

        // Add index for message attachments JSON field
        $this->addSql('CREATE INDEX idx_message_attachments ON communication_messages (attachments)');

        // Add composite index for conversation queries
        $this->addSql('CREATE INDEX idx_conversation_user_active ON communication_conversations (is_active, is_archived)');

        // Add index for message conversation and sender
        $this->addSql('CREATE INDEX idx_message_conversation_sender ON communication_messages (conversation_id, sender_id)');

        // Add index for read receipts
        $this->addSql('CREATE INDEX idx_read_receipt_user_message ON communication_message_read_receipts (user_id, message_id)');
    }

    public function down(Schema $schema): void
    {
        // Drop indexes in reverse order
        $this->addSql('DROP INDEX idx_read_receipt_user_message');
        $this->addSql('DROP INDEX idx_message_conversation_sender');
        $this->addSql('DROP INDEX idx_conversation_user_active');
        $this->addSql('DROP INDEX idx_message_attachments');
        $this->addSql('DROP INDEX idx_message_reactions');
        $this->addSql('DROP INDEX idx_conversation_participants');
    }
}
