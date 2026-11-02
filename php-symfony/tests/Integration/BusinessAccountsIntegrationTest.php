<?php declare(strict_types=1);

namespace App\Tests\Integration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\BusinessAccounts\Entity\BusinessAccount;
use App\BusinessAccounts\Repository\BusinessAccountRepository;
use App\BusinessAccounts\Service\BusinessAccountsService;
use Psr\Log\NullLogger;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\BusinessAccounts\Integration
 * 
 * Integration tests for BusinessAccounts module using real PDO
 */
#[AllowMockObjectsWithoutExpectations]
class BusinessAccountsIntegrationTest extends TestCase
{
    private ?\PDO $pdo = null;
    private BusinessAccountRepository $repository;
    private BusinessAccountsService $service;

    protected function setUp(): void
    {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // Create table
        $this->pdo->exec("
            CREATE TABLE business_accounts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                business_name VARCHAR(255) NOT NULL,
                business_type VARCHAR(50) NOT NULL,
                tax_id VARCHAR(50),
                address TEXT,
                phone VARCHAR(20),
                email VARCHAR(255),
                whatsapp_business_id VARCHAR(100),
                is_verified BOOLEAN DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME
            )
        ");

        $this->repository = new BusinessAccountRepository($this->pdo);
        $logger = new NullLogger();
        $this->service = new BusinessAccountsService($this->repository, $logger);
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
    }

    // ==================== CRUD Tests ====================

    public function testCreateAndRetrieveAccount(): void
    {
        $account = $this->service->createAccount(123, 'My Business', 'retail');

        $this->assertNotNull($account->getId());
        $this->assertEquals(123, $account->getUserId());
        $this->assertEquals('My Business', $account->getBusinessName());
        $this->assertEquals('retail', $account->getBusinessType());
        $this->assertFalse($account->isVerified());

        // Retrieve from database
        $retrieved = $this->repository->findById($account->getId());
        $this->assertNotNull($retrieved);
        $this->assertEquals('My Business', $retrieved->getBusinessName());
    }

    public function testGetUserAccounts(): void
    {
        $this->service->createAccount(123, 'Business 1', 'retail');
        $this->service->createAccount(123, 'Business 2', 'restaurant');
        $this->service->createAccount(456, 'Other Business', 'service');

        $user123 = $this->service->getUserAccounts(123);
        $user456 = $this->service->getUserAccounts(456);

        $this->assertCount(2, $user123);
        $this->assertCount(1, $user456);
    }

    public function testUpdateTaxId(): void
    {
        $account = $this->service->createAccount(123, 'My Business', 'retail');
        $updated = $this->service->updateTaxId($account->getId(), '123-456-789', 123);

        $this->assertEquals('123-456-789', $updated->getTaxId());

        $retrieved = $this->repository->findById($account->getId());
        $this->assertEquals('123-456-789', $retrieved->getTaxId());
    }

    public function testUpdateContact(): void
    {
        $account = $this->service->createAccount(123, 'My Business', 'retail');
        $updated = $this->service->updateContact($account->getId(), '+849012345678', 'contact@business.com', 123);

        $this->assertEquals('+849012345678', $updated->getPhone());
        $this->assertEquals('contact@business.com', $updated->getEmail());
    }

    public function testDeleteAccount(): void
    {
        $account = $this->service->createAccount(123, 'My Business', 'retail');
        $id = $account->getId();

        $result = $this->service->deleteAccount($id, 123);
        $this->assertTrue($result);

        // Soft delete: account still exists but findById should return null (soft deleted)
        // In SQLite in-memory, we need to verify soft delete worked
        // The account is still in DB but findById filters by deleted_at IS NULL
    }

    public function testDeleteAccountNotFound(): void
    {
        $this->expectException(\App\Shared\Exception\NotFoundException::class);
        $this->service->deleteAccount(999, 123);
    }

    // ==================== Verification Tests ====================

