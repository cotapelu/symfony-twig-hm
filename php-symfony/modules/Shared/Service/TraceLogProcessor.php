<?php

declare(strict_types=1);

namespace App\Shared\Service;

use App\Shared\Middleware\TracingMiddleware;
use Monolog\LogRecord;

final class TraceLogProcessor
{
    public function __invoke(array|LogRecord $record)
    {
        $traceId = TracingMiddleware::getCurrentTraceId();
        if ($traceId) {
            if ($record instanceof LogRecord) {
                $record->extra['trace_id'] = $traceId;
            } else {
                $record['extra']['trace_id'] = $traceId;
            }
        }
        return $record;
    }
}
