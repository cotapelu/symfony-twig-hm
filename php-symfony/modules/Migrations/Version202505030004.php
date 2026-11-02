<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version202505030004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add deleted_at column to communication_users table for GDPR soft delete';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE php.communication_users ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_user_deleted_at ON php.communication_users (deleted_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE php.communication_users DROP deleted_at');
        $this->addSql('DROP INDEX idx_user_deleted_at ON php.communication_users');
    }
}
