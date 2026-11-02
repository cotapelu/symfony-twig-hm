<?php declare(strict_types=1);

namespace App\Tests\Unit\BusinessAccounts\Controller;

use App\BusinessAccounts\Controller\BusinessAccountsController;
use App\BusinessAccounts\Entity\BusinessAccount;
use App\BusinessAccounts\Service\BusinessAccountsService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers \App\BusinessAccounts\Controller\BusinessAccountsController
 */
class BusinessAccountsControllerTest extends TestCase
{
    private BusinessAccountsController $controller;
    private BusinessAccountsService $service;

    protected function setUp(): void
    {
        $this->service = $this->createMock(BusinessAccountsService::class);
        // Use Reflection to inject the mock into the constructor
        $reflection = new \ReflectionClass(BusinessAccountsController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $reflectionProperty = $reflection->getProperty('service');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($controller, $this->service);
        $this->controller = $controller;
    }

    // ==================== Create Tests ====================

    public function testCreateAccountSuccess(): void
    {
        $account = new BusinessAccount(123, 'My Business', 'retail');
        $account->setId(1);
        $this->service->expects($this->once())
            ->method('createAccount')
            ->with(123, 'My Business', 'retail')
            ->willReturn($account);

        $request = [
            'user_id' => 123,
            'body' => [
                'business_name' => 'My Business',
                'business_type' => 'retail'
            ]
        ];

        $result = $this->controller->createAccount($request);

        $this->assertEquals(201, $result['status']);
        $this->assertInstanceOf(BusinessAccount::class, $result['data']);
    }

    public function testCreateAccountWithAllFields(): void
    {
        $account = new BusinessAccount(123, 'My Business', 'retail');
        $account->setId(1);
        $this->service->expects($this->once())
            ->method('createAccount')
            ->with(123, 'My Business', 'retail')
            ->willReturn($account);

        $request = [
            'user_id' => 123,
            'body' => [
                'business_name' => 'My Business',
                'business_type' => 'retail',
                'tax_id' => '123-456-789',
                'address' => '123 Main St',
                'phone' => '+849012345678',
                'email' => 'contact@business.com'
            ]
        ];

        $result = $this->controller->createAccount($request);

        $this->assertEquals(201, $result['status']);
    }

    // ==================== Read Tests ====================

    public function testGetUserAccounts(): void
    {
        $account = new BusinessAccount(123, 'My Business', 'retail');
        $account->setId(1);
        $this->service->expects($this->once())
            ->method('getUserAccounts')
            ->with(123)
            ->willReturn([$account]);

        $request = ['user_id' => 123];
        $result = $this->controller->getUserAccounts($request);

        $this->assertEquals(200, $result['status']);
        $this->assertCount(1, $result['data']);
    }

    public function testGetAccountFound(): void
    {
        $account = new BusinessAccount(123, 'My Business', 'retail');
        $account->setId(1);
        $this->service->expects($this->once())
            ->method('getAccount')
            ->with(1)
            ->willReturn($account);

        $request = ['params' => ['id' => 1]];
        $result = $this->controller->getAccount($request);

        $this->assertEquals(200, $result['status']);
        $this->assertSame($account, $result['data']);
    }

    public function testGetAccountNotFound(): void
    {
        $this->service->expects($this->once())
            ->method('getAccount')
            ->with(999)
            ->willReturn(null);

        $request = ['params' => ['id' => 999]];
        $result = $this->controller->getAccount($request);

        $this->assertEquals(404, $result['status']);
        $this->assertEquals('Account not found', $result['error']);
    }

    // ==================== Update Tests ====================

    public function testUpdateAccount(): void
    {
        $account = new BusinessAccount(123, 'My Business', 'retail');
        $account->setId(1);
        // getAccount is called twice: once for existence check, once for return
        $this->service->expects($this->exactly(2))
            ->method('getAccount')
            ->with(1)
            ->willReturn($account);
        $this->service->expects($this->once())
            ->method('updateContact')
            ->with(1, '+849012345678', 'contact@business.com')
            ->willReturn($account);

        $request = [
            'params' => ['id' => 1],
            'body' => [
                'phone' => '+849012345678',
                'email' => 'contact@business.com'
            ]
        ];

        $result = $this->controller->updateAccount($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testUpdateAccountNotFound(): void
    {
        $this->service->expects($this->once())
            ->method('getAccount')
            ->with(999)
            ->willReturn(null);

        $request = [
            'params' => ['id' => 999],
            'body' => ['phone' => '+849012345678']
        ];

        $result = $this->controller->updateAccount($request);

        $this->assertEquals(404, $result['status']);
        $this->assertEquals('Account not found', $result['error']);
    }

    // ==================== Delete Tests ====================

    public function testDeleteAccountSuccess(): void
    {
        $this->service->expects($this->once())
            ->method('deleteAccount')
            ->with(1)
            ->willReturn(true);

        $request = ['params' => ['id' => 1]];
        $result = $this->controller->deleteAccount($request);

        $this->assertEquals(204, $result['status']);
    }

    public function testDeleteAccountNotFound(): void
    {
        $this->service->expects($this->once())
            ->method('deleteAccount')
            ->with(999)
            ->willReturn(false);

        $request = ['params' => ['id' => 999]];
        $result = $this->controller->deleteAccount($request);

        $this->assertEquals(404, $result['status']);
        $this->assertEquals('Account not found', $result['error']);
    }

    // ==================== Search Tests ====================

    public function testSearchByName(): void
    {
        $account = new BusinessAccount(123, 'My Business', 'retail');
        $account->setId(1);
        $this->service->expects($this->once())
            ->method('searchByName')
            ->with('My')
            ->willReturn([$account]);

        $request = ['query' => ['name' => 'My']];
        $result = $this->controller->searchByName($request);

        $this->assertEquals(200, $result['status']);
        $this->assertCount(1, $result['data']);
    }

    public function testSearchByNameEmpty(): void
    {
        $this->service->expects($this->once())
            ->method('searchByName')
            ->with('')
            ->willReturn([]);

        $request = ['query' => ['name' => '']];
        $result = $this->controller->searchByName($request);

        $this->assertEquals(200, $result['status']);
        $this->assertCount(0, $result['data']);
    }
}
