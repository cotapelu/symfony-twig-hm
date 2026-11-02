<?php

declare(strict_types=1);

namespace App\AppCore;

use Psr\Log\LoggerInterface;
use App\AppCore\Event\EventDispatcher;
use App\AppCore\Transaction\TransactionManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Bootstrap
{
    private ContainerInterface $container;
    private LoggerInterface $logger;
    private ?ModuleRegistry $moduleRegistry = null;
    private ?EventDispatcher $eventDispatcher = null;
    private ?TransactionManager $transactionManager = null;

    public function __construct(
        ContainerInterface $container,
        LoggerInterface $logger
    ) {
        $this->container = $container;
        $this->logger = $logger;
    }

    public function bootstrap(): void
    {
        $this->logger->info('[INFO] AppCore.Bootstrap.bootstrap - Starting application bootstrap...');

        try {
            $this->executeBootstrapSequence();
            $this->logger->info('[INFO] AppCore.Bootstrap.bootstrap - Application bootstrap completed successfully');
        } catch (\Exception $e) {
            $this->logger->error('[ERROR] AppCore.Bootstrap.bootstrap - Application bootstrap failed: ' . $e->getMessage() . ' - Check container configuration and module dependencies', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'component' => 'AppCore.Bootstrap',
                'action' => 'bootstrap'
            ]);
            throw $e;
        }
    }

    private function executeBootstrapSequence(): void
    {
        $this->initializeCore();
        $this->initializePlatformServices();
        $this->initializeModules();
        $this->initializeEventing();
        $this->initializeTransactions();
        $this->registerFacades();
        $this->fireBootstrapEvents();
    }

    private function initializeCore(): void
    {
        $this->moduleRegistry = $this->container->get(ModuleRegistry::class);
        $this->eventDispatcher = $this->container->get(EventDispatcher::class);
        $this->transactionManager = $this->container->get(TransactionManager::class);
        $this->logger->info('[INFO] AppCore.Bootstrap.initializeCore - Core services initialized');
    }

    private function initializePlatformServices(): void
    {
        $iam = $this->container->get(\Platform\IAM\IAMService::class);
        $mdm = $this->container->get(\Platform\MDM\ReferenceDataService::class);
        $broker = $this->container->get(\Platform\Eventing\Broker\MessageBrokerInterface::class);
        $outbox = $this->container->get(\Platform\Eventing\Outbox\OutboxProcessor::class);
        $config = $this->container->get(\Platform\Config\ConfigurationManager::class);

        $this->container->set('platform.iam', $iam);
        $this->container->set('platform.mdm', $mdm);
        $this->container->set('platform.eventing.broker', $broker);
        $this->container->set('platform.eventing.outbox', $outbox);
        $this->container->set('platform.config', $config);

        $this->logger->info('[INFO] AppCore.Bootstrap.initializePlatformServices - Platform services initialized and registered');
    }

    private function initializeModules(): void
    {
        $this->discoverModules();
        $this->loadModulesInOrder();
    }

    private function discoverModules(): void
    {
        foreach ($this->getModulePaths() as $moduleName => $path) {
            try {
                $this->moduleRegistry->discover($moduleName, $path);
                $this->logger->debug('[DEBUG] AppCore.Bootstrap.discoverModules - Module discovered: ' . $moduleName, ['module' => $moduleName, 'path' => $path]);
            } catch (\Exception $e) {
                $this->logger->warning('[WARNING] AppCore.Bootstrap.discoverModules - Failed to discover module: ' . $moduleName, ['error' => $e->getMessage(), 'module' => $moduleName, 'path' => $path]);
            }
        }
    }

    private function loadModulesInOrder(): void
    {
        foreach ($this->moduleRegistry->getLoadOrder() as $moduleName) {
            try {
                $this->moduleRegistry->load($moduleName);
                $this->logger->debug('[DEBUG] AppCore.Bootstrap.loadModulesInOrder - Module loaded: ' . $moduleName, ['module' => $moduleName]);
            } catch (\Exception $e) {
                $this->logger->error('[ERROR] AppCore.Bootstrap.loadModulesInOrder - Failed to load module: ' . $moduleName, ['error' => $e->getMessage(), 'module' => $moduleName]);
                throw $e;
            }
        }
        $this->logger->info('[INFO] AppCore.Bootstrap.loadModulesInOrder - All modules loaded successfully');
    }

    private function initializeEventing(): void
    {
        $this->logger->info('[INFO] AppCore.Bootstrap.initializeEventing - Event system initialized');
    }

    private function initializeTransactions(): void
    {
        $this->logger->info('[INFO] AppCore.Bootstrap.initializeTransactions - Transaction system initialized');
    }

    private function registerFacades(): void
    {
        Facade::setContainer($this->container);
        $this->logger->info('[INFO] AppCore.Bootstrap.registerFacades - Facades registered');
    }

    private function fireBootstrapEvents(): void
    {
        if ($this->moduleRegistry === null) {
            throw new \RuntimeException('Module registry not initialized');
        }
        
        $moduleNames = array_keys($this->moduleRegistry->getLoadedModules());
        
        $this->eventDispatcher->dispatch(new \App\AppCore\Event\DomainEvent('app.bootstrap.started', ['timestamp' => time(), 'modules' => $moduleNames]));
        $this->eventDispatcher->dispatch(new \App\AppCore\Event\DomainEvent('app.bootstrap.completed', ['timestamp' => time(), 'modules_loaded' => count($moduleNames)]));
        
        $this->logger->info('[INFO] AppCore.Bootstrap.fireBootstrapEvents - Bootstrap events fired');
    }

    private function getModulePaths(): array
    {
        $config = $this->container->get('config');
        return $config['modules']['paths'] ?? [
            'users' => __DIR__ . '/../Module/Users',
            'billing' => __DIR__ . '/../Module/Billing',
            'orders' => __DIR__ . '/../Module/Orders',
            'products' => __DIR__ . '/../Module/Products',
        ];
    }

    public function shutdown(): void
    {
        $this->logger->info('[INFO] AppCore.Bootstrap.shutdown - Starting application shutdown...');

        try {
            if ($this->eventDispatcher === null) {
                throw new \RuntimeException('Event dispatcher not initialized');
            }
            $this->dispatchShutdownEvent();
            Facade::clearInstances();
            $this->logger->info('[INFO] AppCore.Bootstrap.shutdown - Application shutdown completed');
        } catch (\Exception $e) {
            $this->logger->error('[ERROR] AppCore.Bootstrap.shutdown - Application shutdown failed: ' . $e->getMessage() . ' - Check facade instances and event dispatcher', [
                'error' => $e->getMessage(),
                'component' => 'AppCore.Bootstrap',
                'action' => 'shutdown'
            ]);
        }
    }

    private function dispatchShutdownEvent(): void
    {
        $this->eventDispatcher->dispatch(new \App\AppCore\Event\DomainEvent('app.shutdown.started', ['timestamp' => time()]));
    }

    public function getModuleRegistry(): ModuleRegistry
    {
        if ($this->moduleRegistry === null) {
            throw new \RuntimeException('Module registry not initialized');
        }
        return $this->moduleRegistry;
    }

    public function getEventDispatcher(): EventDispatcher
    {
        if ($this->eventDispatcher === null) {
            throw new \RuntimeException('Event dispatcher not initialized');
        }
        return $this->eventDispatcher;
    }

    public function getTransactionManager(): TransactionManager
    {
        if ($this->transactionManager === null) {
            throw new \RuntimeException('Transaction manager not initialized');
        }
        return $this->transactionManager;
    }
}
