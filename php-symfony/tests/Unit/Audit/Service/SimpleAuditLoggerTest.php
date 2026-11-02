<?php declare(strict_types=1);

namespace App\Tests\Unit\Audit\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Audit\Service\SimpleAuditLogger;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SimpleAuditLoggerTest extends TestCase
{
    private string $tempDir;
    private string $logFile;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/audit_test_' . uniqid();
        mkdir($this->tempDir, 0700, true);
        $this->logFile = $this->tempDir . '/audit.log';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }
        rmdir($this->tempDir);
    }

    public function testLogCreatesFileAndWritesValidJsonLine(): void
    {
        $logger = new SimpleAuditLogger($this->tempDir . '/');
        $logger->log('user-123', 'create', 'App\\User\\Entity\\User', ['id' => 1]);

        $this->assertFileExists($this->logFile);
        $line = trim(file_get_contents($this->logFile));
        $data = json_decode($line, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('timestamp', $data);
        $this->assertArrayHasKey('user_id', $data);
        $this->assertArrayHasKey('action', $data);
        $this->assertArrayHasKey('entity', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertSame('user-123', $data['user_id']);
        $this->assertSame('create', $data['action']);
        $this->assertSame('App\\User\\Entity\\User', $data['entity']);
        $this->assertSame(['id' => 1], $data['data']);
    }

    public function testLogAppendsMultipleEntries(): void
    {
        $logger = new SimpleAuditLogger($this->tempDir . '/');
        $logger->log('user-1', 'action1', 'Entity1', ['a' => 1]);
        $logger->log('user-2', 'action2', 'Entity2', ['b' => 2]);

        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES);
        $this->assertCount(2, $lines);

        $data1 = json_decode($lines[0], true);
        $data2 = json_decode($lines[1], true);

        $this->assertSame('user-1', $data1['user_id']);
        $this->assertSame('action1', $data1['action']);
        $this->assertSame('user-2', $data2['user_id']);
        $this->assertSame('action2', $data2['action']);
    }

    public function testLogWithEmptyDataWritesEmptyDataField(): void
    {
        $logger = new SimpleAuditLogger($this->tempDir . '/');
        $logger->log('system', 'system_start', 'App\\Core\\System', []);

        $line = trim(file_get_contents($this->logFile));
        $data = json_decode($line, true);
        $this->assertSame([], $data['data']);
    }
}
