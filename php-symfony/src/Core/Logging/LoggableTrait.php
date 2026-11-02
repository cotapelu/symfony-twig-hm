<?php

declare(strict_types=1);

namespace App\Core\Logging;

use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;

trait LoggableTrait
{
    private function getCorrelationId(Request $request): string
    {
        return $request->headers->get('X-Correlation-ID', uniqid('', true));
    }

    private function getUserIdFromRequest(Request $request): ?string
    {
        $authHeader = $request->headers->get('Authorization');
        if ($authHeader && preg_match('/^Bearer\s+(\S+)$/', $authHeader, $matches)) {
            return 'user_' . substr(md5($matches[1]), 0, 8);
        }
        return null;
    }

    private function logRequest(string $action, Request $request, array $extra = []): void
    {
        $logEntry = $this->buildRequestLogEntry($action, $request, 'info', $extra);
        error_log(json_encode($logEntry));
    }

    private function logError(string $action, Request $request, \Throwable $e, array $extra = []): void
    {
        $logEntry = $this->buildErrorLogEntry($action, $request, $e, $extra);
        error_log(json_encode($logEntry));
    }

    private function buildRequestLogEntry(string $action, Request $request, string $level, array $extra = []): array
    {
        return [
            'timestamp' => (new DateTimeImmutable())->format('c'),
            'level' => $level,
            'action' => $action,
            'request' => [
                'method' => $request->getMethod(),
                'path' => $request->getPathInfo(),
                'query' => $request->query->all(),
            ],
            'correlation_id' => $this->getCorrelationId($request),
            'user_id' => $this->getUserIdFromRequest($request),
        ] + $extra;
    }

    private function buildErrorLogEntry(string $action, Request $request, \Throwable $e, array $extra = []): array
    {
        return $this->buildRequestLogEntry($action, $request, 'error', $extra) + [
            'error' => [
                'message' => $e->getMessage(),
                'class' => get_class($e),
                'code' => $e->getCode(),
            ],
        ];
    }
}
