<?php

declare(strict_types=1);

// Display all errors for debugging notices
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set test environment before loading env
$_ENV['APP_ENV'] = 'test';
$_SERVER['APP_ENV'] = 'test';

require_once __DIR__ . '/../vendor/autoload.php';

// Allow mock objects without expectations to reduce notices
if (class_exists(\PHPUnit\Framework\MockObject\MockObject::class)) {
    \PHPUnit\Framework\MockObject\MockObject::setAllowMockWithoutExpectations(true);
}

use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__ . '/../.env');

// Determine database based on test suite or env
// Check multiple sources for DATABASE_URL (phpunit.xml env, system env, .env)
$databaseUrl = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? getenv('DATABASE_URL') ?? 'sqlite:///' . dirname(__DIR__) . '/var/test.db';

error_log('BOOTSTRAP: DATABASE_URL = ' . $databaseUrl);

// Force SQLite file database for tests if not explicitly set to postgresql
if (!str_starts_with($databaseUrl, 'postgresql://')) {
    $projectDir = dirname(__DIR__);
    $testDbPath = $projectDir . '/var/test.db';
    // Remove existing DB file to start fresh
    if (file_exists($testDbPath)) {
        @unlink($testDbPath);
    }
    $databaseUrl = 'sqlite:///' . $testDbPath;
}

putenv('DATABASE_URL=' . $databaseUrl);
$_SERVER['DATABASE_URL'] = $databaseUrl;
$_ENV['DATABASE_URL'] = $databaseUrl;

// Ensure JWT secret is long enough for HS256
if (empty($_ENV['JWT_SECRET']) || strlen($_ENV['JWT_SECRET']) < 32) {
    $_ENV['JWT_SECRET'] = 'testsecrettestsecrettestsecret123';
    $_SERVER['JWT_SECRET'] = $_ENV['JWT_SECRET'];
    putenv('JWT_SECRET=' . $_ENV['JWT_SECRET']);
}