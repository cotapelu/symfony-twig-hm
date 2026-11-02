<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version202505040001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create feature_flags table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE feature_flags (
            id SERIAL PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            enabled BOOLEAN NOT NULL DEFAULT FALSE,
            description TEXT NULL,
            created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE feature_flags');
    }
}
