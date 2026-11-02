<?php declare(strict_types=1);

namespace App\Tests\Unit\BusinessAccounts\Service;

use App\BusinessAccounts\Entity\BusinessAccount;
use App\BusinessAccounts\Repository\BusinessAccountRepositoryInterface;
use App\BusinessAccounts\Service\BusinessAccountsService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\BusinessAccounts\Service\BusinessAccountsService
 */
class BusinessAccountsServiceTest extends TestCase
{
    private BusinessAccountsService $service;
    private BusinessAccountRepositoryInterface $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(BusinessAccountRepositoryInterface::class);
        $this->service = new BusinessAccountsService($this->repository);
    }

    // ==================== Create Tests ====================

    public function testCreateAccountSuccess(): void
    {
        $account = new BusinessAccount(123, 'My Business', 'retail');
        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($a) {
                return $a instanceof BusinessAccount
                    && $a->getUserId() === 123
                    && $a->getBusinessName() === 'My Business'
                    && $a->getBusinessType() === 'retail';
            }))
            ->willReturnCallback(function (BusinessAccount $a) {
                $a->setId(1);
                return $a;
            });

        $result = $this->service->createAccount(123, 'My Business', 'retail');

        $this->assertInstanceOf(BusinessAccount::class, $result);
        $this->assertEquals(1, $result->getId());
        $this->assertEquals(123, $result->getUserId());
    }

    // ==================== Read Tests ====================

    public function testGetAccountFound(): void
    {
        $account = new BusinessAccount(123, 'My Business', 'retail');
        $account->setId(1);
        $this->repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($account);

        $result = $this->service->getAccount(1);

        $this->assertSame($account, $result);
    }

    public function testGetAccountNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->getAccount(999);

        $this->assertNull($result);
    }

    public function testGetUserAccounts(): void
    {
        $account1 = new BusinessAccount(123, 'Business 1', 'retail');
        $account1->setId(1);
        $account2 = new BusinessAccount(123, 'Business 2', 'service');
        $account2->setId(2);
        $this->repository->expects($this->once())
            ->method('findByUserId')
            ->with(123)
            ->willReturn([$account1, $account2]);

        $result = $this->service->getUserAccounts(123);

        $this->assertCount(2, $result);
        $this->assertContains($account1, $result);
        $this->assertContains($account2, $result);
    }

    // ==================== Update Tests ====================

    public function testUpdateTaxId(): void
    {
        $account = new BusinessAccount(123, 'My Business', 'retail');
        $account->setId(1);
        $this->repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($account);
        $this->repository->expects($this->once())
            ->method('save')
            ->willReturn($account);

        $result = $this->service->updateTaxId(1, '123-456-789');

        $this->assertSame($account, $result);
        $this->assertEquals('123-456-789', $account->getTaxId());
    }

    public function testUpdateTaxIdAccountNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->updateTaxId(999, '123-456-789');

        $this->assertNull($result);
    }

    public function testUpdateContact(): void
    {
        $account = new BusinessAccount(123, 'My Business', 'retail');
        $account->setId(1);
        $this->repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($account);
        $this->repository->expects($this->once())
            ->method('save')
            ->willReturn($account);

        $result = $this->service->updateContact(1, '+849012345678', 'contact@business.com');

        $this->assertSame($account, $result);
        $this->assertEquals('+849012345678', $account->getPhone());
        $this->assertEquals('contact@business.com', $account->getEmail());
    }

    public function testVerifyAccount(): void
    {
        $account = new BusinessAccount(123, 'My Business', 'retail');
        $account->setId(1);
        $this->assertFalse($account->isVerified());
        $this->repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($account);
        $this->repository->expects($this->once())
            ->method('save')
            ->willReturn($account);

        $result = $this->service->verifyAccount(1);

        $this->assertSame($account, $result);
        $this->assertTrue($account->isVerified());
    }

    // ==================== Delete Tests ====================

    public function testDeleteAccountSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('delete')
            ->with(1)
            ->willReturn(true);

        $result = $this->service->deleteAccount(1);

        $this->assertTrue($result);
    }

    public function testDeleteAccountNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('delete')
            ->with(999)
            ->willReturn(false);

        $result = $this->service->deleteAccount(999);

        $this->assertFalse($result);
    }

    // ==================== Search Tests ====================

    public function testSearchByName(): void
    {
        $account1 = new BusinessAccount(123, 'My Business', 'retail');
        $account1->setId(1);
        $account2 = new BusinessAccount(456, 'My Store', 'retail');
        $account2->setId(2);
        // Simulate search by returning specific results
        $this->repository->method('searchByName')->willReturn([$account1, $account2]);

        $result = $this->service->searchByName('My');

        $this->assertCount(2, $result);
    }

    public function testSearchByNameCaseInsensitive(): void
    {
        $account = new BusinessAccount(123, 'MY BUSINESS', 'retail');
        $account->setId(1);
        $this->repository->method('searchByName')->willReturn([$account]);

        $result = $this->service->searchByName('business');

        $this->assertCount(1, $result);
    }

    public function testGetVerifiedAccounts(): void
    {
        $verified = new BusinessAccount(123, 'Verified', 'retail');
        $verified->setId(1);
        $verified->setVerified(true);
        $unverified = new BusinessAccount(456, 'Not Verified', 'service');
        $unverified->setId(2);
        $this->repository->expects($this->once())
            ->method('findVerified')
            ->willReturn([$verified]);

        $result = $this->service->getVerifiedAccounts();

        $this->assertCount(1, $result);
        $this->assertSame($verified, $result[0]);
    }

    public function testGetAccountsByType(): void
    {
        $retail = new BusinessAccount(123, 'Shop', 'retail');
        $retail->setId(1);
        $service = new BusinessAccount(456, 'Service', 'service');
        $service->setId(2);
        $this->repository->expects($this->once())
            ->method('findByBusinessType')
            ->with('retail')
            ->willReturn([$retail]);

        $result = $this->service->getAccountsByType('retail');

        $this->assertCount(1, $result);
        $this->assertSame($retail, $result[0]);
    }
}
