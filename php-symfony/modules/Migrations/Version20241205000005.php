<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration: Fix Conversation entity participantIds field type.
 */
final class Version20241205000005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix Conversation entity participantIds field type from text to json';
    }

    public function up(Schema $schema): void
    {
        // Convert participant_ids from text to json
        $this->addSql('ALTER TABLE communication_conversations ALTER COLUMN participant_ids TYPE JSON USING participant_ids::json');
    }

    public function down(Schema $schema): void
    {
        // Convert back from json to text (this will lose array structure)
        $this->addSql('ALTER TABLE communication_conversations ALTER COLUMN participant_ids TYPE TEXT');
    }
}
