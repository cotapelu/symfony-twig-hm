<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250103000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add phone verification fields to user_verifications table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE communication_user_verifications ADD phone_code VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE communication_user_verifications ADD phone_code_expires_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE communication_user_verifications ADD phone_code_attempts INT DEFAULT 0');
        $this->addSql('ALTER TABLE communication_user_verifications ADD phone_number VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE communication_user_verifications ADD last_phone_sent_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE communication_user_verifications DROP phone_code');
        $this->addSql('ALTER TABLE communication_user_verifications DROP phone_code_expires_at');
        $this->addSql('ALTER TABLE communication_user_verifications DROP phone_code_attempts');
        $this->addSql('ALTER TABLE communication_user_verifications DROP phone_number');
        $this->addSql('ALTER TABLE communication_user_verifications DROP last_phone_sent_at');
    }
}
