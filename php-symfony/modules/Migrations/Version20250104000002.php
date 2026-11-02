<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250104000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tables for Groups/Community module';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE groups (
                id VARCHAR(36) NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT DEFAULT NULL,
                avatar VARCHAR(255) DEFAULT NULL,
                is_public BOOLEAN NOT NULL DEFAULT FALSE,
                is_community BOOLEAN NOT NULL DEFAULT FALSE,
                is_channel BOOLEAN NOT NULL DEFAULT FALSE,
                is_forum BOOLEAN NOT NULL DEFAULT FALSE,
                max_members INT NOT NULL DEFAULT 500,
                settings JSON DEFAULT NULL,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY(id),
                INDEX idx_groups_active (is_active),
                INDEX idx_groups_public (is_public)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');

        $this->addSql('
            CREATE TABLE group_members (
                id VARCHAR(36) NOT NULL,
                group_id VARCHAR(36) DEFAULT NULL,
                user_id VARCHAR(36) NOT NULL,
                role VARCHAR(50) NOT NULL DEFAULT "member",
                is_muted BOOLEAN NOT NULL DEFAULT FALSE,
                is_banned BOOLEAN NOT NULL DEFAULT FALSE,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                joined_at DATETIME NOT NULL,
                last_active_at DATETIME NOT NULL,
                metadata JSON DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY(id),
                INDEX idx_group_member_user (user_id),
                INDEX idx_group_member_group (group_id),
                INDEX idx_group_member_role (role),
                INDEX idx_group_member_active (is_active),
                FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE group_members');
        $this->addSql('DROP TABLE groups');
    }
}
