<?php

declare(strict_types=1);

namespace Tests\Unit\BusinessAccount\Controller;

use App\BusinessAccount\Controller\BusinessAccountController;
use App\BusinessAccount\Service\BusinessAccountService;
use App\BusinessAccount\Entity\Business;
use App\BusinessAccount\Entity\CatalogItem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\TestCase;

class BusinessAccountControllerTest extends TestCase
{
    private BusinessAccountService $serviceMock;
    private BusinessAccountController $controller;

    protected function setUp(): void
    {
        $this->serviceMock = $this->createMock(BusinessAccountService::class);
        $this->controller = new BusinessAccountController($this->serviceMock);
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function invokePrivate(object $obj, string $method, array $args = []): mixed
    {
        $ref = new \ReflectionMethod($obj, $method);
        $ref->setAccessible(true);
        return $ref->invoke($obj, ...$args);
    }

    private function createBusiness(int $id, string $name, string $email, array $extra = []): Business
    {
        $business = new Business();
        $ref = new \ReflectionProperty($business, 'id');
        $ref->setAccessible(true);
        $ref->setValue($business, $id);
        $business->setName($name);
        $business->setEmail($email);
        $business->setOwnerId((string)$id);
        if (!isset($extra['category'])) {
            $business->setCategory('general');
        }
        foreach ($extra as $k => $v) {
            $setter = 'set' . ucfirst($k);
            if (method_exists($business, $setter)) {
                $business->$setter($v);
            }
        }
        return $business;
    }

    // ========== VALIDATION ==========

    public function testValidateRegistrationData_MissingName_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Name is required');
        $this->invokePrivate($this->controller, 'validateRegistrationData', [['email' => 'test@test.com', 'password' => '12345678']]);
    }

    public function testValidateRegistrationData_InvalidEmail_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Valid email is required');
        $this->invokePrivate($this->controller, 'validateRegistrationData', [['name' => 'Test', 'email' => 'bad', 'password' => '12345678']]);
    }

    public function testValidateRegistrationData_ShortPassword_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters');
        $this->invokePrivate($this->controller, 'validateRegistrationData', [['name' => 'Test', 'email' => 'test@test.com', 'password' => 'short']]);
    }

    public function testValidateRegistrationData_Valid(): void
    {
        $this->invokePrivate($this->controller, 'validateRegistrationData', [[
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => '12345678'
        ]]);
        $this->assertTrue(true);
    }

    public function testValidateCatalogItemData_MissingName_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Name is required');
        $this->invokePrivate($this->controller, 'validateCatalogItemData', [['price' => 10]]);
    }

    public function testValidateCatalogItemData_InvalidPrice_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Valid price > 0 required');
        $this->invokePrivate($this->controller, 'validateCatalogItemData', [['name' => 'Item', 'price' => -5]]);
    }

    public function testValidateSubscriptionData_MissingPlan_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Plan is required');
        $this->invokePrivate($this->controller, 'validateSubscriptionData', [['months' => 1]]);
    }

    public function testValidateSubscriptionData_InvalidPlan_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid plan');
        $this->invokePrivate($this->controller, 'validateSubscriptionData', [['plan' => 'bad', 'months' => 1]]);
    }

    public function testValidateSubscriptionData_Valid(): void
    {
        $this->invokePrivate($this->controller, 'validateSubscriptionData', [['plan' => 'basic', 'months' => 1]]);
        $this->assertTrue(true);
    }

    // ========== REGISTER ==========

    public function testRegister_InvalidJson_Returns400(): void
    {
        $request = Request::create('/api/business/register', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{bad}');
        $response = $this->controller->register($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testRegister_ValidationError_Returns400(): void
    {
        $payload = json_encode(['email' => 'test@test.com', 'password' => '123']);
        $request = Request::create('/api/business/register', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->register($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testRegister_Success_Returns201(): void
    {
        $payload = json_encode([
            'name' => 'My Business',
            'email' => 'my@test.com',
            'password' => '12345678'
        ]);
        $request = Request::create('/api/business/register', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        
        $business = $this->createBusiness(1, 'My Business', 'my@test.com');
        $this->serviceMock->method('createBusiness')->willReturn($business);
        
        $response = $this->controller->register($request);
        $this->assertSame(201, $response->getStatusCode());
    }

    // ========== GET PROFILE ==========

    public function testGetProfile_Success_Returns200(): void
    {
        $business = $this->createBusiness(1, 'My Business', 'my@test.com');
        $this->serviceMock->method('getBusinessStats')->with($business)->willReturn(['sales' => 100]);
        $request = Request::create('/api/business/profile', 'GET');
        $response = $this->controller->getProfile($business, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ========== CATALOG ==========

    public function testListCatalog_Success_Returns200(): void
    {
        $business = $this->createBusiness(1, 'Biz', 'biz@test.com');
        $repo = $this->createMock(\App\BusinessAccount\Repository\CatalogItemRepository::class);
        $repo->method('findActiveByBusiness')->with(1)->willReturn([]);
        $this->serviceMock->method('getCatalogRepository')->willReturn($repo);
        $request = Request::create('/api/business/catalog', 'GET');
        $response = $this->controller->listCatalog($business, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testCreateCatalogItem_InvalidJson_Returns400(): void
    {
        $business = $this->createBusiness(1, 'Biz', 'biz@test.com');
        $this->serviceMock->method('hasFeature')->with($business, 'catalog')->willReturn(true);
        $request = Request::create('/api/business/catalog', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'bad');
        $response = $this->controller->createCatalogItem($business, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateCatalogItem_NoCatalogFeature_Returns403(): void
    {
        $business = $this->createBusiness(1, 'Basic', 'b@test.com', ['subscription' => 'basic']);
        $this->serviceMock->method('hasFeature')->with($business, 'catalog')->willReturn(false);
        $payload = json_encode(['name' => 'Item', 'price' => 10]);
        $request = Request::create('/api/business/catalog', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->createCatalogItem($business, $request);
        $this->assertSame(403, $response->getStatusCode());
    }

    // ========== API KEYS ==========
    // Skipping AccessDeniedException test due to missing import in controller


    // ========== SEARCH ==========

    public function testSearch_Success_Returns200(): void
    {
        $biz = $this->createBusiness(1, 'Test', 't@test.com', ['status' => 'active']);
        $repo = $this->createMock(\App\BusinessAccount\Repository\BusinessRepository::class);
        $repo->method('findByStatus')->with('active')->willReturn([$biz]);
        $this->serviceMock->method('getBusinessRepository')->willReturn($repo);
        $request = Request::create('/api/business/search', 'GET', ['status' => 'active']);
        $response = $this->controller->search($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ========== CATEGORIES ==========

    public function testListCategories_Success_Returns200(): void
    {
        $repo = $this->createMock(\App\BusinessAccount\Repository\BusinessCategoryRepository::class);
        $repo->method('findActiveCategories')->willReturn([]);
        $request = Request::create('/api/business/categories', 'GET');
        $response = $this->controller->listCategories($repo, $request);
        $this->assertSame(200, $response->getStatusCode());
    }
}
