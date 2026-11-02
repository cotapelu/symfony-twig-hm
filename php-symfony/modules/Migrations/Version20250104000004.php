<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250104000004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tables for Rewards module';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE achievement_badges (
                id VARCHAR(36) NOT NULL,
                user_id VARCHAR(36) NOT NULL,
                type VARCHAR(100) NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT DEFAULT NULL,
                icon VARCHAR(255) DEFAULT NULL,
                metadata JSON DEFAULT NULL,
                earned_at DATETIME NOT NULL,
                PRIMARY KEY(id),
                INDEX idx_badge_user (user_id),
                INDEX idx_badge_type (type),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');

        $this->addSql('
            CREATE TABLE reward_points (
                id VARCHAR(36) NOT NULL,
                user_id VARCHAR(36) NOT NULL,
                points INT NOT NULL DEFAULT 0,
                level INT NOT NULL DEFAULT 1,
                lifetime_points INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY(id),
                UNIQUE INDEX idx_reward_user (user_id),
                INDEX idx_reward_points (points),
                INDEX idx_reward_level (level),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE achievement_badges');
        $this->addSql('DROP TABLE reward_points');
    }
}
