<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250110000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user_refresh_tokens table for JWT refresh token support';
    }

    public function up(Schema $schema): void
    {
        $connection = $this->getConnection();
        $connection->beginTransaction();
        try {
            $this->addSql('CREATE TABLE user_refresh_tokens (
                id VARCHAR(64) NOT NULL,
                user_id VARCHAR(36) NOT NULL,
                device_id VARCHAR(36) NOT NULL,
                token VARCHAR(255) NOT NULL,
                expires_at DATETIME NOT NULL,
                revoked_at DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                user_agent TEXT DEFAULT NULL,
                PRIMARY KEY(id)
            )');

            $this->addSql('CREATE INDEX idx_user_refresh_tokens_id ON user_refresh_tokens (id)');
            $this->addSql('CREATE INDEX idx_user_refresh_tokens_user_id ON user_refresh_tokens (user_id)');
            $this->addSql('CREATE INDEX idx_user_refresh_tokens_device_id ON user_refresh_tokens (device_id)');
            $this->addSql('CREATE UNIQUE INDEX idx_user_refresh_tokens_token ON user_refresh_tokens (token)');
            $this->addSql('CREATE INDEX idx_user_refresh_tokens_expires_at ON user_refresh_tokens (expires_at)');
            $this->addSql('CREATE INDEX idx_user_refresh_tokens_revoked_at ON user_refresh_tokens (revoked_at)');
            $this->addSql('CREATE INDEX idx_user_refresh_tokens_user_device ON user_refresh_tokens (user_id, device_id)');
            $this->addSql('CREATE INDEX idx_user_refresh_tokens_valid ON user_refresh_tokens (expires_at, revoked_at) WHERE revoked_at IS NULL');

            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    public function down(Schema $schema): void
    {
        $connection = $this->getConnection();
        $connection->beginTransaction();
        try {
            $this->addSql('DROP TABLE user_refresh_tokens');
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }
}
