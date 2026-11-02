<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration: Add comprehensive user profile fields.
 */
final class Version20241205000004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add comprehensive user profile fields to communication_users table';
    }

    public function up(Schema $schema): void
    {
        // Add comprehensive profile fields to users table
        $this->addSql('ALTER TABLE communication_users ADD COLUMN bio TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE communication_users ADD COLUMN location VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE communication_users ADD COLUMN website VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE communication_users ADD COLUMN social_links JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE communication_users ADD COLUMN birth_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE communication_users ADD COLUMN gender VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE communication_users ADD COLUMN occupation VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE communication_users ADD COLUMN interests JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE communication_users ADD COLUMN custom_fields JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE communication_users ADD COLUMN profile_updated_at DATETIME DEFAULT NULL');

        // Add indexes for commonly queried fields
        $this->addSql('CREATE INDEX IDX_USER_LOCATION ON communication_users (location)');
        $this->addSql('CREATE INDEX IDX_USER_OCCUPATION ON communication_users (occupation)');
        $this->addSql('CREATE INDEX IDX_USER_PROFILE_UPDATED ON communication_users (profile_updated_at)');
    }

    public function down(Schema $schema): void
    {
        // Remove profile fields
        $this->addSql('DROP INDEX IF EXISTS IDX_USER_PROFILE_UPDATED');
        $this->addSql('DROP INDEX IF EXISTS IDX_USER_OCCUPATION');
        $this->addSql('DROP INDEX IF EXISTS IDX_USER_LOCATION');

        $this->addSql('ALTER TABLE communication_users DROP COLUMN profile_updated_at');
        $this->addSql('ALTER TABLE communication_users DROP COLUMN custom_fields');
        $this->addSql('ALTER TABLE communication_users DROP COLUMN interests');
        $this->addSql('ALTER TABLE communication_users DROP COLUMN occupation');
        $this->addSql('ALTER TABLE communication_users DROP COLUMN gender');
        $this->addSql('ALTER TABLE communication_users DROP COLUMN birth_date');
        $this->addSql('ALTER TABLE communication_users DROP COLUMN social_links');
        $this->addSql('ALTER TABLE communication_users DROP COLUMN website');
        $this->addSql('ALTER TABLE communication_users DROP COLUMN location');
        $this->addSql('ALTER TABLE communication_users DROP COLUMN bio');
    }
}
