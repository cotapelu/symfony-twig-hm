<?php

declare(strict_types=1);

namespace Platform\Eventing\Broker;

interface MessageBrokerInterface
{
    public function publish(string $topic, array $message): void;

    public function subscribe(string $topic, callable $handler): string;

    public function unsubscribe(string $subscriptionId): void;
}