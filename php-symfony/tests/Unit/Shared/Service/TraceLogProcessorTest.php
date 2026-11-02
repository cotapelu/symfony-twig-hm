<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\Service\TraceLogProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

#[AllowMockObjectsWithoutExpectations]
class TraceLogProcessorTest extends TestCase
{
    private TraceLogProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new TraceLogProcessor();
        // Ensure clean static state
        $this->resetTracingMiddlewareStatic();
    }

    public function testProcessWithNoTraceDoesNotAddTraceId(): void
    {
        $record = new LogRecord(new \DateTimeImmutable(), 'channel', Level::Info, 'message');
        $result = ($this->processor)($record);
        $this->assertArrayNotHasKey('trace_id', $result->extra);
    }

    public function testProcessAddsTraceIdWhenPresent(): void
    {
        $traceId = '0af7651916cd43dd8448eb211c80319c';
        $this->setTracingTraceId($traceId);

        $record = new LogRecord(new \DateTimeImmutable(), 'channel', Level::Info, 'message');
        $result = ($this->processor)($record);
        $this->assertSame($traceId, $result->extra['trace_id']);
    }

    public function testProcessWithArrayRecordAddsTraceId(): void
    {
        $traceId = 'b7ad6b7169203331c4a1b2c3d4e5f6a7';
        $this->setTracingTraceId($traceId);

        $record = [
            'channel' => 'app',
            'level' => \Psr\Log\LogLevel::WARNING,
            'message' => 'Something happened',
            'context' => [],
            'datetime' => new \DateTimeImmutable(),
        ];
        $result = ($this->processor)($record);
        $this->assertSame($traceId, $result['extra']['trace_id']);
    }

    public function testProcessDoesNotBreakRecordStructure(): void
    {
        $this->setTracingTraceId('xyz123');

        $record = new LogRecord(new \DateTimeImmutable(), 'test', Level::Error, 'error msg', ['key' => 'value']);
        $result = ($this->processor)($record);

        $this->assertInstanceOf(LogRecord::class, $result);
        $this->assertSame('error msg', $result->message);
        $this->assertSame('test', $result->channel);
        $this->assertArrayHasKey('key', $result->context);
    }

    private function resetTracingMiddlewareStatic(): void
    {
        $refClass = new \ReflectionClass(\App\Shared\Middleware\TracingMiddleware::class);
        $currentTraceProp = $refClass->getProperty('currentTrace');
        $currentTraceProp->setAccessible(true);
        $currentTraceProp->setValue(null, []);
        $spansProp = $refClass->getProperty('spans');
        $spansProp->setAccessible(true);
        $spansProp->setValue(null, []);
    }

    private function setTracingTraceId(string $traceId): void
    {
        $refClass = new \ReflectionClass(\App\Shared\Middleware\TracingMiddleware::class);
        $currentTraceProp = $refClass->getProperty('currentTrace');
        $currentTraceProp->setAccessible(true);
        $currentTraceProp->setValue(null, ['trace_id' => $traceId, 'span_id' => 'span123']);
    }
}
