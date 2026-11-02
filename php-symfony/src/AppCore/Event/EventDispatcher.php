<?php

namespace App\AppCore\Event;

use App\AppCore\Event\Event;
use App\AppCore\Event\EventListener;

class EventDispatcher
{
    private array $listeners = [];
    private array $wildcardListeners = [];
    private bool $asyncDispatch = false;
    private int $maxRetries = 3;

    public function __construct(bool $asyncDispatch = false, int $maxRetries = 3)
    {
        $this->asyncDispatch = $asyncDispatch;
        $this->maxRetries = $maxRetries;
    }

    public function addListener(string $eventName, callable $listener, int $priority = 0): string
    {
        $listenerId = uniqid('listener_', true);
        
        if ($eventName === '*') {
            $this->wildcardListeners[$listenerId] = new EventListener($listener, $priority);
        } else {
            if (!isset($this->listeners[$eventName])) {
                $this->listeners[$eventName] = [];
            }
            
            $this->listeners[$eventName][$listenerId] = new EventListener($listener, $priority);
        }

        $this->sortListeners($eventName);
        
        return $listenerId;
    }

    public function removeListener(string $eventName, string $listenerId): bool
    {
        if ($eventName === '*') {
            unset($this->wildcardListeners[$listenerId]);
            return true;
        }

        if (isset($this->listeners[$eventName][$listenerId])) {
            unset($this->listeners[$eventName][$listenerId]);
            return true;
        }

        return false;
    }

    public function dispatch(Event $event): void
    {
        $eventName = $event->getName();
        $errors = [];

        try {
            $this->dispatchToListeners($eventName, $event);
            $this->dispatchToListeners('*', $event);
            
        } catch (\Exception $e) {
            $errors[] = $e;
        }

        if (!empty($errors)) {
            throw new \RuntimeException('One or more listeners failed: ' . implode(', ', array_map(fn($e) => $e->getMessage(), $errors)));
        }
    }

    public function dispatchAsync(Event $event): void
    {
        if (!$this->asyncDispatch) {
            $this->dispatch($event);
            return;
        }

        $this->executeAsync($event);
    }

    public function getListeners(string $eventName): array
    {
        return $this->listeners[$eventName] ?? [];
    }

    public function getWildcardListeners(): array
    {
        return $this->wildcardListeners;
    }

    public function hasListeners(string $eventName): bool
    {
        return !empty($this->listeners[$eventName]) || !empty($this->wildcardListeners);
    }

    public function clearListeners(string $eventName = null): void
    {
        if ($eventName === null) {
            $this->listeners = [];
            $this->wildcardListeners = [];
            return;
        }

        $this->listeners[$eventName] = [];
    }

    private function dispatchToListeners(string $eventName, Event $event): void
    {
        $listeners = $this->listeners[$eventName] ?? [];
        
        foreach ($listeners as $listener) {
            $this->invokeListener($listener, $event);
        }
    }

    private function invokeListener(EventListener $listener, Event $event): void
    {
        $retries = 0;
        
        while ($retries < $this->maxRetries) {
            try {
                $listener($event);
                return;
                
            } catch (\Exception $e) {
                $retries++;
                
                if ($retries >= $this->maxRetries) {
                    throw $e;
                }
                
                usleep(100000);
            }
        }
    }

    private function sortListeners(string $eventName): void
    {
        if (!isset($this->listeners[$eventName])) {
            return;
        }

        uasort($this->listeners[$eventName], function ($a, $b) {
            return $b->getPriority() <=> $a->getPriority();
        });
    }

    private function executeAsync(Event $event): void
    {
        if (function_exists('pcntl_fork')) {
            $pid = pcntl_fork();
            
            if ($pid === -1) {
                throw new \RuntimeException('Failed to fork process');
            } elseif ($pid === 0) {
                $this->dispatch($event);
                exit(0);
            }
        } else {
            throw new \RuntimeException('Async dispatch requires pcntl extension');
        }
    }
}