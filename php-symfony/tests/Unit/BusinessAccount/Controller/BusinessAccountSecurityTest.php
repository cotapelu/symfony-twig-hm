<?php

declare(strict_types=1);

namespace App\Tests\Unit\BusinessAccount\Controller;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

// Load BusinessAccount entities (combined file) to make Business class available
require_once __DIR__.'/../../../../modules/BusinessAccount/Entity/BusinessAccountEntities.php';

use PHPUnit\Framework\TestCase;
use App\BusinessAccount\Controller\BusinessAccountController;
use App\BusinessAccount\Entity\Business;
use App\BusinessAccount\Service\BusinessAccountService;
use App\BusinessAccount\Entity\ApiKey;
use App\BusinessAccount\Entity\CatalogItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;

#[AllowMockObjectsWithoutExpectations]
class BusinessAccountSecurityTest extends TestCase
{
    private BusinessAccountController $controller;
    private BusinessAccountService $service;

    protected function setUp(): void
    {
        $this->service = $this->createMock(BusinessAccountService::class);
        
        $reflection = new ReflectionClass(BusinessAccountController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        
        // Set service property
        $prop = $reflection->getProperty('service');
        $prop->setAccessible(true);
        $prop->setValue($controller, $this->service);
        
        // Set container to avoid AbstractController errors
        $container = $this->createMock(ContainerInterface::class);
        $containerProp = $reflection->getProperty('container');
        $containerProp->setAccessible(true);
        $containerProp->setValue($controller, $container);
        
        $this->controller = $controller;
    }

    /**
     * @group IDOR
     */
    public function testVerifyBusinessThrowsWhenDifferentOwner(): void
    {
        $currentBusiness = $this->createMockBusiness('1');
        $reflection = new ReflectionMethod($this->controller, 'verifyBusiness');
        $reflection->setAccessible(true);
        
        $response = $reflection->invoke($this->controller, $currentBusiness, 2);
        
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Cannot verify business you do not own', $data['error']);
    }

    /**
     * @group IDOR
     */
    public function testApproveBusinessThrowsWhenDifferentOwner(): void
    {
        $currentBusiness = $this->createMockBusiness('1');
        $reflection = new ReflectionMethod($this->controller, 'approveBusiness');
        $reflection->setAccessible(true);
        
        $response = $reflection->invoke($this->controller, $currentBusiness, 2);
        
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Cannot approve business you do not own', $data['error']);
    }

    /**
     * @group IDOR
     */
    public function testSuspendBusinessThrowsWhenDifferentOwner(): void
    {
        $currentBusiness = $this->createMockBusiness('1');
        $reflection = new ReflectionMethod($this->controller, 'suspendBusiness');
        $reflection->setAccessible(true);
        
        $response = $reflection->invoke($this->controller, $currentBusiness, 2);
        
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Cannot suspend business you do not own', $data['error']);
    }

    /**
     * @group INPUT_VALIDATION
     */
    public function testValidateRegistrationDataRejectsInvalidUrl(): void
    {
        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('validateRegistrationData');
        $method->setAccessible(true);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid website URL');
        
        $data = [
            'name' => 'Test Business',
            'email' => 'test@example.com',
            'password' => 'secret123',
            'website' => 'not-a-url'
        ];
        $method->invoke($this->controller, $data);
    }

    /**
     * @group INPUT_VALIDATION
     */
    public function testValidateRegistrationDataRejectsInvalidLatitude(): void
    {
        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('validateRegistrationData');
        $method->setAccessible(true);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Latitude must be between -90 and 90');
        
        $data = [
            'name' => 'Test Business',
            'email' => 'test@example.com',
            'password' => 'secret123',
            'latitude' => 100
        ];
        $method->invoke($this->controller, $data);
    }

    /**
     * @group INPUT_VALIDATION
     */
    public function testValidateCatalogItemDataRejectsInvalidImageUrl(): void
    {
        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('validateCatalogItemData');
        $method->setAccessible(true);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid image URL');
        
        $data = [
            'name' => 'Product',
            'price' => 100,
            'imageUrl' => 'invalid'
        ];
        $method->invoke($this->controller, $data);
    }

    private function createMockBusiness(string $id): Business
    {
        $business = new Business();
        $reflection = new \ReflectionClass($business);
        $prop = $reflection->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($business, (int)$id);
        return $business;
    }
}
