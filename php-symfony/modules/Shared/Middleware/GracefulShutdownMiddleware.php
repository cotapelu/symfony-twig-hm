<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Psr\Log\LoggerInterface;

class GracefulShutdownMiddleware implements EventSubscriberInterface
{
    private bool $shuttingDown = false;
    private bool $shouldExit = false;

    public function __construct(
        private LoggerInterface $logger
    ) {
        $this->registerSignalHandlers();
    }

    private function registerSignalHandlers(): void
    {
        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);

            pcntl_signal(SIGTERM, function () {
                $this->logger->info('Received SIGTERM, initiating graceful shutdown...');
                $this->shuttingDown = true;
                $this->shouldExit = true;
            });

            pcntl_signal(SIGINT, function () {
                $this->logger->info('Received SIGINT, initiating graceful shutdown...');
                $this->shuttingDown = true;
                $this->shouldExit = true;
            });
        }
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        if ($this->shouldExit) {
            $this->logger->info('Graceful shutdown complete, exiting...');
            
            $this->flushConnections();
            $this->flushCache();
            
            exit(0);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => ['onKernelTerminate', -10],
        ];
    }

    public function isShuttingDown(): bool
    {
        return $this->shuttingDown;
    }

    private function flushConnections(): void
    {
        $this->logger->info('Flushing database connections...');
        try {
            $connection = $this->getEntityManagerConnection();
            if ($connection && method_exists($connection, 'close')) {
                $connection->close();
            }
        } catch (\Exception $e) {
            $this->logger->error('Error flushing database connections', [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function getEntityManagerConnection()
    {
        $container = $GLOBALS['kernel'] ?? null;
        if (!$container || !method_exists($container, 'getContainer')) {
            return null;
        }
        $doctrine = $container->getContainer()->get('doctrine');
        if (!$doctrine || !method_exists($doctrine, 'getManager')) {
            return null;
        }
        $entityManager = $doctrine->getManager();
        if (!$entityManager || !method_exists($entityManager, 'getConnection')) {
            return null;
        }
        return $entityManager->getConnection();
    }

    private function flushCache(): void
    {
        $this->logger->info('Flushing cache...');
        
        try {
            $container = $GLOBALS['kernel'] ?? null;
            if ($container && method_exists($container, 'getContainer')) {
                $cache = $container->getContainer()->get('cache.app');
                if ($cache && method_exists($cache, 'clear')) {
                    $cache->clear();
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error flushing cache', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
