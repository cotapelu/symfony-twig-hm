<?php declare(strict_types=1);

namespace App\Tests\Integration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\User\Entity\UserProfile;
use App\User\Repository\UserProfileRepository;
use App\User\Service\UserProfileService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UserIntegrationTest extends TestCase
{
    private ?\PDO $pdo = null;
    private UserProfileRepository $repository;
    private UserProfileService $service;

    protected function setUp(): void
    {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // Create table
        $this->pdo->exec("
            CREATE TABLE user_profiles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id VARCHAR(255) NOT NULL UNIQUE,
                display_name VARCHAR(255),
                bio TEXT,
                avatar VARCHAR(255),
                email VARCHAR(255),
                phone VARCHAR(20),
                address TEXT,
                city VARCHAR(100),
                country VARCHAR(100),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME
            )
        ");

        $this->repository = new UserProfileRepository($this->pdo);
        $this->service = new UserProfileService($this->repository);
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
    }

    // ==================== CRUD Tests ====================

    public function testCreateAndRetrieveProfile(): void
    {
        $profile = $this->service->createProfile('user_123');

        $this->assertNotNull($profile->getUserId());
        $this->assertEquals('user_123', $profile->getUserId());

        // Retrieve from database
        $retrieved = $this->repository->findByUserId('user_123');
        $this->assertNotNull($retrieved);
        $this->assertEquals('user_123', $retrieved->getUserId());
    }

    public function testGetUserProfile(): void
    {
        $this->service->createProfile('user_123');

        $profile = $this->service->getUserProfile('user_123');

        $this->assertNotNull($profile);
        $this->assertEquals('user_123', $profile->getUserId());
    }

    public function testGetUserProfileNotFound(): void
    {
        $profile = $this->service->getUserProfile('nonexistent');

        $this->assertNull($profile);
    }

    public function testUpdateProfile(): void
    {
        $this->service->createProfile('user_123');
        $updated = $this->service->updateProfile('user_123', [
            'displayName' => 'John Doe',
            'bio' => 'Software developer',
            'email' => 'john@example.com'
        ]);

        $this->assertEquals('John Doe', $updated->getDisplayName());
        $this->assertEquals('Software developer', $updated->getBio());
        $this->assertEquals('john@example.com', $updated->getEmail());

        // Verify in database
        $retrieved = $this->repository->findByUserId('user_123');
        $this->assertEquals('John Doe', $retrieved->getDisplayName());
    }

    public function testDeleteProfile(): void
    {
        $this->service->createProfile('user_123');

        $result = $this->service->deleteProfile('user_123');
        $this->assertTrue($result);

        // Profile should not be found
        $retrieved = $this->repository->findByUserId('user_123');
        $this->assertNull($retrieved);
    }

    public function testDeleteProfileNotFound(): void
    {
        $result = $this->service->deleteProfile('nonexistent');
        $this->assertFalse($result);
    }

    // ==================== Data Integrity Tests ====================

    public function testProfileIsolationBetweenUsers(): void
    {
        $this->service->createProfile('user_1');
        $this->service->createProfile('user_2');

        $profile1 = $this->service->getUserProfile('user_1');
        $profile2 = $this->service->getUserProfile('user_2');

        $this->assertNotNull($profile1);
        $this->assertNotNull($profile2);
        $this->assertEquals('user_1', $profile1->getUserId());
        $this->assertEquals('user_2', $profile2->getUserId());
    }

    public function testAllFieldsCanBeUpdated(): void
    {
        $this->service->createProfile('user_123');
        $updated = $this->service->updateProfile('user_123', [
            'displayName' => 'John Doe',
            'bio' => 'Developer',
            'avatar' => '/avatars/user.png',
            'email' => 'john@example.com',
            'phone' => '+849012345678',
            'address' => '123 Main St',
            'city' => 'Hanoi',
            'country' => 'Vietnam'
        ]);

        $this->assertEquals('John Doe', $updated->getDisplayName());
        $this->assertEquals('Developer', $updated->getBio());
        $this->assertEquals('/avatars/user.png', $updated->getAvatar());
        $this->assertEquals('john@example.com', $updated->getEmail());
        $this->assertEquals('+849012345678', $updated->getPhone());
        $this->assertEquals('123 Main St', $updated->getAddress());
        $this->assertEquals('Hanoi', $updated->getCity());
        $this->assertEquals('Vietnam', $updated->getCountry());
    }

    public function testOptionalFieldsAreNullByDefault(): void
    {
        $profile = $this->service->createProfile('user_123');

        $this->assertNull($profile->getDisplayName());
        $this->assertNull($profile->getBio());
        $this->assertNull($profile->getAvatar());
        $this->assertNull($profile->getEmail());
        $this->assertNull($profile->getPhone());
        $this->assertNull($profile->getAddress());
        $this->assertNull($profile->getCity());
        $this->assertNull($profile->getCountry());
    }

    public function testGetProfileByUserId(): void
    {
        $this->service->createProfile('user_123');

        $retrieved = $this->service->getProfileByUserId('user_123');

        $this->assertNotNull($retrieved);
        $this->assertEquals('user_123', $retrieved->getUserId());
    }

    public function testUpdateNonExistentProfileCreatesNew(): void
    {
        $result = $this->service->updateProfile('new_user', ['displayName' => 'New']);

        $this->assertNotNull($result);
        $this->assertEquals('new_user', $result->getUserId());
        $this->assertEquals('New', $result->getDisplayName());
    }
}
