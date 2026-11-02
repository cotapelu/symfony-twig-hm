<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250104000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add database indexes for improved query performance';
    }

    public function up(Schema $schema): void
    {
        // Index for communication_messages table
        $this->addSql('
            CREATE INDEX IF NOT EXISTS idx_message_conversation_created
            ON communication_messages(conversation_id, created_at DESC)
        ');

        $this->addSql('
            CREATE INDEX IF NOT EXISTS idx_message_sender_created
            ON communication_messages(sender_id, created_at DESC)
        ');

        $this->addSql('
            CREATE INDEX IF NOT EXISTS idx_message_thread_created
            ON communication_messages(thread_id, created_at DESC)
        ');

        // Index for communication_conversations table
        $this->addSql('
            CREATE INDEX IF NOT EXISTS idx_conversation_updated_at
            ON communication_conversations(updated_at DESC)
        ');

        // Index for communication_conversation_participants table
        $this->addSql('
            CREATE INDEX IF NOT EXISTS idx_participant_user_active
            ON communication_conversation_participants(user_id, is_active, conversation_id)
        ');

        // Index for communication_notifications table
        $this->addSql('
            CREATE INDEX IF NOT EXISTS idx_notification_user_read
            ON communication_notifications(user_id, read_at, created_at DESC)
        ');

        // Index for communication_calls table
        $this->addSql('
            CREATE INDEX IF NOT EXISTS idx_call_user_created
            ON communication_calls(user_id, created_at DESC)
        ');
    }

    public function down(Schema $schema): void
    {
        // Drop indexes
        $this->addSql('DROP INDEX IF EXISTS idx_message_conversation_created ON communication_messages');
        $this->addSql('DROP INDEX IF EXISTS idx_message_sender_created ON communication_messages');
        $this->addSql('DROP INDEX IF EXISTS idx_message_thread_created ON communication_messages');
        $this->addSql('DROP INDEX IF EXISTS idx_conversation_updated_at ON communication_conversations');
        $this->addSql('DROP INDEX IF EXISTS idx_participant_user_active ON communication_conversation_participants');
        $this->addSql('DROP INDEX IF EXISTS idx_notification_user_read ON communication_notifications');
        $this->addSql('DROP INDEX IF EXISTS idx_call_user_created ON communication_calls');
    }
}
