<?php

declare(strict_types=1);

namespace App\Tests\BusinessAccounts\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use PHPUnit\Framework\TestCase;
use App\BusinessAccounts\Service\BusinessAccountsService;
use App\BusinessAccounts\Entity\BusinessAccount;
use App\BusinessAccounts\Repository\BusinessAccountRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\AuthorizationException;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class BusinessAccountsServiceTest extends TestCase
{
    private BusinessAccountRepositoryInterface $repo;
    private BusinessAccountsService $service;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(BusinessAccountRepositoryInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $this->service = new BusinessAccountsService($this->repo, $logger);
    }

    public function testCreateAccount(): void
    {
        $userId = 42;
        $name = 'Test Business';
        $type = 'company';

        $account = new BusinessAccount($userId, $name, $type);
        $this->repo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (BusinessAccount $a) use ($userId, $name, $type) {
                return $a->getUserId() === $userId
                    && $a->getBusinessName() === $name
                    && $a->getBusinessType() === $type;
            }))
            ->willReturn($account);

        $result = $this->service->createAccount($userId, $name, $type);
        $this->assertSame($account, $result);
    }

    public function testGetAccount(): void
    {
        $id = 7;
        $userId = 5;
        $account = new BusinessAccount($userId, 'Biz', 'type');
        $account->setId($id);

        $this->repo->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($account);

        $result = $this->service->getAccount($id, $userId);
        $this->assertSame($account, $result);
    }

    public function testGetAccountWhenNotFound(): void
    {
        $this->repo->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->service->getAccount(999, 1);
    }

    public function testGetAccountWhenNotOwner(): void
    {
        $id = 7;
        $ownerId = 5;
        $userId = 6; // different user
        $account = new BusinessAccount($ownerId, 'Biz', 'type');
        $account->setId($id);

        $this->repo->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($account);

        $this->expectException(AuthorizationException::class);
        $this->service->getAccount($id, $userId);
    }

    public function testGetUserAccounts(): void
    {
        $userId = 5;
        $accounts = [
            new BusinessAccount($userId, 'A', 'type'),
            new BusinessAccount($userId, 'B', 'type')
        ];

        $this->repo->expects($this->once())
            ->method('findByUserId')
            ->with((string)$userId, 20, 0)
            ->willReturn($accounts);

        $result = $this->service->getUserAccounts($userId, 20, 0);
        $this->assertSame($accounts, $result);
    }

    public function testUpdateTaxId(): void
    {
        $id = 10;
        $taxId = 'TAX123';
        $userId = 1;
        $account = new BusinessAccount($userId, 'Biz', 'type');
        $account->setId($id);
        $account->setTaxId(null);

        $this->repo->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($account);
        $this->repo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (BusinessAccount $a) use ($taxId) {
                return $a->getTaxId() === $taxId;
            }))
            ->willReturn($account);

        $result = $this->service->updateTaxId($id, $taxId, $userId);
        $this->assertSame($account, $result);
    }

    public function testUpdateTaxIdWhenNotOwner(): void
    {
        $id = 10;
        $taxId = 'TAX123';
        $ownerId = 1;
        $userId = 2;
        $account = new BusinessAccount($ownerId, 'Biz', 'type');
        $account->setId($id);

        $this->repo->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($account);

        $this->expectException(AuthorizationException::class);
        $this->service->updateTaxId($id, $taxId, $userId);
    }

    public function testUpdateContact(): void
    {
        $id = 11;
        $phone = '1234567890';
        $email = 'test@example.com';
        $userId = 1;
        $account = new BusinessAccount($userId, 'Biz', 'type');
        $account->setId($id);

        $this->repo->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($account);
        $this->repo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (BusinessAccount $a) use ($phone, $email) {
                return $a->getPhone() === $phone && $a->getEmail() === $email;
            }))
            ->willReturn($account);

        $result = $this->service->updateContact($id, $phone, $email, $userId);
        $this->assertSame($account, $result);
    }

    public function testSetWhatsappBusiness(): void
    {
        $id = 12;
        $waId = 'wa123';
        $userId = 1;
        $account = new BusinessAccount($userId, 'Biz', 'type');
        $account->setId($id);

        $this->repo->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($account);
        $this->repo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (BusinessAccount $a) use ($waId) {
                return $a->getWhatsappBusinessId() === $waId;
            }))
            ->willReturn($account);

        $result = $this->service->setWhatsappBusiness($id, $waId, $userId);
        $this->assertSame($account, $result);
    }

    public function testVerifyAccount(): void
    {
        $id = 13;
        $userId = 1;
        $account = new BusinessAccount($userId, 'Biz', 'type');
        $account->setId($id);
        $account->setVerified(false);

        $this->repo->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($account);
        $this->repo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (BusinessAccount $a) {
                return $a->isVerified() === true;
            }))
            ->willReturn($account);

        $result = $this->service->verifyAccount($id, $userId);
        $this->assertSame($account, $result);
        $this->assertTrue($account->isVerified());
    }

    public function testVerifyAccountWhenNotOwner(): void
    {
        $id = 13;
        $ownerId = 1;
        $userId = 2;
        $account = new BusinessAccount($ownerId, 'Biz', 'type');
        $account->setId($id);

        $this->repo->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($account);

        $this->expectException(AuthorizationException::class);
        $this->service->verifyAccount($id, $userId);
    }

    public function testDeleteAccount(): void
    {
        $id = 14;
        $userId = 1;
        $account = new BusinessAccount($userId, 'Biz', 'type');
        $account->setId($id);

        $this->repo->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($account);
        $this->repo->expects($this->once())
            ->method('delete')
            ->with($id)
            ->willReturn(true);

        $result = $this->service->deleteAccount($id, $userId);
        $this->assertTrue($result);
    }

    public function testDeleteAccountWhenNotOwner(): void
    {
        $id = 14;
        $ownerId = 1;
        $userId = 2;
        $account = new BusinessAccount($ownerId, 'Biz', 'type');
        $account->setId($id);

        $this->repo->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($account);

        $this->expectException(AuthorizationException::class);
        $this->service->deleteAccount($id, $userId);
    }

    public function testSearchUserAccounts(): void
    {
        $userId = 99;
        $name = 'Foo';
        $result = [new BusinessAccount($userId, 'Foo Bar', 'type')];

        $this->repo->expects($this->once())
            ->method('searchByUserIdAndName')
            ->with($userId, $name, 10, 5)
            ->willReturn($result);

        $actual = $this->service->searchUserAccounts($userId, $name, 10, 5);
        $this->assertSame($result, $actual);
    }
}
