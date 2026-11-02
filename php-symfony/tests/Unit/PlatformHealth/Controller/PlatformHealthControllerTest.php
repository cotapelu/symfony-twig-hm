<?php declare(strict_types=1);

namespace App\Tests\Unit\PlatformHealth\Controller;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\PlatformHealth\Controller\PlatformHealthController;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[AllowMockObjectsWithoutExpectations]
class PlatformHealthControllerTest extends TestCase
{
    private CacheInterface&MockObject $cache;
    private PlatformHealthController $controller;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->controller = new PlatformHealthController($this->cache, 30);
        // Initialize container to satisfy AbstractController
        $this->controller->setContainer(new ContainerBuilder());
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(PlatformHealthController::class, $this->controller);
    }

    public function testIamHealthReturns200WhenHealthy(): void
    {
        $this->cache->expects($this->once())
            ->method('get')
            ->with('health_IAM', $this->isCallable())
            ->willReturn([
                'status' => 'healthy',
                'service' => 'IAM',
                'error' => null,
            ]);

        $response = $this->controller->iamHealth();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('healthy', $data['status']);
    }

    public function testIamHealthReturns503WhenUnhealthy(): void
    {
        $this->cache->expects($this->once())
            ->method('get')
            ->with('health_IAM', $this->isCallable())
            ->willReturn([
                'status' => 'unhealthy',
                'service' => 'IAM',
                'error' => 'JWT_SECRET not configured',
            ]);

        $response = $this->controller->iamHealth();
        $this->assertSame(503, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('unhealthy', $data['status']);
    }

    public function testMdmHealthReturns200(): void
    {
        $this->cache->expects($this->once())
            ->method('get')
            ->with('health_MDM', $this->isCallable())
            ->willReturn([
                'status' => 'healthy',
                'service' => 'MDM',
                'error' => null,
            ]);

        $response = $this->controller->mdmHealth();
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testMdmHealthReturns503WhenUnhealthy(): void
    {
        $this->cache->expects($this->once())
            ->method('get')
            ->with('health_MDM', $this->isCallable())
            ->willReturn([
                'status' => 'unhealthy',
                'service' => 'MDM',
                'error' => 'Database connection failed',
            ]);

        $response = $this->controller->mdmHealth();
        $this->assertSame(503, $response->getStatusCode());
    }

    public function testEventingHealth(): void
    {
        $this->cache->expects($this->once())
            ->method('get')
            ->with('health_Eventing', $this->isCallable())
            ->willReturn([
                'status' => 'healthy',
                'service' => 'Eventing',
                'error' => null,
            ]);

        $response = $this->controller->eventingHealth();
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testConfigHealth(): void
    {
        $this->cache->expects($this->once())
            ->method('get')
            ->with('health_Config', $this->isCallable())
            ->willReturn([
                'status' => 'healthy',
                'service' => 'Config',
                'error' => null,
            ]);

        $response = $this->controller->configHealth();
        $this->assertSame(200, $response->getStatusCode());
    }
}
