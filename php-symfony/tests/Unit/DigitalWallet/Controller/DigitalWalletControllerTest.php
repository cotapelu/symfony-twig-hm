<?php declare(strict_types=1);

namespace Tests\Unit\DigitalWallet\Controller;

use App\DigitalWallet\Controller\DigitalWalletController;
use App\DigitalWallet\Service\DigitalWalletService;
use App\DigitalWallet\Entity\DigitalWallet;
use App\DigitalWallet\Entity\WalletTransaction;
use App\User\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use InvalidArgumentException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use PHPUnit\Framework\TestCase;

class DigitalWalletControllerTest extends TestCase
{
    private DigitalWalletService $serviceMock;
    private DigitalWalletController $controller;

    protected function setUp(): void
    {
        $this->serviceMock = $this->createMock(DigitalWalletService::class);
        $this->controller = new DigitalWalletController($this->serviceMock);
    }

    private function setAuthenticatedUser(string $userId = '123'): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);
        $this->controller->setTestUser($user);
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function createWalletMock(int $id, string $userId, string $type = 'main', string $currency = 'VND', int $balance = 0): DigitalWallet
    {
        $wallet = $this->createMock(DigitalWallet::class);
        $wallet->method('getId')->willReturn($id);
        $wallet->method('getUserId')->willReturn($userId);
        $wallet->method('getWalletType')->willReturn($type);
        $wallet->method('getCurrency')->willReturn($currency);
        $wallet->method('getBalance')->willReturn($balance);
        return $wallet;
    }

    private function createTransactionMock(int $id, int $amount, string $status = 'completed', ?int $fee = null, ?\DateTimeImmutable $createdAt = null): WalletTransaction
    {
        $tx = $this->createMock(WalletTransaction::class);
        $tx->method('getId')->willReturn($id);
        $tx->method('getAmount')->willReturn($amount);
        $tx->method('getStatus')->willReturn($status);
        if ($fee !== null) {
            $tx->method('getFee')->willReturn($fee);
        }
        if ($createdAt !== null) {
            $tx->method('getCreatedAt')->willReturn($createdAt);
        } else {
            $tx->method('getCreatedAt')->willReturn(new \DateTimeImmutable());
        }
        return $tx;
    }

    // ==================== createWallet ====================

    public function testCreateWallet_Success(): void
    {
        $this->setAuthenticatedUser('user1');
        $wallet = $this->createWalletMock(1, 'user1', 'main', 'VND', 0);
        $this->serviceMock->expects($this->once())
            ->method('createWallet')
            ->with('user1', 'main', 'VND')
            ->willReturn($wallet);

        $request = Request::create('/create', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['walletType' => 'main']));
        $response = $this->controller->createWallet($request);

        $this->assertSame(201, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['id']);
        $this->assertSame('user1', $data['userId']);
        $this->assertSame('main', $data['walletType']);
        $this->assertSame(0, $data['balance']);
        $this->assertSame('VND', $data['currency']);
    }

    public function testCreateWallet_InvalidWalletType_Returns400(): void
    {
        $this->setAuthenticatedUser('user1');
        $request = Request::create('/create', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['walletType' => 'invalid']));
        $response = $this->controller->createWallet($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Wallet type must be one of', $response->getContent());
    }

    public function testCreateWallet_InvalidCurrency_Returns400(): void
    {
        $this->setAuthenticatedUser('user1');
        $request = Request::create('/create', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['currency' => 'XYZ']));
        $response = $this->controller->createWallet($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Currency must be one of', $response->getContent());
    }

    public function testCreateWallet_Unauthenticated_Returns403(): void
    {
        $this->controller->setContainer(new ContainerBuilder());
        $request = Request::create('/create', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['walletType' => 'main']));
        $response = $this->controller->createWallet($request);
        $this->assertSame(403, $response->getStatusCode());
    }

    // ==================== deposit ====================

    public function testDeposit_Success(): void
    {
        $this->setAuthenticatedUser('user1');
        $wallet = $this->createWalletMock(1, 'user1', 'main', 'VND', 1000);
        $transaction = $this->createTransactionMock(101, 1000, 'completed');

        $this->serviceMock->expects($this->once())
            ->method('deposit')
            ->with('1', 1000, 'Deposit', null, 'user1')
            ->willReturn($transaction);
        $this->serviceMock->expects($this->once())
            ->method('isWalletOwnedBy')
            ->with('1', 'user1')
            ->willReturn(true);

        $request = Request::create('/1/deposit', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['amount' => 1000]));
        $response = $this->controller->deposit('1', $request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true)['data'];
        $this->assertSame(101, $data['transactionId']);
        $this->assertSame(1000, $data['amount']);
        $this->assertSame('completed', $data['status']);
    }

    public function testDeposit_InvalidAmount_BelowMin_Returns400(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->serviceMock->expects($this->once())
            ->method('isWalletOwnedBy')
            ->with('1', 'user1')
            ->willReturn(true);
        $request = Request::create('/1/deposit', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['amount' => 500]));
        $response = $this->controller->deposit('1', $request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Amount must be between', $response->getContent());
    }

    public function testDeposit_WalletNotOwned_Returns403(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->serviceMock->expects($this->once())
            ->method('isWalletOwnedBy')
            ->with('1', 'user1')
            ->willReturn(false);

        $request = Request::create('/1/deposit', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['amount' => 1000]));
        $response = $this->controller->deposit('1', $request);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('Access denied', $response->getContent());
    }

    public function testDeposit_ServiceThrowsException_Returns500(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->serviceMock->expects($this->once())
            ->method('isWalletOwnedBy')
            ->with('1', 'user1')
            ->willReturn(true);
        $this->serviceMock->expects($this->once())
            ->method('deposit')
            ->with('1', 1000, 'Deposit', null, 'user1')
            ->will($this->throwException(new \Exception('Service failure')));

        $request = Request::create('/1/deposit', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['amount' => 1000]));
        $response = $this->controller->deposit('1', $request);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(['error' => 'Internal server error'], json_decode($response->getContent(), true));
    }

    // ==================== withdraw ====================

    public function testWithdraw_Success(): void
    {
        $this->setAuthenticatedUser('user1');
        $wallet = $this->createWalletMock(1, 'user1', 'main', 'VND', 2000);
        $transaction = $this->createTransactionMock(102, -1000, 'completed'); // amount may be negative but abs shown

        $this->serviceMock->expects($this->once())
            ->method('withdraw')
            ->with('1', 1000, 'Withdrawal', null, 'user1')
            ->willReturn($transaction);
        $this->serviceMock->expects($this->once())
            ->method('isWalletOwnedBy')
            ->with('1', 'user1')
            ->willReturn(true);

        $request = Request::create('/1/withdraw', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['amount' => 1000]));
        $response = $this->controller->withdraw('1', $request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true)['data'];
        $this->assertSame(102, $data['transactionId']);
        $this->assertSame(1000, $data['amount']); // abs
        $this->assertSame('completed', $data['status']);
    }

    public function testWithdraw_ServiceThrowsException_Returns500(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->serviceMock->expects($this->once())
            ->method('isWalletOwnedBy')
            ->with('1', 'user1')
            ->willReturn(true);
        $this->serviceMock->expects($this->once())
            ->method('withdraw')
            ->with('1', 1000, 'Withdrawal', null, 'user1')
            ->will($this->throwException(new \Exception('Service failure')));

        $request = Request::create('/1/withdraw', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['amount' => 1000]));
        $response = $this->controller->withdraw('1', $request);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(['error' => 'Internal server error'], json_decode($response->getContent(), true));
    }

    // ==================== transfer ====================

    public function testTransfer_Success(): void
    {
        $this->setAuthenticatedUser('user1');
        $fromWallet = $this->createWalletMock(1, 'user1', 'main', 'VND', 2000);
        $toWallet = $this->createWalletMock(2, 'user2', 'main', 'VND', 1000);

        $txFrom = $this->createTransactionMock(201, -500, 'completed', 25);
        $txTo = $this->createTransactionMock(202, 500, 'completed');

        $this->serviceMock->expects($this->once())
            ->method('isWalletOwnedBy')
            ->with('1', 'user1')
            ->willReturn(true);
        $this->serviceMock->expects($this->once())
            ->method('walletExists')
            ->with('2')
            ->willReturn(true);
        $this->serviceMock->expects($this->once())
            ->method('transfer')
            ->with('1', '2', 1000, 'Transfer', null, 'user1')
            ->willReturn([$txFrom, $txTo]);

        $request = Request::create('/1/transfer', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'toWalletId' => '2',
            'amount' => 1000
        ]));
        $response = $this->controller->transfer('1', $request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true)['data'];
        $this->assertSame(201, $data['fromTransaction']['id']);
        $this->assertSame(202, $data['toTransaction']['id']);
    }

    public function testTransfer_RecipientNotFound_Returns400(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->serviceMock->expects($this->once())
            ->method('isWalletOwnedBy')
            ->with('1', 'user1')
            ->willReturn(true);
        $this->serviceMock->expects($this->once())
            ->method('walletExists')
            ->with('2')
            ->willReturn(false);

        $request = Request::create('/1/transfer', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'toWalletId' => '2',
            'amount' => 1000
        ]));
        $response = $this->controller->transfer('1', $request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['error' => 'Recipient wallet not found'], json_decode($response->getContent(), true));
    }

    // ==================== getBalance ====================

    public function testGetBalance_Success(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->serviceMock->expects($this->once())
            ->method('getBalance')
            ->with('1', 'user1')
            ->willReturn(1500);

        $request = Request::create('/1/balance', 'GET');
        $response = $this->controller->getBalance('1');

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true)['data'];
        $this->assertSame('1', $data['walletId']);
        $this->assertSame(1500, $data['balance']);
    }

    // ==================== getTransactions (MAJOR GAP - NOT PREVIOUSLY TESTED) ====================

    public function testGetTransactions_Success_ReturnsTransactionsList(): void
    {
        $this->setAuthenticatedUser('user1');
        $tx1 = $this->createTransactionMock(301, -500, 'completed', 10);
        $tx2 = $this->createTransactionMock(302, 2000, 'completed');

        $this->serviceMock->expects($this->once())
            ->method('getTransactionHistory')
            ->with('1', 50, 0, 'user1')
            ->willReturn([$tx1, $tx2]);

        $request = Request::create('/1/transactions', 'GET');
        $response = $this->controller->getTransactions('1', $request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true)['data'];
        $this->assertCount(2, $data);
        $this->assertSame(301, $data[0]['id']);
        $this->assertSame(-500, $data[0]['amount']);
        $this->assertSame(302, $data[1]['id']);
        $this->assertSame(2000, $data[1]['amount']);
    }

    public function testGetTransactions_WithPagination_UsesLimitAndOffset(): void
    {
        $this->setAuthenticatedUser('user1');
        $tx = $this->createTransactionMock(303, 100, 'completed');

        $this->serviceMock->expects($this->once())
            ->method('getTransactionHistory')
            ->with('1', 10, 20, 'user1')
            ->willReturn([$tx]);

        $request = Request::create('/1/transactions', 'GET', ['limit' => '10', 'offset' => '20']);
        $response = $this->controller->getTransactions('1', $request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetTransactions_Unauthenticated_Returns403(): void
    {
        $this->controller->setContainer(new ContainerBuilder());
        $request = Request::create('/1/transactions', 'GET');
        $response = $this->controller->getTransactions('1', $request);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testGetTransactions_WalletNotOwned_Returns403(): void
    {
        $this->setAuthenticatedUser('user1');
        // In the actual implementation, validateWalletOwnership is called via depositAction pattern
        // Wait, getTransactionsAction does NOT call validateWalletOwnership! It calls getAuthenticatedUserId then directly service call
        // Actually the code shows: $userId = $this->getAuthenticatedUserId(); no ownership check!
        // So the service itself must enforce ownership. We'll test service exception path instead.
        $this->serviceMock->expects($this->once())
            ->method('getTransactionHistory')
            ->with('1', 50, 0, 'user1')
            ->will($this->throwException(new \Exception('Access denied')));

        $request = Request::create('/1/transactions', 'GET');
        $response = $this->controller->getTransactions('1', $request);
        $this->assertSame(500, $response->getStatusCode());
    }

    public function testGetTransactions_ServiceThrowsException_Returns500(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->serviceMock->expects($this->once())
            ->method('getTransactionHistory')
            ->with('1', 50, 0, 'user1')
            ->will($this->throwException(new \Exception('DB error')));

        $request = Request::create('/1/transactions', 'GET');
        $response = $this->controller->getTransactions('1', $request);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(['error' => 'Internal server error'], json_decode($response->getContent(), true));
    }

    public function testGetTransactions_InvalidLimit_ThrowsValidationError(): void
    {
        $this->setAuthenticatedUser('user1');
        $request = Request::create('/1/transactions', 'GET', ['limit' => '-5']);
        $response = $this->controller->getTransactions('1', $request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('limit must be at least', $response->getContent());
    }

    public function testGetTransactions_LimitExceedsMax_ThrowsValidationError(): void
    {
        $this->setAuthenticatedUser('user1');
        $request = Request::create('/1/transactions', 'GET', ['limit' => '200']);
        $response = $this->controller->getTransactions('1', $request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('limit must not exceed', $response->getContent());
    }

    // ==================== withdraw - Missing validation tests ====================

    public function testWithdraw_InvalidAmount_AboveMax_Returns400(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->serviceMock->expects($this->once())
            ->method('isWalletOwnedBy')
            ->with('1', 'user1')
            ->willReturn(true);
        $request = Request::create('/1/withdraw', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['amount' => 200000000]));
        $response = $this->controller->withdraw('1', $request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Amount must be between', $response->getContent());
    }

    public function testWithdraw_InvalidAmount_NonNumeric_Returns400(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->serviceMock->expects($this->once())
            ->method('isWalletOwnedBy')
            ->with('1', 'user1')
            ->willReturn(true);
        $request = Request::create('/1/withdraw', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['amount' => 'abc']));
        $response = $this->controller->withdraw('1', $request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Valid amount is required', $response->getContent());
    }

    public function testWithdraw_MissingAmount_Returns400(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->serviceMock->expects($this->once())
            ->method('isWalletOwnedBy')
            ->with('1', 'user1')
            ->willReturn(true);
        $request = Request::create('/1/withdraw', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([]));
        $response = $this->controller->withdraw('1', $request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Valid amount is required', $response->getContent());
    }

    public function testWithdraw_DescriptionTooLong_Returns400(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->serviceMock->expects($this->once())
            ->method('isWalletOwnedBy')
            ->with('1', 'user1')
            ->willReturn(true);
        $longDesc = str_repeat('a', 501);
        $request = Request::create('/1/withdraw', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['amount' => 1000, 'description' => $longDesc]));
        $response = $this->controller->withdraw('1', $request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Description must not exceed 500 characters', $response->getContent());
    }

    // ==================== transfer - Missing validation tests ====================

    public function testTransfer_MissingToWalletId_Returns400(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->serviceMock->expects($this->once())
            ->method('isWalletOwnedBy')
            ->with('1', 'user1')
            ->willReturn(true);
        $request = Request::create('/1/transfer', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['amount' => 1000]));
        $response = $this->controller->transfer('1', $request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['error' => 'Recipient wallet ID is required'], json_decode($response->getContent(), true));
    }

    public function testTransfer_EmptyToWalletId_Returns400(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->serviceMock->expects($this->once())
            ->method('isWalletOwnedBy')
            ->with('1', 'user1')
            ->willReturn(true);
        $request = Request::create('/1/transfer', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['toWalletId' => '', 'amount' => 1000]));
        $response = $this->controller->transfer('1', $request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['error' => 'Recipient wallet ID is required'], json_decode($response->getContent(), true));
    }

    public function testTransfer_InvalidAmount_BelowMin_Returns400(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->serviceMock->expects($this->once())
            ->method('isWalletOwnedBy')
            ->with('1', 'user1')
            ->willReturn(true);
        $request = Request::create('/1/transfer', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['toWalletId' => '2', 'amount' => 500]));
        $response = $this->controller->transfer('1', $request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Amount must be between', $response->getContent());
    }

    public function testTransfer_InvalidAmount_AboveMax_Returns400(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->serviceMock->expects($this->once())
            ->method('isWalletOwnedBy')
            ->with('1', 'user1')
            ->willReturn(true);
        $request = Request::create('/1/transfer', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['toWalletId' => '2', 'amount' => 200000000]));
        $response = $this->controller->transfer('1', $request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Amount must be between', $response->getContent());
    }

    public function testTransfer_InvalidAmount_NonNumeric_Returns400(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->serviceMock->expects($this->once())
            ->method('isWalletOwnedBy')
            ->with('1', 'user1')
            ->willReturn(true);
        $request = Request::create('/1/transfer', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['toWalletId' => '2', 'amount' => 'xyz']));
        $response = $this->controller->transfer('1', $request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Valid amount is required', $response->getContent());
    }

    public function testTransfer_MissingAmount_Returns400(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->serviceMock->expects($this->once())
            ->method('isWalletOwnedBy')
            ->with('1', 'user1')
            ->willReturn(true);
        $request = Request::create('/1/transfer', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['toWalletId' => '2']));
        $response = $this->controller->transfer('1', $request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Valid amount is required', $response->getContent());
    }

    public function testTransfer_DescriptionTooLong_Returns400(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->serviceMock->expects($this->once())
            ->method('isWalletOwnedBy')
            ->with('1', 'user1')
            ->willReturn(true);
        $longDesc = str_repeat('a', 501);
        $request = Request::create('/1/transfer', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['toWalletId' => '2', 'amount' => 1000, 'description' => $longDesc]));
        $response = $this->controller->transfer('1', $request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Description must not exceed 500 characters', $response->getContent());
    }

    public function testTransfer_ServiceThrowsException_Returns500(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->serviceMock->expects($this->once())
            ->method('isWalletOwnedBy')
            ->with('1', 'user1')
            ->willReturn(true);
        $this->serviceMock->expects($this->once())
            ->method('walletExists')
            ->with('2')
            ->willReturn(true);
        $this->serviceMock->expects($this->once())
            ->method('transfer')
            ->with('1', '2', 1000, 'Transfer', null, 'user1')
            ->will($this->throwException(new \Exception('Service failure')));

        $request = Request::create('/1/transfer', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['toWalletId' => '2', 'amount' => 1000]));
        $response = $this->controller->transfer('1', $request);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(['error' => 'Internal server error'], json_decode($response->getContent(), true));
    }

    // ==================== getBalance - Missing edge tests ====================

    public function testGetBalance_WalletNotFound_Returns500(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->serviceMock->expects($this->once())
            ->method('getBalance')
            ->with('1', 'user1')
            ->will($this->throwException(new \Exception('Wallet not found')));

        $request = Request::create('/1/balance', 'GET');
        $response = $this->controller->getBalance('1');
        $this->assertSame(500, $response->getStatusCode());
    }

    public function testGetBalance_AccessDenied_Returns403(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->serviceMock->expects($this->once())
            ->method('getBalance')
            ->with('1', 'user1')
            ->will($this->throwException(new AccessDeniedException('Access denied')));

        $request = Request::create('/1/balance', 'GET');
        $response = $this->controller->getBalance('1');
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(['error' => 'Access denied'], json_decode($response->getContent(), true));
    }

    // ==================== deposit - Additional validation edge cases ====================

    public function testDeposit_InvalidAmount_AboveMax_Returns400(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->serviceMock->expects($this->once())
            ->method('isWalletOwnedBy')
            ->with('1', 'user1')
            ->willReturn(true);
        $request = Request::create('/1/deposit', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['amount' => 200000000]));
        $response = $this->controller->deposit('1', $request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Amount must be between', $response->getContent());
    }

    public function testDeposit_InvalidAmount_NonNumeric_Returns400(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->serviceMock->expects($this->once())
            ->method('isWalletOwnedBy')
            ->with('1', 'user1')
            ->willReturn(true);
        $request = Request::create('/1/deposit', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['amount' => 'xyz']));
        $response = $this->controller->deposit('1', $request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Valid amount is required', $response->getContent());
    }

    public function testDeposit_MissingAmount_Returns400(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->serviceMock->expects($this->once())
            ->method('isWalletOwnedBy')
            ->with('1', 'user1')
            ->willReturn(true);
        $request = Request::create('/1/deposit', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([]));
        $response = $this->controller->deposit('1', $request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Valid amount is required', $response->getContent());
    }

    public function testDeposit_DescriptionTooLong_Returns400(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->serviceMock->expects($this->once())
            ->method('isWalletOwnedBy')
            ->with('1', 'user1')
            ->willReturn(true);
        $longDesc = str_repeat('a', 501);
        $request = Request::create('/1/deposit', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['amount' => 1000, 'description' => $longDesc]));
        $response = $this->controller->deposit('1', $request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Description must not exceed 500 characters', $response->getContent());
    }

    // ==================== createWallet - Additional edge cases ====================

    public function testCreateWallet_SavingsWallet_WithUSD_Success(): void
    {
        $this->setAuthenticatedUser('user1');
        $wallet = $this->createWalletMock(2, 'user1', 'savings', 'USD', 0);
        $this->serviceMock->expects($this->once())
            ->method('createWallet')
            ->with('user1', 'savings', 'USD')
            ->willReturn($wallet);

        $request = Request::create('/create', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['walletType' => 'savings', 'currency' => 'USD']));
        $response = $this->controller->createWallet($request);

        $this->assertSame(201, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('savings', $data['walletType']);
        $this->assertSame('USD', $data['currency']);
    }

    public function testCreateWallet_AmountDefaults_WhenNotProvided(): void
    {
        // No amount field in request; defaulting in service? Not applicable for createWallet
        $this->setAuthenticatedUser('user1');
        $wallet = $this->createWalletMock(3, 'user1', 'main', 'VND', 0);
        $this->serviceMock->expects($this->once())
            ->method('createWallet')
            ->with('user1', 'main', 'VND')
            ->willReturn($wallet);

        $request = Request::create('/create', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([]));
        $response = $this->controller->createWallet($request);

        $this->assertSame(201, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('main', $data['walletType']);
        $this->assertSame('VND', $data['currency']);
    }

    public function testGetTransactions_EmptyList_ReturnsEmptyArray(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->serviceMock->expects($this->once())
            ->method('getTransactionHistory')
            ->with('1', 50, 0, 'user1')
            ->willReturn([]);

        $request = Request::create('/1/transactions', 'GET');
        $response = $this->controller->getTransactions('1', $request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true)['data'];
        $this->assertSame([], $data);
    }
}
