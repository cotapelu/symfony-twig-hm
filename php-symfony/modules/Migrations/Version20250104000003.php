<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250104000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create table for Subscription module';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE subscriptions (
                id VARCHAR(36) NOT NULL,
                user_id VARCHAR(36) NOT NULL,
                plan_id VARCHAR(100) NOT NULL,
                status VARCHAR(50) NOT NULL DEFAULT "pending",
                started_at DATETIME NOT NULL,
                ended_at DATETIME DEFAULT NULL,
                auto_renew BOOLEAN NOT NULL DEFAULT TRUE,
                amount DECIMAL(10, 2) NOT NULL,
                currency VARCHAR(50) NOT NULL DEFAULT "USD",
                features JSON DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY(id),
                INDEX idx_subscription_user (user_id),
                INDEX idx_subscription_plan (plan_id),
                INDEX idx_subscription_status (status),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE subscriptions');
    }
}
