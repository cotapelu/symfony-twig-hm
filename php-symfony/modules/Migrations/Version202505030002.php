<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version202505030002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add PostgreSQL GIN indexes for full-text search optimization';
    }

    public function up(Schema $schema): void
    {
        // Messages: full-text search on content
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_messages_content_gin ON php.communication_messages USING GIN (to_tsvector(\'english\', content))');

        // Notifications: full-text search on title and message (combined)
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_notifications_title_message_gin ON php.notifications USING GIN (to_tsvector(\'english\', title || \' \' || message))');

        // Articles (if exists): search on title and content
        // $this->addSql('CREATE INDEX IF NOT EXISTS idx_news_article_title_content_gin ON php.news_article USING GIN (to_tsvector(\'english\', title || \' \' || content))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_messages_content_gin ON php.communication_messages');
        $this->addSql('DROP INDEX IF EXISTS idx_notifications_title_message_gin ON php.notifications');
        // $this->addSql('DROP INDEX IF EXISTS idx_news_article_title_content_gin ON php.news_article');
    }
}
