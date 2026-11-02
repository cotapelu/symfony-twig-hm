<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241205000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add thread_id column to communication_messages table';
    }

    public function up(Schema $schema): void
    {
        // Add thread_id column to messages table
        $this->addSql('ALTER TABLE communication_messages ADD COLUMN thread_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_THREAD_ID ON communication_messages (thread_id)');
    }

    public function down(Schema $schema): void
    {
        // Remove thread_id column
        $this->addSql('DROP INDEX IF EXISTS IDX_THREAD_ID');
        $this->addSql('ALTER TABLE communication_messages DROP COLUMN thread_id');
    }
}
