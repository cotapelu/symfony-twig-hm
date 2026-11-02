<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241205000007 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix schema sync issues - add missing columns and tables';
    }

    public function up(Schema $schema): void
    {
        // Add missing columns to communication_messages
        $this->addSql('ALTER TABLE communication_messages ADD COLUMN delivered_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE communication_messages ADD COLUMN read_at DATETIME DEFAULT NULL');

        // Add missing columns to communication_users
        $this->addSql('ALTER TABLE communication_users ADD COLUMN email_verified_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE communication_users ADD COLUMN phone_verified_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE communication_users ADD COLUMN biometric_verified_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE communication_users ADD COLUMN verificationToken VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE communication_users ADD COLUMN verification_token_expires_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE communication_users ADD COLUMN verificationLevel VARCHAR(20) DEFAULT \'basic\' NOT NULL');

        // Create communication_message_reads table
        $this->addSql('
            CREATE TABLE communication_message_reads (
                id VARCHAR(36) NOT NULL,
                messageId VARCHAR(36) NOT NULL,
                userId VARCHAR(36) NOT NULL,
                read_at DATETIME NOT NULL,
                PRIMARY KEY (id)
            )
        ');

        // Create communication_polls table
        $this->addSql('
            CREATE TABLE communication_polls (
                id VARCHAR(36) NOT NULL,
                messageId VARCHAR(36) NOT NULL,
                question VARCHAR(500) NOT NULL,
                options CLOB NOT NULL,
                votes CLOB NOT NULL,
                creatorId VARCHAR(36) NOT NULL,
                created_at DATETIME NOT NULL,
                expires_at DATETIME DEFAULT NULL,
                is_active BOOLEAN DEFAULT 1 NOT NULL,
                PRIMARY KEY (id)
            )
        ');
    }

    public function down(Schema $schema): void
    {
        // Remove added columns
        $this->addSql('ALTER TABLE communication_messages DROP COLUMN delivered_at');
        $this->addSql('ALTER TABLE communication_messages DROP COLUMN read_at');

        $this->addSql('ALTER TABLE communication_users DROP COLUMN email_verified_at');
        $this->addSql('ALTER TABLE communication_users DROP COLUMN phone_verified_at');
        $this->addSql('ALTER TABLE communication_users DROP COLUMN biometric_verified_at');
        $this->addSql('ALTER TABLE communication_users DROP COLUMN verificationToken');
        $this->addSql('ALTER TABLE communication_users DROP COLUMN verification_token_expires_at');
        $this->addSql('ALTER TABLE communication_users DROP COLUMN verificationLevel');

        // Drop tables
        $this->addSql('DROP TABLE communication_message_reads');
        $this->addSql('DROP TABLE communication_polls');
    }
}
