<?php
declare(strict_types=1);

namespace App\Tests\Unit\BusinessAccount\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use PHPUnit\Framework\TestCase;
use App\BusinessAccount\Service\BusinessAccountService;
use App\BusinessAccount\Entity\Business;
use App\BusinessAccount\Entity\BusinessCategory;
use App\BusinessAccount\Entity\CatalogItem;
use App\BusinessAccount\Entity\Subscription;
use App\BusinessAccount\Entity\ApiKey;
use App\BusinessAccount\Repository\BusinessRepository;
use App\BusinessAccount\Repository\CatalogItemRepository;
use App\BusinessAccount\Repository\SubscriptionRepository;
use App\BusinessAccount\Repository\ApiKeyRepository;
use App\BusinessAccount\Repository\BusinessCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;

require_once __DIR__ . '/../../../../modules/BusinessAccount/Entity/BusinessAccountEntities.php';
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AllowMockObjectsWithoutExpectations]
class BusinessAccountServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $passwordHasher;
    private BusinessRepository $businessRepo;
    private CatalogItemRepository $catalogRepo;
    private SubscriptionRepository $subscriptionRepo;
    private ApiKeyRepository $apiKeyRepo;
    private BusinessCategoryRepository $categoryRepo;
    private BusinessAccountService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->businessRepo = $this->createMock(BusinessRepository::class);
        $this->catalogRepo = $this->createMock(CatalogItemRepository::class);
        $this->subscriptionRepo = $this->createMock(SubscriptionRepository::class);
        $this->apiKeyRepo = $this->createMock(ApiKeyRepository::class);
        $this->categoryRepo = $this->createMock(BusinessCategoryRepository::class);

        // Configure EntityManager to return our mocks
        $this->em->method('getRepository')
            ->willReturnMap([
                [Business::class, $this->businessRepo],
                [CatalogItem::class, $this->catalogRepo],
                [Subscription::class, $this->subscriptionRepo],
                [ApiKey::class, $this->apiKeyRepo],
                [BusinessCategory::class, $this->categoryRepo],
            ]);

        $this->service = new BusinessAccountService(
            $this->em,
            $this->passwordHasher
        );
    }

    public function testCreateBusinessSuccess(): void
    {
        $data = [
            'name' => 'Test Business',
            'email' => 'test@example.com',
            'phone' => '1234567890',
            'description' => 'Test description',
            'address' => '123 Test St',
            'latitude' => 10.123456,
            'longitude' => 20.654321,
            'website' => 'https://example.com',
            'instagram' => '@testbusiness',
            'facebook' => 'TestBusinessPage',
            'password' => 'securepassword123'
        ];

        $business = $this->createMock(Business::class);

        $hashedPassword = 'hashed_password_123';
        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->with($this->anything(), 'securepassword123')
            ->willReturn($hashedPassword);

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->anything());

        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->createBusiness($data);

        $this->assertInstanceOf(Business::class, $result);
    }

    public function testCreateBusinessWithoutOptionalFields(): void
    {
        $data = [
            'name' => 'Minimal Business',
            'email' => 'minimal@example.com'
        ];

        $this->passwordHasher->expects($this->never())
            ->method('hashPassword');

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->anything());

        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->createBusiness($data);

        $this->assertInstanceOf(Business::class, $result);
    }

    public function testUpdateBusinessSuccess(): void
    {
        $business = $this->createMock(Business::class);

        $category = $this->createMock(BusinessCategory::class);
        $category->method('getName')->willReturn('Technology');

        $this->categoryRepo->expects($this->once())
            ->method('find')
            ->with(5)
            ->willReturn($category);

        $this->em->expects($this->once())
            ->method('flush');

        $data = [
            'name' => 'New Name',
            'description' => 'New Description',
            'phone' => '0987654321',
            'address' => '456 New Ave',
            'latitude' => 15.987654,
            'longitude' => 25.321098,
            'website' => 'https://newsite.com',
            'instagram' => '@newbusiness',
            'facebook' => 'NewBusinessPage',
            'logo' => 'logo.png',
            'coverImage' => 'cover.jpg',
            'category' => 5
        ];

        $result = $this->service->updateBusiness($business, $data);

        $this->assertSame($business, $result);
    }

    public function testCreateSubscriptionSuccess(): void
    {
        $business = $this->createMock(Business::class);

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->anything());

        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->createSubscription($business, 'professional', 6);

        $this->assertInstanceOf(Subscription::class, $result);
    }

    public function testGenerateApiKeySuccess(): void
    {
        $business = $this->createMock(Business::class);
        $business->method('getId')->willReturn(123);

        $this->apiKeyRepo->expects($this->once())
            ->method('revokeOldKeys')
            ->with(123);

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->anything());

        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->generateApiKey($business, 'Test Key');

        $this->assertInstanceOf(ApiKey::class, $result);
    }

    public function testVerifyBusinessSuccess(): void
    {
        $business = $this->createMock(Business::class);

        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->verifyBusiness($business);

        $this->assertSame($business, $result);
    }

    public function testHasFeatureProfessionalPlan(): void
    {
        $business = $this->createMock(Business::class);
        $business->method('getId')->willReturn(123);
        
        $subscription = $this->createMock(Subscription::class);
        $subscription->method('getPlan')->willReturn('professional');
        
        $this->subscriptionRepo->expects($this->exactly(6))
            ->method('findActiveByBusiness')
            ->with(123)
            ->willReturn($subscription);

        $this->assertTrue($this->service->hasFeature($business, 'advanced_analytics'));
        $this->assertTrue($this->service->hasFeature($business, 'priority_support'));
        $this->assertTrue($this->service->hasFeature($business, 'custom_branding'));
        $this->assertFalse($this->service->hasFeature($business, 'multi_user'));
        $this->assertFalse($this->service->hasFeature($business, 'webhooks'));
        $this->assertFalse($this->service->hasFeature($business, 'white_label'));
    }

    public function testHasFeatureNoSubscription(): void
    {
        $business = $this->createMock(Business::class);
        $business->method('getId')->willReturn(123);
        
        $this->subscriptionRepo->expects($this->once())
            ->method('findActiveByBusiness')
            ->with(123)
            ->willReturn(null);

        $this->assertFalse($this->service->hasFeature($business, 'catalog'));
    }

    public function testValidateApiKeySuccess(): void
    {
        $apiKey = $this->createMock(ApiKey::class);
        $business = $this->createMock(Business::class);

        $this->apiKeyRepo->expects($this->once())
            ->method('findByKey')
            ->with('test_api_key_123')
            ->willReturn($apiKey);
        $apiKey->method('getBusiness')->willReturn($business);

        $result = $this->service->validateApiKey('test_api_key_123');

        $this->assertSame($business, $result);
    }

    public function testValidateApiKeyNotFound(): void
    {
        $this->apiKeyRepo->expects($this->once())
            ->method('findByKey')
            ->with('nonexistent_key')
            ->willReturn(null);

        $result = $this->service->validateApiKey('nonexistent_key');

        $this->assertNull($result);
    }
}