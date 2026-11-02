<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Base class for integration tests that require database isolation.
 * 
 * Each test runs inside a transaction that is rolled back after the test,
 * ensuring a clean database state.
 * 
 * Usage: extend this class instead of WebTestCase for DB-integration tests.
 */
abstract class DatabaseIntegrationTestCase extends WebTestCase
{
    /** @var EntityManagerInterface */
    protected static $entityManager;

    /** @var \Symfony\Bundle\FrameworkBundle\KernelBrowser|null */
    protected static $client = null;

    /** @var bool Whether we started a transaction */
    private bool $transactionStarted = false;

    public static function setUpBeforeClass(): void
    {
        // Force SQLite if pdo_pgsql missing
        if (!extension_loaded('pdo_pgsql')) {
            // Override DATABASE_URL to use in-memory SQLite
            $_ENV['DATABASE_URL'] = 'sqlite:///:memory:';
            $_SERVER['DATABASE_URL'] = 'sqlite:///:memory:';
            putenv('DATABASE_URL=sqlite:///:memory:');
        }
        parent::setUpBeforeClass();
        // Boot kernel and get client
        $client = static::createClient([], [
            'HTTP_HOST' => 'localhost',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);
        self::$client = $client;
        self::getClient($client); // set trait's client
        // Disable kernel reboot to keep same EntityManager/connection across requests
        self::$client->disableReboot();
        self::$entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        // Create database schema (once)
        $metadata = self::$entityManager->getMetadataFactory()->getAllMetadata();
        error_log('DATABASE INTEGRATION TEST: Metadata count: ' . count($metadata));
        // Detect duplicate entity classes
        $classNames = [];
        foreach ($metadata as $md) {
            $name = $md->getName();
            error_log('  - ' . $name);
            $classNames[] = $name;
        }
        $duplicates = array_diff_assoc($classNames, array_unique($classNames));
        if ($duplicates) {
            error_log('DUPLICATE METADATA CLASSES: ' . implode(', ', $duplicates));
        }
        if (!empty($metadata)) {
            $schemaTool = new SchemaTool(self::$entityManager);
            // Drop schema first to ensure clean state
            try {
                $schemaTool->dropSchema($metadata);
                error_log('Schema dropped');
            } catch (\Exception $e) {
                error_log('Drop schema warning: ' . $e->getMessage());
            }
            // updateSchema is safe to run repeatedly; create tables if not exist
            try {
                $schemaTool->updateSchema($metadata, true);
                error_log('Schema update completed');
            } catch (\Doctrine\DBAL\Schema\Exception\IndexAlreadyExists $e) {
                // Ignore duplicate index errors (can happen with complex mappings)
                error_log('Ignoring IndexAlreadyExists: ' . $e->getMessage());
            }
        } else {
            error_log('No metadata found, skipping schema creation');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Use the stored entity manager (the kernel is still booted)
        // Ensure we have a fresh entity manager reference
        self::$entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        error_log('Connection params (setUp): ' . json_encode(self::$entityManager->getConnection()->getParams()));
        // Ensure clean DB state before each test: truncate all tables
        $this->truncateAllTables();
        // Start transaction for the test
        $this->startTransaction();
    }

    protected function tearDown(): void
    {
        // Rollback transaction to undo all changes
        $this->rollbackTransaction();
        // Clear EntityManager to detach all entities
        if (self::$entityManager instanceof EntityManagerInterface) {
            self::$entityManager->clear();
        }
        // Intentionally do NOT call parent::tearDown() to keep kernel alive.
        // That avoids losing the in-memory database and client.
    }

    /**
     * Start a new transaction. Subsequent queries will be part of this transaction.
     * Must be paired with rollbackTransaction().
     */
    private function startTransaction(): void
    {
        if (self::$entityManager->getConnection()->isTransactionActive()) {
            // Nested transaction – use savepoint
            self::$entityManager->getConnection()->executeStatement('SAVEPOINT test_savepoint');
        } else {
            self::$entityManager->getConnection()->beginTransaction();
        }
        $this->transactionStarted = true;
    }

    /**
     * Rollback current transaction, undoing all changes.
     */
    private function rollbackTransaction(): void
    {
        if (self::$entityManager->getConnection()->isTransactionActive()) {
            // Check if we used savepoint (by attempting to detect)
            // For simplicity, always rollback. If nested, we might need to release savepoint.
            self::$entityManager->getConnection()->rollBack();
        }
        $this->transactionStarted = false;
    }

    /**
     * Truncate all tables in the database to ensure a clean state before each test.
     */
    private function truncateAllTables(): void
    {
        $connection = self::$entityManager->getConnection();
        $schemaManager = $connection->createSchemaManager();
        $tables = $schemaManager->listTableNames();
        error_log('TRUNCATE: Found tables: ' . implode(', ', $tables));
        $platform = $connection->getDatabasePlatform();

        if ($platform instanceof PostgreSQLPlatform) {
            $connection->executeStatement('SET session_replication_role = \'replica\'');
            foreach ($tables as $table) {
                $connection->executeStatement(sprintf('TRUNCATE TABLE %s RESTART IDENTITY CASCADE', $table));
            }
            $connection->executeStatement('SET session_replication_role = \'origin\'');
        } else {
            // SQLite, MySQL, etc.
            // Disable foreign key checks for SQLite
            if ($platform instanceof SQLitePlatform) {
                $connection->executeStatement('PRAGMA foreign_keys = OFF');
            }
            foreach ($tables as $table) {
                $connection->executeStatement(sprintf('DELETE FROM %s', $table));
            }
            if ($platform instanceof SQLitePlatform) {
                $connection->executeStatement('PRAGMA foreign_keys = ON');
            }
        }
    }

    /**
     * Override createClient to reuse the pre‑booted client from setUpBeforeClass.
     * This avoids "booting the kernel before calling createClient" errors when tests call createClient.
     */
    protected static function createClient(array $kernelOptions = [], array $configuration = []): KernelBrowser
    {
        if (self::$client === null) {
            // Call parent only if client not yet created (should normally not happen).
            self::$client = parent::createClient($kernelOptions, $configuration);
            self::$client->disableReboot();
        }
        return self::$client;
    }

    /**
     * Helper: get EntityManager for direct DB manipulations (seeding, assertions).
     */
    protected function getEntityManager(): EntityManagerInterface
    {
        return self::$entityManager;
    }
}
