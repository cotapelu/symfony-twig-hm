<?php declare(strict_types=1);

namespace App\Tests\Unit\BusinessAccounts\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\BusinessAccounts\Entity\BusinessAccount;
use App\BusinessAccounts\Repository\BusinessAccountRepositoryInterface;
use App\BusinessAccounts\Service\BusinessAccountsService;
use App\Shared\Exception\AuthorizationException;
use App\Shared\Exception\NotFoundException;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class BusinessAccountsServiceTest extends TestCase
{
    private BusinessAccountRepositoryInterface $repoMock;
    private LoggerInterface $loggerMock;
    private BusinessAccountsService $service;

    protected function setUp(): void
    {
        $this->repoMock = $this->createMock(BusinessAccountRepositoryInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->service = new BusinessAccountsService($this->repoMock, $this->loggerMock);
    }

    private function createAccount(?int $id = null, int $userId = 1, string $name = 'Biz', string $type = 'corp', ?string $taxId = null, bool $isVerified = false): BusinessAccount
    {
        $account = new BusinessAccount($userId, $name, $type);
        $account->setTaxId($taxId);
        $account->setVerified($isVerified);
        if ($id !== null) {
            $account->setId($id);
        }
        return $account;
    }

    // ==================== createAccount ====================

    public function testCreateAccount(): void
    {
        $account = $this->createAccount();
        $this->repoMock->method('save')->willReturnArgument(0);
        $this->loggerMock->expects($this->once())->method('info')->with(
            'Business account created',
            $this->arrayHasKey('account_id')
        );

        $result = $this->service->createAccount(1, 'MyBiz', 'llc');

        $this->assertInstanceOf(BusinessAccount::class, $result);
        $this->assertSame(1, $result->getUserId());
        $this->assertSame('MyBiz', $result->getBusinessName());
        $this->assertSame('llc', $result->getBusinessType());
    }

    // ==================== getAccount ====================

    public function testGetAccountSuccess(): void
    {
        $account = $this->createAccount(id: 5, userId: 1);
        $this->repoMock->method('findById')->with(5)->willReturn($account);
        $result = $this->service->getAccount(5, 1);
        $this->assertSame($account, $result);
    }

    public function testGetAccountThrowsIfNotFound(): void
    {
        $this->repoMock->method('findById')->with(5)->willReturn(null);
        $this->expectException(NotFoundException::class);
        $this->service->getAccount(5, 1);
    }

    public function testGetAccountThrowsIfNotOwner(): void
    {
        $account = $this->createAccount(id: 5, userId: 2);
        $this->repoMock->method('findById')->with(5)->willReturn($account);
        $this->expectException(AuthorizationException::class);
        $this->service->getAccount(5, 1);
    }

    // ==================== getUserAccounts ====================

    public function testGetUserAccounts(): void
    {
        $accounts = [$this->createAccount(), $this->createAccount()];
        $this->repoMock->method('findByUserId')->with((string)1, 20, 0)->willReturn($accounts);
        $result = $this->service->getUserAccounts(1);
        $this->assertSame($accounts, $result);
    }

    public function testGetUserAccountsWithLimitOffset(): void
    {
        $accounts = [$this->createAccount()];
        $this->repoMock->method('findByUserId')->with((string)2, 5, 10)->willReturn($accounts);
        $result = $this->service->getUserAccounts(2, 5, 10);
        $this->assertSame($accounts, $result);
    }

    // ==================== updateTaxId ====================

    public function testUpdateTaxId(): void
    {
        $account = $this->createAccount(id: 5, userId: 1, taxId: null);
        $this->repoMock->method('findById')->with(5)->willReturn($account);
        $this->repoMock->method('save')->willReturnArgument(0);
        $this->loggerMock->expects($this->once())->method('info')->with(
            'Business account tax ID updated',
            $this->arrayHasKey('account_id')
        );

        $result = $this->service->updateTaxId(5, '123-45-6789', 1);

        $this->assertSame($account, $result);
        $this->assertSame('123-45-6789', $result->getTaxId());
    }

    public function testUpdateTaxIdThrowsIfNotFound(): void
    {
        $this->repoMock->method('findById')->with(5)->willReturn(null);
        $this->expectException(NotFoundException::class);
        $this->service->updateTaxId(5, 'tax', 1);
    }

    public function testUpdateTaxIdThrowsIfNotOwner(): void
    {
        $account = $this->createAccount(id: 5, userId: 2);
        $this->repoMock->method('findById')->with(5)->willReturn($account);
        $this->expectException(AuthorizationException::class);
        $this->service->updateTaxId(5, 'tax', 1);
    }

    // ==================== updateContact ====================

    public function testUpdateContact(): void
    {
        $account = $this->createAccount(id: 5, userId: 1);
        $this->repoMock->method('findById')->with(5)->willReturn($account);
        $this->repoMock->method('save')->willReturnArgument(0);

        $result = $this->service->updateContact(5, '+123456', 'email@example.com', 1);

        $this->assertSame($account, $result);
        $this->assertSame('+123456', $result->getPhone());
        $this->assertSame('email@example.com', $result->getEmail());
    }

    // ==================== setWhatsappBusiness ====================

    public function testSetWhatsappBusiness(): void
    {
        $account = $this->createAccount(id: 5, userId: 1);
        $this->repoMock->method('findById')->with(5)->willReturn($account);
        $this->repoMock->method('save')->willReturnArgument(0);

        $result = $this->service->setWhatsappBusiness(5, 'whatsapp-id-123', 1);

        $this->assertSame($account, $result);
        $this->assertSame('whatsapp-id-123', $result->getWhatsappBusinessId());
    }

    // ==================== verifyAccount ====================

    public function testVerifyAccount(): void
    {
        $account = $this->createAccount(id: 5, userId: 1, isVerified: false);
        $this->repoMock->method('findById')->with(5)->willReturn($account);
        $this->repoMock->method('save')->willReturnArgument(0);
        $this->loggerMock->expects($this->once())->method('info')->with(
            'Business account verified',
            $this->arrayHasKey('account_id')
        );

        $result = $this->service->verifyAccount(5, 1);

        $this->assertSame($account, $result);
        $this->assertTrue($result->isVerified());
    }

    public function testVerifyAccountThrowsIfNotOwner(): void
    {
        $account = $this->createAccount(id: 5, userId: 2);
        $this->repoMock->method('findById')->with(5)->willReturn($account);
        $this->expectException(AuthorizationException::class);
        $this->service->verifyAccount(5, 1);
    }

    // ==================== deleteAccount ====================

    public function testDeleteAccount(): void
    {
        $account = $this->createAccount(id: 5, userId: 1);
        $account->setId(5);
        $this->repoMock->method('findById')->with(5)->willReturn($account);
        $this->repoMock->method('delete')->with(5)->willReturn(true);
        $this->loggerMock->expects($this->once())->method('info')->with(
            'Business account deleted',
            $this->arrayHasKey('account_id')
        );

        $result = $this->service->deleteAccount(5, 1);

        $this->assertTrue($result);
    }

    public function testDeleteAccountThrowsIfNotFound(): void
    {
        $this->repoMock->method('findById')->with(5)->willReturn(null);
        $this->expectException(NotFoundException::class);
        $this->service->deleteAccount(5, 1);
    }

    // ==================== searchUserAccounts ====================

    public function testSearchUserAccounts(): void
    {
        $accounts = [$this->createAccount(name: 'Alice'), $this->createAccount(name: 'Bob')];
        $this->repoMock->method('searchByUserIdAndName')->with(1, 'Ali', 10, 0)->willReturn($accounts);
        $result = $this->service->searchUserAccounts(1, 'Ali', 10, 0);
        $this->assertSame($accounts, $result);
    }
}