    public function testVerifyAccount(): void
    {
        $account = $this->service->createAccount(123, 'My Business', 'retail');
        $this->assertFalse($account->isVerified());

        $verified = $this->service->verifyAccount($account->getId(), 123);
        $this->assertTrue($verified->isVerified());

        $retrieved = $this->repository->findById($account->getId());
        $this->assertTrue($retrieved->isVerified());
    }

    public function testGetVerifiedAccounts(): void
    {

        $verified1 = $this->service->createAccount(123, 'Verified 1', 'retail');
        $this->service->verifyAccount($verified1->getId(), 123);
        $verified2 = $this->service->createAccount(123, 'Verified 2', 'restaurant');
        $this->service->verifyAccount($verified2->getId(), 123);
        $unverified = $this->service->createAccount(123, 'Unverified', 'service');

        $verified = $this->service->getVerifiedAccounts();

        $this->assertCount(2, $verified);
        foreach ($verified as $account) {
            $this->assertTrue($account->isVerified());
        }
    }

    // ==================== Search Tests ====================

    public function testSearchByName(): void
    {
        $this->service->createAccount(123, 'My Restaurant', 'restaurant');
        $this->service->createAccount(123, 'My Shop', 'retail');
        $this->service->createAccount(123, 'Other Store', 'retail');

        $results = $this->service->searchByName('My');

        $this->assertCount(2, $results);
    }

    public function testSearchByNameCaseInsensitive(): void
    {
        $this->service->createAccount(123, 'MY BUSINESS', 'retail');
        $this->service->createAccount(123, 'my small shop', 'retail');

        $results = $this->service->searchByName('business');

        $this->assertCount(1, $results);
    }

    // ==================== Filter Tests ====================

    public function testGetAccountsByType(): void
    {
        $this->service->createAccount(123, 'Shop 1', 'retail');
        $this->service->createAccount(123, 'Shop 2', 'retail');
        $this->service->createAccount(123, 'Restaurant', 'restaurant');

        $retail = $this->service->getAccountsByType('retail');
        $restaurant = $this->service->getAccountsByType('restaurant');

        $this->assertCount(2, $retail);
        $this->assertCount(1, $restaurant);
    }

    // ==================== Data Integrity Tests ====================

    public function testAccountIsolationBetweenUsers(): void
    {
        $user1 = $this->service->createAccount(1, 'User1 Business', 'retail');
        $user2 = $this->service->createAccount(2, 'User2 Business', 'restaurant');

        $user1Accounts = $this->service->getUserAccounts(1);
        $user2Accounts = $this->service->getUserAccounts(2);

        $this->assertCount(1, $user1Accounts);
        $this->assertCount(1, $user2Accounts);
        $this->assertEquals('User1 Business', $user1Accounts[0]->getBusinessName());
        $this->assertEquals('User2 Business', $user2Accounts[0]->getBusinessName());
    }

    public function testFindByTaxId(): void
    {
        $account = $this->service->createAccount(123, 'My Business', 'retail');
        $this->service->updateTaxId($account->getId(), '123-456-789', 123);

        $found = $this->repository->findByTaxId('123-456-789');

        $this->assertNotNull($found);
        $this->assertEquals('My Business', $found->getBusinessName());
    }

    public function testBusinessTypes(): void
    {
        $validTypes = ['retail', 'restaurant', 'service', 'manufacturing', 'technology'];

        foreach ($validTypes as $type) {
            $account = $this->service->createAccount(123, "Test $type", $type);
            $this->assertEquals($type, $account->getBusinessType());
        }
    }

    public function testSetWhatsappBusiness(): void
    {
        $account = $this->service->createAccount(123, 'My Business', 'retail');
        $updated = $this->service->setWhatsappBusiness($account->getId(), 'whatsapp_business_id', 123);

        $this->assertEquals('whatsapp_business_id', $updated->getWhatsappBusinessId());
    }

    public function testOptionalFieldsCanBeNull(): void
    {
        $account = $this->service->createAccount(123, 'My Business', 'retail');

        $this->assertNull($account->getTaxId());
        $this->assertNull($account->getAddress());
        $this->assertNull($account->getPhone());
        $this->assertNull($account->getEmail());
        $this->assertNull($account->getWhatsappBusinessId());
    }
}
