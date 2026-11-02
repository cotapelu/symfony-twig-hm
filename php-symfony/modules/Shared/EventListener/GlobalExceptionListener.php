<?php

declare(strict_types=1);

namespace App\Shared\EventListener;

use App\Shared\Exception\ValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION, method: 'onKernelException', priority: 10)]
class GlobalExceptionListener
{
    private const ERROR_MESSAGES = [
        400 => 'Bad Request',
        401 => 'Authentication required',
        403 => 'Access denied',
        404 => 'Resource not found',
        409 => 'Conflict',
        422 => 'Validation failed',
        429 => 'Rate limit exceeded',
        500 => 'Internal server error',
    ];
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Only handle JSON API requests
        if (!$this->isApiRequest($request)) {
            return;
        }

        // Log the exception with context
        $this->logException($exception, $request);

        // Create appropriate response
        $response = $this->createErrorResponse($exception);

        $event->setResponse($response);
    }

    private function isApiRequest($request): bool
    {
        return str_starts_with($request->getPathInfo(), '/api/')
               || 'application/json' === $request->headers->get('Accept')
               || 'application/json' === $request->headers->get('Content-Type');
    }

    private function logException(\Throwable $exception, $request): void
    {
        $context = [
            'url' => $request->getUri(),
            'method' => $request->getMethod(),
            'ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
            'request_id' => $request->headers->get('X-Request-ID', uniqid('req_', true)),
        ];

        if ($exception instanceof HttpExceptionInterface) {
            // HTTP exceptions are expected, log at info level
            $this->logger->info('HTTP Exception', array_merge($context, [
                'status_code' => $exception->getStatusCode(),
                'message' => $exception->getMessage(),
            ]));
        } else {
            // Unexpected exceptions, log at error level
            $this->logger->error('Unhandled Exception', array_merge($context, [
                'exception_class' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]));
        }
    }

    private function createErrorResponse(\Throwable $exception): JsonResponse
    {
        if ($exception instanceof ValidationException) {
            $response = $this->buildValidationResponse($exception);
        } elseif ($exception instanceof HttpExceptionInterface) {
            $response = $this->buildHttpResponse($exception);
        } else {
            $response = $this->buildUnexpectedResponse($exception);
        }
        $this->enrichResponse($response, $exception);
        return new JsonResponse($response, $response['code']);
    }

    private function buildValidationResponse(ValidationException $exception): array
    {
        return [
            'error' => 'Validation failed',
            'code' => 422,
            'details' => $exception->getErrors(),
        ];
    }

    private function buildHttpResponse(HttpExceptionInterface $exception): array
    {
        $statusCode = $exception->getStatusCode();
        $message = $exception->getMessage();
        $errorMessage = self::ERROR_MESSAGES[$statusCode] ?? $message;
        return [
            'error' => $errorMessage,
            'code' => $statusCode,
        ];
    }

    private function buildUnexpectedResponse(\Throwable $exception): array
    {
        $statusCode = 500;
        $errorMessage = 'An unexpected error occurred';
        if (str_contains($exception->getMessage(), 'SQLSTATE')) {
            $errorMessage = 'Database error';
        } elseif (str_contains($exception->getMessage(), 'Constraint violation')) {
            $errorMessage = 'Data validation error';
        }
        return [
            'error' => $errorMessage,
            'code' => $statusCode,
        ];
    }

    private function enrichResponse(array &$response, \Throwable $exception): void
    {
        if (429 === $response['code']) {
            $response['retry_after'] = 60;
        }
        if ('dev' === $_ENV['APP_ENV']) {
            $response['debug'] = [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }
    }
}
