<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\HealthCheckController;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class HealthCheckControllerTest extends TestCase
{
    #[Test]
    public function live_always_returns_200(): void
    {
        $controller = new HealthCheckController($this->createMock(EntityManagerInterface::class));
        $response = $controller->live();

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('alive', $data['status']);
        $this->assertArrayHasKey('timestamp', $data);
    }

    #[Test]
    public function health_returns_healthy_when_database_ok(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $connection->method('fetchOne')->with('SELECT 1')->willReturn('1');
        $em->method('getConnection')->willReturn($connection);

        $controller = new HealthCheckController($em);
        $response = $controller->health();

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('healthy', $data['status']);
        $this->assertSame('ok', $data['components']['database']);
    }

    #[Test]
    public function health_returns_unhealthy_when_database_fails(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $connection->method('fetchOne')->with('SELECT 1')->willThrowException(new \RuntimeException('DB down'));
        $em->method('getConnection')->willReturn($connection);

        $controller = new HealthCheckController($em);
        $response = $controller->health();

        $this->assertSame(503, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('unhealthy', $data['status']);
        $this->assertSame('error', $data['components']['database']);
    }

    #[Test]
    public function ready_returns_ready_when_all_components_ok(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $connection->method('fetchOne')->with('SELECT 1')->willReturn('1');
        $em->method('getConnection')->willReturn($connection);

        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturn('ok');

        $bus = $this->createMock(MessageBusInterface::class);

        $controller = new HealthCheckController($em, $cache, $bus);
        $response = $controller->ready();

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('ready', $data['status']);
    }

    #[Test]
    public function ready_returns_not_ready_when_cache_fails(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $connection->method('fetchOne')->with('SELECT 1')->willReturn('1');
        $em->method('getConnection')->willReturn($connection);

        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willThrowException(new \RuntimeException('Cache down'));

        $controller = new HealthCheckController($em, $cache);
        $response = $controller->ready();

        $this->assertSame(503, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('not ready', $data['status']);
        $this->assertSame('error', $data['components']['cache']);
    }
}
