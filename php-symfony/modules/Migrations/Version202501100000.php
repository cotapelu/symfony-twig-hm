<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version202501100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user_refresh_tokens table for JWT refresh token support';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('user_refresh_tokens');
        $table->addColumn('id', 'string', ['length' => 64]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['id'], ['name' => 'idx_user_refresh_tokens_id']);

        $table->addColumn('user_id', 'string', ['length' => 36]);
        $table->addIndex(['user_id'], ['name' => 'idx_user_refresh_tokens_user_id']);

        $table->addColumn('device_id', 'string', ['length' => 36]);
        $table->addIndex(['device_id'], ['name' => 'idx_user_refresh_tokens_device_id']);

        $table->addColumn('token', 'string', ['length' => 255]);
        $table->addIndex(['token'], ['name' => 'idx_user_refresh_tokens_token', 'unique' => true]);

        $table->addColumn('expires_at', 'datetime');
        $table->addIndex(['expires_at'], ['name' => 'idx_user_refresh_tokens_expires_at']);

        $table->addColumn('revoked_at', 'datetime', ['notnull' => false]);
        $table->addIndex(['revoked_at'], ['name' => 'idx_user_refresh_tokens_revoked_at']);

        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->addColumn('user_agent', 'text', ['notnull' => false]);

        $table->addIndex(['user_id', 'device_id'], ['name' => 'idx_user_refresh_tokens_user_device']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('user_refresh_tokens');
    }
}
