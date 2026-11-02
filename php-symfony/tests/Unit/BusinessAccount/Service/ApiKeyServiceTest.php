<?php declare(strict_types=1);

namespace App\Tests\Unit\BusinessAccount\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\BusinessAccount\Entity\ApiKey;
use App\BusinessAccount\Entity\Business;
use App\BusinessAccount\Repository\ApiKeyRepository;
use App\BusinessAccount\Service\ApiKeyService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ApiKeyServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private ApiKeyRepository&MockObject $apiKeyRepo;
    private ApiKeyService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->apiKeyRepo = $this->createMock(ApiKeyRepository::class);
        $this->em->method('getRepository')->with('App\BusinessAccount\Entity\ApiKey')->willReturn($this->apiKeyRepo);
        $this->service = new ApiKeyService($this->em);
    }

    public function testGenerateApiKey(): void
    {
        $business = $this->createMock(Business::class);
        $business->method('getId')->willReturn(123);
        $name = 'Test API Key';

        // When name is provided, findByBusiness is not called (null coalesce short-circuits)
        $this->apiKeyRepo->expects($this->never())->method('findByBusiness');
        $this->apiKeyRepo->expects($this->once())->method('revokeOldKeys')->with(123, 2);
        $this->em->expects($this->once())->method('persist')->with($this->callback(function($arg) use ($business, $name) {
            $this->assertInstanceOf(ApiKey::class, $arg);
            $this->assertSame($business, $arg->getBusiness());
            $this->assertSame($name, $arg->getName());
            $this->assertIsString($arg->getKey());
            $this->assertTrue($arg->isActive());
            return true;
        }));
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->generateApiKey($business, $name);
        $this->assertInstanceOf(ApiKey::class, $result);
        $this->assertSame($business, $result->getBusiness());
        $this->assertSame($name, $result->getName());
    }

    public function testGenerateApiKeyWithDefaultName(): void
    {
        $business = $this->createMock(Business::class);
        $business->method('getId')->willReturn(123);
        // Existing keys count = 2
        $this->apiKeyRepo->expects($this->once())->method('revokeOldKeys')->with(123, 2);
        $this->apiKeyRepo->expects($this->once())->method('findByBusiness')->with(123)->willReturn([new ApiKey(), new ApiKey()]);
        $this->em->expects($this->once())->method('persist')->with($this->callback(fn($arg) => $arg->getName() === 'API Key 3'));
        $this->em->expects($this->once())->method('flush');
        $this->service->generateApiKey($business, null);
    }

    public function testRevokeApiKey(): void
    {
        $apiKey = $this->createMock(ApiKey::class);
        $apiKey->expects($this->once())->method('setIsActive')->with(false);
        $this->em->expects($this->once())->method('flush');
        $result = $this->service->revokeApiKey($apiKey);
        $this->assertSame($apiKey, $result);
    }

    public function testValidateApiKeyWhenValid(): void
    {
        $key = 'valid-key';
        $business = $this->createMock(Business::class);
        $apiKey = $this->createMock(ApiKey::class);
        $apiKey->method('getBusiness')->willReturn($business);
        $this->apiKeyRepo->expects($this->once())->method('findByKey')->with($key)->willReturn($apiKey);
        $result = $this->service->validateApiKey($key);
        $this->assertSame($business, $result);
    }

    public function testValidateApiKeyWhenInvalid(): void
    {
        $key = 'invalid-key';
        $this->apiKeyRepo->expects($this->once())->method('findByKey')->with($key)->willReturn(null);
        $result = $this->service->validateApiKey($key);
        $this->assertNull($result);
    }

    public function testGetActiveKeys(): void
    {
        $businessId = 123;
        $business = $this->createMock(Business::class);
        $business->method('getId')->willReturn($businessId);
        $keys = [new ApiKey(), new ApiKey()];
        $this->apiKeyRepo->expects($this->once())->method('findActiveByBusiness')->with($businessId)->willReturn($keys);
        $result = $this->service->getActiveKeys($business);
        $this->assertSame($keys, $result);
    }
}
