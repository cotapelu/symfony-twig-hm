<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241205000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users table for authentication';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE communication_users (
            id VARCHAR(36) NOT NULL PRIMARY KEY,
            email VARCHAR(180) NOT NULL UNIQUE,
            roles TEXT NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL,
            last_login_at DATETIME DEFAULT NULL,
            is_active INTEGER DEFAULT 1 NOT NULL,
            phone_number VARCHAR(20) DEFAULT NULL,
            social_provider VARCHAR(100) DEFAULT NULL,
            social_id VARCHAR(255) DEFAULT NULL,
            biometric_data TEXT DEFAULT NULL,
            preferred_language VARCHAR(10) DEFAULT "en",
            timezone VARCHAR(50) DEFAULT "UTC",
            notification_settings TEXT DEFAULT NULL,
            privacy_settings TEXT DEFAULT NULL,
            push_subscription TEXT DEFAULT NULL
        )');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE communication_users');
    }
}
