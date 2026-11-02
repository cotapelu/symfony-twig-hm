<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add foreign key constraints for data integrity.
 */
final class Version20241205000011 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add foreign key constraints and improve data integrity';
    }

    public function up(Schema $schema): void
    {
        // Add foreign key for messages -> conversations
        $this->addSql('ALTER TABLE communication_messages ADD CONSTRAINT FK_messages_conversation FOREIGN KEY (conversation_id) REFERENCES communication_conversations (id) ON DELETE CASCADE');

        // Add foreign key for message read receipts -> messages
        $this->addSql('ALTER TABLE communication_message_read_receipts ADD CONSTRAINT FK_read_receipts_message FOREIGN KEY (message_id) REFERENCES communication_messages (id) ON DELETE CASCADE');

        // Add foreign key for message read receipts -> users
        $this->addSql('ALTER TABLE communication_message_read_receipts ADD CONSTRAINT FK_read_receipts_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');

        // Add foreign key for conversations -> users (creator)
        $this->addSql('ALTER TABLE communication_conversations ADD CONSTRAINT FK_conversations_creator FOREIGN KEY (creator_id) REFERENCES users (id)');

        // Add foreign key for messages -> users (sender)
        $this->addSql('ALTER TABLE communication_messages ADD CONSTRAINT FK_messages_sender FOREIGN KEY (sender_id) REFERENCES users (id)');

        // Add check constraints for data validation
        $this->addSql('ALTER TABLE communication_messages ADD CONSTRAINT CHK_message_type CHECK (type IN (\'text\', \'image\', \'file\', \'voice\', \'video\'))');

        $this->addSql('ALTER TABLE communication_conversations ADD CONSTRAINT CHK_conversation_type CHECK (type IN (\'direct\', \'group\', \'channel\'))');

        // Add NOT NULL constraints where appropriate
        $this->addSql('ALTER TABLE communication_messages ALTER COLUMN conversation_id SET NOT NULL');
        $this->addSql('ALTER TABLE communication_messages ALTER COLUMN sender_id SET NOT NULL');
        $this->addSql('ALTER TABLE communication_messages ALTER COLUMN content SET NOT NULL');
        $this->addSql('ALTER TABLE communication_messages ALTER COLUMN type SET NOT NULL');

        $this->addSql('ALTER TABLE communication_conversations ALTER COLUMN creator_id SET NOT NULL');
        $this->addSql('ALTER TABLE communication_conversations ALTER COLUMN title SET NOT NULL');
        $this->addSql('ALTER TABLE communication_conversations ALTER COLUMN type SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Remove constraints in reverse order
        $this->addSql('ALTER TABLE communication_conversations DROP CONSTRAINT CHK_conversation_type');
        $this->addSql('ALTER TABLE communication_messages DROP CONSTRAINT CHK_message_type');

        $this->addSql('ALTER TABLE communication_messages DROP CONSTRAINT FK_messages_sender');
        $this->addSql('ALTER TABLE communication_conversations DROP CONSTRAINT FK_conversations_creator');
        $this->addSql('ALTER TABLE communication_message_read_receipts DROP CONSTRAINT FK_read_receipts_user');
        $this->addSql('ALTER TABLE communication_message_read_receipts DROP CONSTRAINT FK_read_receipts_message');
        $this->addSql('ALTER TABLE communication_messages DROP CONSTRAINT FK_messages_conversation');

        // Remove NOT NULL constraints
        $this->addSql('ALTER TABLE communication_conversations ALTER COLUMN type DROP NOT NULL');
        $this->addSql('ALTER TABLE communication_conversations ALTER COLUMN title DROP NOT NULL');
        $this->addSql('ALTER TABLE communication_conversations ALTER COLUMN creator_id DROP NOT NULL');

        $this->addSql('ALTER TABLE communication_messages ALTER COLUMN type DROP NOT NULL');
        $this->addSql('ALTER TABLE communication_messages ALTER COLUMN content DROP NOT NULL');
        $this->addSql('ALTER TABLE communication_messages ALTER COLUMN sender_id DROP NOT NULL');
        $this->addSql('ALTER TABLE communication_messages ALTER COLUMN conversation_id DROP NOT NULL');
    }
}
