<?php

declare(strict_types=1);

namespace Tests\Unit\BusinessAccounts\Controller;

use App\BusinessAccounts\Controller\BusinessAccountsController;
use App\BusinessAccounts\Service\BusinessAccountsService;
use App\BusinessAccounts\Entity\BusinessAccount;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class BusinessAccountsControllerTest extends TestCase
{
    private BusinessAccountsService $serviceMock;
    private LoggerInterface $loggerMock;
    private BusinessAccountsController $controller;

    protected function setUp(): void
    {
        $this->serviceMock = $this->createMock(BusinessAccountsService::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->controller = new BusinessAccountsController($this->serviceMock, $this->loggerMock);
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function invokePrivate(object $obj, string $method, array $args = []): mixed
    {
        $ref = new ReflectionMethod($obj, $method);
        $ref->setAccessible(true);
        return $ref->invoke($obj, ...$args);
    }

    private function setAuthenticatedUser(BusinessAccountsController $controller, string $userId = '1'): void
    {
        $user = new class($userId) implements UserInterface {
            private string $id;
            public function __construct(string $id) { $this->id = $id; }
            public function getId(): string { return $this->id; }
            public function getUserIdentifier(): string { return $this->id; }
            public function getRoles(): array { return ['ROLE_USER']; }
            public function getPassword(): ?string { return null; }
            public function getSalt(): ?string { return null; }
            public function eraseCredentials(): void {}
        };
        $controller->setTestUser($user);
    }

    private function createAccountEntity(array $data = []): BusinessAccount
    {
        $account = new BusinessAccount(
            $data['user_id'] ?? 1,
            $data['business_name'] ?? 'Test Biz',
            $data['business_type'] ?? 'company'
        );
        if (isset($data['id'])) $account->setId((int)$data['id']);
        if (array_key_exists('tax_id', $data)) $account->setTaxId($data['tax_id']);
        if (array_key_exists('address', $data)) $account->setAddress($data['address']);
        if (array_key_exists('phone', $data)) $account->setPhone($data['phone']);
        if (array_key_exists('email', $data)) $account->setEmail($data['email']);
        if (array_key_exists('whatsapp_business_id', $data)) $account->setWhatsappBusinessId($data['whatsapp_business_id']);
        if (array_key_exists('is_verified', $data)) $account->setVerified((bool)$data['is_verified']);
        return $account;
    }

    private function createJsonRequest(array $data, string $method = 'POST'): Request
    {
        $content = json_encode($data);
        $server = ['CONTENT_TYPE' => 'application/json'];
        return Request::create('/api/business-accounts', $method, [], [], [], $server, $content);
    }

    private function createQueryRequest(array $query = []): Request
    {
        return Request::create('/api/business-accounts', 'GET', $query);
    }

    // ==================== Validation tests ====================

    public function testValidateBusinessNameRequired_Empty_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Business name is required');
        $this->invokePrivate($this->controller, 'validateBusinessNameRequired', [[]]);
    }

    public function testValidateBusinessNameRequired_Missing_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Business name is required');
        $this->invokePrivate($this->controller, 'validateBusinessNameRequired', [['other' => 'data']]);
    }

    public function testValidateBusinessTypeRequired_Empty_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Business type is required');
        $this->invokePrivate($this->controller, 'validateBusinessTypeRequired', [['business_type' => '']]);
    }

    public function testValidateBusinessName_TooLong_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot exceed 200 characters');
        $this->invokePrivate($this->controller, 'validateBusinessName', [['business_name' => str_repeat('a', 201)]]);
    }

    public function testValidateBusinessName_Valid_NoException(): void
    {
        $this->invokePrivate($this->controller, 'validateBusinessName', [['business_name' => 'Short']]);
        $this->assertTrue(true);
    }

    public function testValidateBusinessType_Invalid_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid business type');
        $this->invokePrivate($this->controller, 'validateBusinessType', [['business_type' => 'corp']]);
    }

    public function testValidateDescription_TooLong_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot exceed 500 characters');
        $this->invokePrivate($this->controller, 'validateDescription', [['description' => str_repeat('a', 501)]]);
    }

    public function testValidateUrlFields_Invalid_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid logoUrl URL');
        $this->invokePrivate($this->controller, 'validateUrlFields', [['logoUrl' => 'not-a-url'], ['logoUrl']]);
    }

    public function testValidatePhone_InvalidChars_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Phone number contains invalid characters');
        $this->invokePrivate($this->controller, 'validatePhone', [['phone' => 'abc123']]);
    }

    public function testValidatePhone_TooLong_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot exceed 20 characters');
        $this->invokePrivate($this->controller, 'validatePhone', [['phone' => str_repeat('1', 21)]]);
    }

    public function testValidateEmail_Invalid_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');
        $this->invokePrivate($this->controller, 'validateEmail', [['email' => 'not-an-email']]);
    }

    public function testValidateEmail_TooLong_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot exceed 100 characters');
        // Construct a valid email with total length > 100 but within RFC limits
        $local = str_repeat('a', 64); // max local part allowed
        $domain = str_repeat('b', 37); // domain label length (37) plus dot and TLD
        $email = $local . '@' . $domain . '.c';
        $this->invokePrivate($this->controller, 'validateEmail', [['email' => $email]]);
    }

    public function testValidateWhatsappBusinessId_InvalidChars_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('WhatsApp Business ID contains invalid characters');
        $this->invokePrivate($this->controller, 'validateWhatsappBusinessId', [['whatsapp_business_id' => 'id@123']]);
    }

    public function testValidateTaxId_ContainsHtml_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tax ID contains invalid characters');
        $this->invokePrivate($this->controller, 'validateTaxId', [['tax_id' => '<script>']]);
    }

    public function testValidateAccountUpdateData_Empty_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No data provided for update');
        $this->invokePrivate($this->controller, 'validateAccountUpdateData', [[]]);
    }

    // ==================== createAccount endpoint ====================

    public function testCreateAccount_Unauthenticated_Returns400(): void
    {
        $request = $this->createJsonRequest(['business_name' => 'Biz', 'business_type' => 'company']);
        // No auth
        $response = $this->controller->createAccount($request);
        $this->assertSame(400, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $content);
        $this->assertSame('Authentication required', $content['error']);
    }

    public function testCreateAccount_InvalidJson_Returns400(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $request = Request::create('/api/business-accounts', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->createAccount($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateAccount_MissingBusinessName_Throws400(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $request = $this->createJsonRequest(['business_type' => 'company']);
        $response = $this->controller->createAccount($request);
        $this->assertSame(400, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertStringContainsString('Business name is required', $content['error']);
    }

    public function testCreateAccount_MissingBusinessType_Throws400(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $request = $this->createJsonRequest(['business_name' => 'Biz']);
        $response = $this->controller->createAccount($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Business type is required', $response->getContent());
    }

    public function testCreateAccount_InvalidBusinessType_Throws400(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $request = $this->createJsonRequest(['business_name' => 'Biz', 'business_type' => 'corp']);
        $response = $this->controller->createAccount($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid business type', $response->getContent());
    }

    public function testCreateAccount_InvalidPhone_Throws400(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $request = $this->createJsonRequest(['business_name' => 'Biz', 'business_type' => 'company', 'phone' => 'abc']);
        $response = $this->controller->createAccount($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateAccount_InvalidEmail_Throws400(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $request = $this->createJsonRequest(['business_name' => 'Biz', 'business_type' => 'company', 'email' => 'bad-email']);
        $response = $this->controller->createAccount($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateAccount_InvalidUrl_Throws400(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $request = $this->createJsonRequest(['business_name' => 'Biz', 'business_type' => 'company', 'logoUrl' => 'not-url']);
        $response = $this->controller->createAccount($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateAccount_InvalidTaxId_Throws400(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $request = $this->createJsonRequest(['business_name' => 'Biz', 'business_type' => 'company', 'tax_id' => '<script>']);
        $response = $this->controller->createAccount($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateAccount_Success_Returns201(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $userId = 1;
        $account = $this->createAccountEntity(['id' => 5, 'user_id' => $userId, 'business_name' => 'MyCo', 'business_type' => 'company']);
        $this->serviceMock->expects($this->once())
            ->method('createAccount')
            ->with($userId, 'MyCo', 'company')
            ->willReturn($account);
        $request = $this->createJsonRequest(['user_id' => $userId, 'business_name' => 'MyCo', 'business_type' => 'company']);
        $response = $this->controller->createAccount($request);
        $this->assertSame(201, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
    }

    public function testCreateAccount_WithOptionalFields_AppliesOptions(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $userId = 1;
        $account = $this->createAccountEntity([
            'id' => 5,
            'user_id' => $userId,
            'business_name' => 'MyCo',
            'business_type' => 'company',
            'tax_id' => 'TAX123',
            'phone' => '+123456789',
            'email' => 'test@example.com',
            'whatsapp_business_id' => 'wa123'
        ]);
        $this->serviceMock->expects($this->once())
            ->method('createAccount')
            ->with($userId, 'MyCo', 'company')
            ->willReturn($account);
        $request = $this->createJsonRequest([
            'user_id' => $userId,
            'business_name' => 'MyCo',
            'business_type' => 'company',
            'tax_id' => 'TAX123',
            'phone' => '+123456789',
            'email' => 'test@example.com',
            'whatsapp_business_id' => 'wa123'
        ]);
        $response = $this->controller->createAccount($request);
        $this->assertSame(201, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
    }

    // ==================== getUserAccounts endpoint ====================

    public function testGetUserAccounts_Unauthenticated_Returns400(): void
    {
        $request = $this->createQueryRequest([]);
        $response = $this->controller->getUserAccounts($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetUserAccounts_Success_Returns200(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $userId = 1;
        $accounts = [
            $this->createAccountEntity(['id' => 1, 'user_id' => $userId, 'business_name' => 'A']),
            $this->createAccountEntity(['id' => 2, 'user_id' => $userId, 'business_name' => 'B'])
        ];
        $this->serviceMock->expects($this->once())
            ->method('getUserAccounts')
            ->with($userId, 20, 0)
            ->willReturn($accounts);
        $request = $this->createQueryRequest([]);
        $response = $this->controller->getUserAccounts($request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
        $this->assertCount(2, $content['data']);
    }

    public function testGetUserAccounts_CustomLimitOffset_Used(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $userId = 1;
        $accounts = [$this->createAccountEntity(['id' => 1, 'user_id' => $userId])];
        $this->serviceMock->expects($this->once())
            ->method('getUserAccounts')
            ->with($userId, 50, 10)
            ->willReturn($accounts);
        $request = $this->createQueryRequest(['limit' => '50', 'offset' => '10']);
        $response = $this->controller->getUserAccounts($request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertSame(50, $content['meta']['limit']);
        $this->assertSame(10, $content['meta']['offset']);
    }

    // ==================== getAccount endpoint ====================

    public function testGetAccount_InvalidId_Returns400(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $request = $this->createQueryRequest([]); // dummy
        $response = $this->controller->getAccount(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetAccount_ServiceThrows_Returns500(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $userId = 1;
        // The controller will call service->getAccount($id, $userId)
        $this->serviceMock->expects($this->once())
            ->method('getAccount')
            ->with(5, $userId)
            ->willThrowException(new \Exception('Not found'));
        $request = $this->createQueryRequest([]);
        $response = $this->controller->getAccount(5, $request);
        $this->assertSame(500, $response->getStatusCode());
    }

    public function testGetAccount_Success_Returns200(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $userId = 1;
        $account = $this->createAccountEntity(['id' => 5, 'user_id' => $userId, 'business_name' => 'Test']);
        $this->serviceMock->expects($this->once())
            ->method('getAccount')
            ->with(5, $userId)
            ->willReturn($account);
        $request = $this->createQueryRequest([]);
        $response = $this->controller->getAccount(5, $request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
    }

    // ==================== updateAccount endpoint ====================

    public function testUpdateAccount_InvalidId_Returns400(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $request = $this->createJsonRequest(['tax_id' => 'TAX123']);
        $response = $this->controller->updateAccount(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateAccount_NoData_Returns400(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $request = $this->createJsonRequest([]);
        $response = $this->controller->updateAccount(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateAccount_InvalidTaxId_Throws400(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $request = $this->createJsonRequest(['tax_id' => '<script>']);
        $response = $this->controller->updateAccount(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateAccount_InvalidPhone_Throws400(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $request = $this->createJsonRequest(['phone' => 'abc']);
        $response = $this->controller->updateAccount(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateAccount_InvalidEmail_Throws400(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $request = $this->createJsonRequest(['email' => 'bad-email']);
        $response = $this->controller->updateAccount(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateAccount_InvalidWhatsapp_Throws400(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $request = $this->createJsonRequest(['whatsapp_business_id' => 'inv@lid']);
        $response = $this->controller->updateAccount(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateAccount_UpdatesTaxId_Success(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $userId = 1;
        $account = $this->createAccountEntity(['id' => 1, 'user_id' => $userId, 'tax_id' => null]);
        // Chain: getAccount after updates
        $this->serviceMock->expects($this->once())
            ->method('updateTaxId')
            ->with(1, 'TAX999', $userId)
            ->willReturn($account);
        $this->serviceMock->expects($this->once())
            ->method('getAccount')
            ->with(1, $userId)
            ->willReturn($account);
        $request = $this->createJsonRequest(['tax_id' => 'TAX999']);
        $response = $this->controller->updateAccount(1, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testUpdateAccount_UpdatesContact_Success(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $userId = 1;
        $account = $this->createAccountEntity(['id' => 1, 'user_id' => $userId, 'phone' => 'old', 'email' => 'old@ex.com']);
        $this->serviceMock->expects($this->once())
            ->method('updateContact')
            ->with(1, '+123', 'new@ex.com', $userId)
            ->willReturn($account);
        $this->serviceMock->expects($this->once())
            ->method('getAccount')
            ->with(1, $userId)
            ->willReturn($account);
        $request = $this->createJsonRequest(['phone' => '+123', 'email' => 'new@ex.com']);
        $response = $this->controller->updateAccount(1, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testUpdateAccount_UpdatesWhatsapp_Success(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $userId = 1;
        $account = $this->createAccountEntity(['id' => 1, 'user_id' => $userId]);
        $this->serviceMock->expects($this->once())
            ->method('setWhatsappBusiness')
            ->with(1, 'WA123', $userId)
            ->willReturn($account);
        $this->serviceMock->expects($this->once())
            ->method('getAccount')
            ->with(1, $userId)
            ->willReturn($account);
        $request = $this->createJsonRequest(['whatsapp_business_id' => 'WA123']);
        $response = $this->controller->updateAccount(1, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testUpdateAccount_Verify_Success(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $userId = 1;
        $account = $this->createAccountEntity(['id' => 1, 'user_id' => $userId, 'is_verified' => true]);
        $this->serviceMock->expects($this->once())
            ->method('verifyAccount')
            ->with(1, $userId)
            ->willReturn($account);
        $this->serviceMock->expects($this->once())
            ->method('getAccount')
            ->with(1, $userId)
            ->willReturn($account);
        $request = $this->createJsonRequest(['verify' => true]);
        $response = $this->controller->updateAccount(1, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== deleteAccount endpoint ====================

    public function testDeleteAccount_InvalidId_Returns400(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $request = $this->createJsonRequest([]);
        $response = $this->controller->deleteAccount(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testDeleteAccount_Success_Returns204(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $userId = 1;
        $this->serviceMock->expects($this->once())
            ->method('deleteAccount')
            ->with(1, $userId);
        $request = $this->createJsonRequest([]);
        $response = $this->controller->deleteAccount(1, $request);
        $this->assertSame(204, $response->getStatusCode());
    }

    // ==================== verifyAccount endpoint ====================

    public function testVerifyAccount_InvalidId_Returns400(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $request = $this->createJsonRequest([]);
        $response = $this->controller->verifyAccount(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testVerifyAccount_Success_Returns200(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $userId = 1;
        $account = $this->createAccountEntity(['id' => 1, 'user_id' => $userId, 'is_verified' => true]);
        $this->serviceMock->expects($this->once())
            ->method('verifyAccount')
            ->with(1, $userId)
            ->willReturn($account);
        $request = $this->createJsonRequest([]);
        $response = $this->controller->verifyAccount(1, $request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
    }

    // ==================== searchByName endpoint ====================

    public function testSearchByName_Unauthenticated_Returns400(): void
    {
        $request = $this->createQueryRequest(['name' => 'test']);
        $response = $this->controller->searchByName($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testSearchByName_EmptyName_ReturnsEmpty(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $request = $this->createQueryRequest(['name' => '']);
        $response = $this->controller->searchByName($request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertEmpty($content['data']);
    }

    public function testSearchByName_Success_Returns200(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $userId = 1;
        $accounts = [
            $this->createAccountEntity(['id' => 1, 'user_id' => $userId, 'business_name' => 'TestBiz'])
        ];
        $this->serviceMock->expects($this->once())
            ->method('searchUserAccounts')
            ->with($userId, 'Test', 20, 0)
            ->willReturn($accounts);
        $request = $this->createQueryRequest(['name' => 'Test']);
        $response = $this->controller->searchByName($request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertCount(1, $content['data']);
    }

    public function testSearchByName_CustomPagination_Used(): void
    {
        $this->setAuthenticatedUser($this->controller);
        $userId = 1;
        $accounts = [];
        $this->serviceMock->expects($this->once())
            ->method('searchUserAccounts')
            ->with($userId, 'Query', 50, 10)
            ->willReturn($accounts);
        $request = $this->createQueryRequest(['name' => 'Query', 'limit' => '50', 'offset' => '10']);
        $response = $this->controller->searchByName($request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertSame(50, $content['meta']['limit']);
        $this->assertSame(10, $content['meta']['offset']);
    }
}
