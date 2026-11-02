<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241205000006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user verification fields';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE communication_users ADD email_verified_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE communication_users ADD phone_verified_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE communication_users ADD biometric_verified_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE communication_users ADD verification_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE communication_users ADD verification_token_expires_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE communication_users ADD verification_level VARCHAR(20) DEFAULT \'basic\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE communication_users DROP email_verified_at');
        $this->addSql('ALTER TABLE communication_users DROP phone_verified_at');
        $this->addSql('ALTER TABLE communication_users DROP biometric_verified_at');
        $this->addSql('ALTER TABLE communication_users DROP verification_token');
        $this->addSql('ALTER TABLE communication_users DROP verification_token_expires_at');
        $this->addSql('ALTER TABLE communication_users DROP verification_level');
    }
}
