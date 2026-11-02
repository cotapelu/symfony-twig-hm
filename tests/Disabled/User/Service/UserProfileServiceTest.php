<?php declare(strict_types=1);

namespace App\Tests\Unit\User\Service;

use App\User\Entity\UserProfile;
use App\User\Repository\UserProfileRepositoryInterface;
use App\User\Service\UserProfileService;
use PHPUnit\Framework\TestCase;

class UserProfileServiceTest extends TestCase
{
    private UserProfileService $service;
    private UserProfileRepositoryInterface $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserProfileRepositoryInterface::class);
        $this->service = new UserProfileService($this->repository);
    }

    public function testCreateProfileSuccess(): void
    {
        $profile = new UserProfile('user_123');
        $profile->setId(1);
        $this->repository->expects($this->once())
            ->method('save')
            ->willReturn($profile);

        $result = $this->service->createProfile('user_123');

        $this->assertInstanceOf(UserProfile::class, $result);
        $this->assertEquals('user_123', $result->getUserId());
    }

    public function testGetUserProfileFound(): void
    {
        $profile = new UserProfile('user_123');
        $profile->setId(1);
        $this->repository->expects($this->once())
            ->method('findByUserId')
            ->with('user_123')
            ->willReturn($profile);

        $result = $this->service->getUserProfile('user_123');

        $this->assertSame($profile, $result);
    }

    public function testGetUserProfileNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('findByUserId')
            ->with('nonexistent')
            ->willReturn(null);

        $result = $this->service->getUserProfile('nonexistent');

        $this->assertNull($result);
    }

    public function testGetProfileById(): void
    {
        $profile = new UserProfile('user_123');
        $profile->setId(1);
        $this->repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($profile);

        $result = $this->service->getProfileById(1);

        $this->assertSame($profile, $result);
    }

    public function testUpdateProfileExisting(): void
    {
        $profile = new UserProfile('user_123');
        $profile->setId(1);
        $this->repository->method('findByUserId')->willReturn($profile);
        $this->repository->method('save')->willReturn($profile);

        $result = $this->service->updateProfile('user_123', ['displayName' => 'John']);

        $this->assertEquals('John', $result->getDisplayName());
    }

    public function testUpdateProfileNew(): void
    {
        $newProfile = new UserProfile('user_new');
        $newProfile->setId(1);
        $this->repository->method('findByUserId')->willReturn(null);
        $this->repository->method('save')->willReturn($newProfile);

        $result = $this->service->updateProfile('user_new', ['displayName' => 'New User']);

        $this->assertInstanceOf(UserProfile::class, $result);
    }

    public function testUpdateProfileWithMultipleFields(): void
    {
        $profile = new UserProfile('user_123');
        $profile->setId(1);
        $this->repository->method('findByUserId')->willReturn($profile);
        $this->repository->method('save')->willReturn($profile);

        $result = $this->service->updateProfile('user_123', [
            'displayName' => 'John',
            'bio' => 'Developer',
            'email' => 'john@example.com'
        ]);

        $this->assertEquals('John', $result->getDisplayName());
        $this->assertEquals('Developer', $result->getBio());
        $this->assertEquals('john@example.com', $result->getEmail());
    }

    public function testDeleteProfileSuccess(): void
    {
        $profile = new UserProfile('user_123');
        $profile->setId(1);
        $this->repository->expects($this->once())
            ->method('findByUserId')
            ->with('user_123')
            ->willReturn($profile);
        $this->repository->expects($this->once())
            ->method('delete')
            ->with(1)
            ->willReturn(true);

        $result = $this->service->deleteProfile('user_123');

        $this->assertTrue($result);
    }

    public function testDeleteProfileNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('findByUserId')
            ->with('nonexistent')
            ->willReturn(null);

        $result = $this->service->deleteProfile('nonexistent');

        $this->assertFalse($result);
    }
}
