<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241205000008 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create device_tokens table for push notifications';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE device_tokens (id VARCHAR(36) NOT NULL, user_id VARCHAR(36) NOT NULL, token VARCHAR(500) NOT NULL, platform VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, last_used_at DATETIME DEFAULT NULL, is_active TINYINT(1) DEFAULT 1 NOT NULL, INDEX IDX_device_tokens_user_id (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE device_tokens');
    }
}
