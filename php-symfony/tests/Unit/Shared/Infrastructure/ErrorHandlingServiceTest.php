<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure;

use App\Exception\AuthenticationException;
use App\Exception\AuthorizationException;
use App\Exception\NotFoundException;
use App\Exception\RateLimitException;
use App\Exception\ValidationException;
use App\Shared\Infrastructure\ErrorHandlingService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

#[AllowMockObjectsWithoutExpectations]
class ErrorHandlingServiceTest extends TestCase
{
    private ErrorHandlingService $service;
    private LoggerInterface&MockObject $loggerMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->service = new ErrorHandlingService($this->loggerMock, false);
    }

    public function testHandleExceptionLogsErrorAndReturnsResponse(): void
    {
        $exception = new \RuntimeException('Test error', 500);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Application error', $this->callback(function(array $context) {
                return isset($context['error_id'], $context['message'], $context['file'], $context['line'], $context['trace'], $context['context']);
            }));

        $response = $this->service->handleException($exception, 'test_context');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('id', $data['error']);
        $this->assertArrayHasKey('message', $data['error']);
        $this->assertArrayHasKey('type', $data['error']);
        $this->assertEquals('runtime_error', $data['error']['type']);
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testHandleExceptionWithValidationExceptionIncludesErrors(): void
    {
        $errors = ['email' => 'Invalid email', 'password' => 'Too short'];
        $exception = new ValidationException('Validation failed', $errors);

        $response = $this->service->handleException($exception);

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('details', $data['error']);
        $this->assertEquals($errors, $data['error']['details']);
    }

    public function testHandleExceptionWithRateLimitExceptionIncludesMeta(): void
    {
        $exception = new RateLimitException('Rate limit exceeded', 60); // retryAfter=60

        $response = $this->service->handleException($exception);

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('remaining', $data);
        $this->assertArrayHasKey('reset_time', $data);
        $this->assertEquals(0, $data['remaining']); // remaining not tracked
        $this->assertGreaterThan(time(), $data['reset_time']); // reset_time = now + retryAfter
    }

    public function testHandleExceptionWithDebugModeIncludesDebugInfo(): void
    {
        $debugService = new ErrorHandlingService($this->loggerMock, true);
        $exception = new \RuntimeException('Debug test');

        $response = $debugService->handleException($exception);

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('debug', $data);
        $this->assertArrayHasKey('message', $data['debug']);
        $this->assertArrayHasKey('file', $data['debug']);
        $this->assertArrayHasKey('line', $data['debug']);
        $this->assertArrayHasKey('trace', $data['debug']);
    }

    public function testHandleExceptionMapsAuthenticationException(): void
    {
        $exception = new AuthenticationException('Invalid token');
        $response = $this->service->handleException($exception);
        $this->assertEquals(401, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('authentication_error', $data['error']['type']);
    }

    public function testHandleExceptionMapsAuthorizationException(): void
    {
        $exception = new AuthorizationException('Access denied');
        $response = $this->service->handleException($exception);
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('authorization_error', $data['error']['type']);
    }

    public function testHandleExceptionMapsNotFoundException(): void
    {
        $exception = new NotFoundException('Not found');
        $response = $this->service->handleException($exception);
        $this->assertEquals(404, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('not_found_error', $data['error']['type']);
    }

    public function testHandleExceptionProvidesFriendlyMessageForSqlErrors(): void
    {
        $exception = new \RuntimeException('SQLSTATE[HY000] Connection refused');
        $response = $this->service->handleException($exception);
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Database error occurred', $data['error']['message']);
    }

    public function testHandleExceptionProvidesFriendlyMessageForTimeout(): void
    {
        $exception = new \RuntimeException('Request timeout');
        $response = $this->service->handleException($exception);
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Request timed out', $data['error']['message']);
    }

    public function testHandleExceptionProvidesFriendlyMessageForPermission(): void
    {
        $exception = new \RuntimeException('permission denied'); // lowercase to match check
        $response = $this->service->handleException($exception);
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Access denied', $data['error']['message']);
    }

    public function testCreateValidationError(): void
    {
        $errors = ['field' => 'error'];
        $response = $this->service->createValidationError($errors);
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Validation failed', $data['error']);
        $this->assertEquals($errors, $data['details']);
    }

    public function testCreateRateLimitError(): void
    {
        $remaining = [100, 50];
        $resetTime = [60, 120];
        $response = $this->service->createRateLimitError($remaining, $resetTime);
        $this->assertEquals(429, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Rate limit exceeded', $data['error']);
        $this->assertEquals($remaining, $data['remaining']);
        $this->assertEquals($resetTime, $data['reset_time']);
    }

    public function testCreateAuthError(): void
    {
        $response = $this->service->createAuthError('Custom message');
        $this->assertEquals(401, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Custom message', $data['error']);
    }

    public function testCreateForbiddenError(): void
    {
        $response = $this->service->createForbiddenError('Custom forbid');
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Custom forbid', $data['error']);
    }

    public function testCreateNotFoundError(): void
    {
        $response = $this->service->createNotFoundError('Custom not found');
        $this->assertEquals(404, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Custom not found', $data['error']);
    }

    public function testHandleExceptionWithEmptyMessage(): void
    {
        $exception = new \RuntimeException('');
        $response = $this->service->handleException($exception);
        $data = json_decode($response->getContent(), true);
        // Empty message should still return generic friendly message for runtime exceptions
        $this->assertEquals('An unexpected error occurred', $data['error']['message']);
    }

    public function testHandleExceptionWithInvalidArgumentExceptionReturnsOriginal(): void
    {
        $exception = new \InvalidArgumentException('Invalid input');
        $response = $this->service->handleException($exception);
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Invalid input', $data['error']['message']);
    }
}
