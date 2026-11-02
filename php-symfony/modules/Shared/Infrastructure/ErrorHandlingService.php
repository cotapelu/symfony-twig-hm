<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure;

use App\Exception\AuthenticationException;
use App\Exception\AuthorizationException;
use App\Exception\NotFoundException;
use App\Exception\RateLimitException;
use App\Exception\ValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ErrorHandlingService
{
    public function __construct(
        private LoggerInterface $logger,
        private bool $debug = false,
    ) {
    }

    public function handleException(\Throwable $exception, string $context = ''): JsonResponse
    {
        $errorId = uniqid('err_', true);
        $message = $exception->getMessage();
        $code = $exception->getCode();

        // Log the error
        $this->logger->error('Application error', [
            'error_id' => $errorId,
            'message' => $message,
            'code' => $code,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context,
        ]);

        // Determine HTTP status code
        $statusCode = $this->getHttpStatusCode($exception);

        // Build error response
        $errorResponse = [
            'error' => [
                'id' => $errorId,
                'message' => $this->getUserFriendlyMessage($exception),
                'type' => $this->getErrorType($exception),
            ],
        ];

        // Add validation errors for ValidationException
        if ($exception instanceof ValidationException) {
            $errorResponse['error']['details'] = $exception->getErrors();
        }

        // Add rate limit info for RateLimitException
        if ($exception instanceof RateLimitException) {
            // RateLimitException provides retry_after as seconds
            $errorResponse['remaining'] = 0; // Not tracked by current exception
            $errorResponse['reset_time'] = time() + $exception->getRetryAfter();
        }

        // Add debug information in development
        if ($this->debug) {
            $errorResponse['debug'] = [
                'message' => $message,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => explode("\n", $exception->getTraceAsString()),
            ];
        }

        return new JsonResponse($errorResponse, $statusCode);
    }

    public function createValidationError(array $errors): JsonResponse
    {
        return new JsonResponse([
            'error' => 'Validation failed',
            'details' => $errors,
        ], Response::HTTP_BAD_REQUEST);
    }

    public function createRateLimitError(array $remaining, array $resetTime): JsonResponse
    {
        return new JsonResponse([
            'error' => 'Rate limit exceeded',
            'remaining' => $remaining,
            'reset_time' => $resetTime,
        ], Response::HTTP_TOO_MANY_REQUESTS);
    }

    public function createAuthError(string $message = 'Authentication required'): JsonResponse
    {
        return new JsonResponse([
            'error' => $message,
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function createForbiddenError(string $message = 'Access denied'): JsonResponse
    {
        return new JsonResponse([
            'error' => $message,
        ], Response::HTTP_FORBIDDEN);
    }

    public function createNotFoundError(string $message = 'Resource not found'): JsonResponse
    {
        return new JsonResponse([
            'error' => $message,
        ], Response::HTTP_NOT_FOUND);
    }

    private function getHttpStatusCode(\Throwable $exception): int
    {
        // Map exception types to HTTP status codes
        return match (get_class($exception)) {
            ValidationException::class => Response::HTTP_BAD_REQUEST,
            AuthenticationException::class => Response::HTTP_UNAUTHORIZED,
            AuthorizationException::class => Response::HTTP_FORBIDDEN,
            NotFoundException::class => Response::HTTP_NOT_FOUND,
            RateLimitException::class => Response::HTTP_TOO_MANY_REQUESTS,
            \InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
            \DomainException::class => Response::HTTP_BAD_REQUEST,
            \UnexpectedValueException::class => Response::HTTP_BAD_REQUEST,
            \LengthException::class => Response::HTTP_BAD_REQUEST,
            \OutOfBoundsException::class => Response::HTTP_BAD_REQUEST,
            \LogicException::class => Response::HTTP_BAD_REQUEST,
            \RuntimeException::class => Response::HTTP_INTERNAL_SERVER_ERROR,
            \Error::class => Response::HTTP_INTERNAL_SERVER_ERROR,
            default => Response::HTTP_INTERNAL_SERVER_ERROR,
        };
    }

    private function getUserFriendlyMessage(\Throwable $exception): string
    {
        // Return user-friendly messages instead of technical errors
        $message = $exception->getMessage();

        if (str_contains($message, 'SQLSTATE')) {
            return 'Database error occurred';
        }

        if (str_contains($message, 'Connection refused')) {
            return 'Service temporarily unavailable';
        }

        if (str_contains($message, 'timeout')) {
            return 'Request timed out';
        }

        if (str_contains($message, 'permission')) {
            return 'Access denied';
        }

        // For validation errors, return as-is
        if ($exception instanceof \InvalidArgumentException) {
            return $message;
        }

        // Generic fallback
        return 'An unexpected error occurred';
    }

    private function getErrorType(\Throwable $exception): string
    {
        return match (get_class($exception)) {
            ValidationException::class => 'validation_error',
            AuthenticationException::class => 'authentication_error',
            AuthorizationException::class => 'authorization_error',
            NotFoundException::class => 'not_found_error',
            RateLimitException::class => 'rate_limit_error',
            \InvalidArgumentException::class => 'validation_error',
            \DomainException::class => 'business_logic_error',
            \RuntimeException::class => 'runtime_error',
            \Error::class => 'system_error',
            default => 'unknown_error',
        };
    }
}
