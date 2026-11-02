<?php

declare(strict_types=1);

namespace App\Shared\Controller;

use App\Shared\Middleware\MetricsMiddleware;
use Doctrine\ORM\EntityManagerInterface;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MetricsController extends BaseController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route('/metrics', name: 'metrics', methods: ['GET'])]
    public function index(): Response
    {
        $metrics = MetricsMiddleware::getMetrics();
        $histogram = MetricsMiddleware::getDurationHistogram();

        $output = '';
        $output .= $this->renderCounter('http_requests_total', 'Total number of HTTP requests', $metrics['http_requests_total'] ?? 0);
        $output .= $this->renderCounter('database_queries_total', 'Total number of database queries', $metrics['database_queries_total'] ?? 0);
        $output .= $this->renderCounter('cache_hits_total', 'Total number of cache hits', $metrics['cache_hits_total'] ?? 0);
        $output .= $this->renderCounter('cache_misses_total', 'Total number of cache misses', $metrics['cache_misses_total'] ?? 0);
        $output .= $this->renderCounter('errors_total', 'Total number of errors', $metrics['errors_total'] ?? 0);
        $output .= $this->renderCounter('slow_queries_total', 'Total number of slow database queries (>100ms)', $metrics['slow_queries_total'] ?? 0);
        $output .= $this->renderHistogram('http_request_duration_ms', 'Request duration in milliseconds', $histogram);
        $output .= $this->renderGauge('messenger_queue_depth', 'Number of pending asynchronous messenger messages', $this->getQueueDepth());
        $output .= $this->renderGauge('active_users', 'Number of currently active users (placeholder)', 0);

        return new Response($output, 200, ['Content-Type' => 'text/plain; version=0.0.4']);
    }

    private function renderCounter(string $name, string $help, int $value): string
    {
        return "# HELP {$name} {$help}\n# TYPE {$name} counter\n{$name} {$value}\n";
    }

    private function renderHistogram(string $name, string $help, array $histogram): string
    {
        $out = "# HELP {$name} {$help}\n# TYPE {$name} histogram\n";
        foreach ($histogram['le_order'] as $le) {
            $count = $histogram['buckets'][$le] ?? 0;
            $out .= sprintf("{$name}_bucket{le=\"%d\"} %d\n", $le, $count);
        }
        $out .= sprintf("{$name}_bucket{le=\"+Inf\"} %d\n", $histogram['count']);
        $out .= sprintf("{$name}_sum %f\n", $histogram['sum']);
        $out .= sprintf("{$name}_count %d\n", $histogram['count']);
        return $out;
    }

    private function renderGauge(string $name, string $help, int $value): string
    {
        return "# HELP {$name} {$help}\n# TYPE {$name} gauge\n{$name} {$value}\n";
    }

    private function getQueueDepth(): int
    {
        try {
            return (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM messenger_messages WHERE status = ?', ['queued']);
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
