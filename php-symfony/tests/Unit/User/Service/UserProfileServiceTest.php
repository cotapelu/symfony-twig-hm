<?php declare(strict_types=1);

namespace App\Tests\Unit\User\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\User\Entity\UserProfile;
use App\User\Repository\UserProfileRepositoryInterface;
use App\User\Service\UserProfileService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UserProfileServiceTest extends TestCase
{
    private UserProfileRepositoryInterface&MockObject $repository;
    private UserProfileService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserProfileRepositoryInterface::class);
        $this->service = new UserProfileService($this->repository);
    }

    public function testCreateProfile(): void
    {
        $userId = 'user_123';
        $profile = new UserProfile($userId);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (UserProfile $p) use ($userId) {
                return $p->getUserId() === $userId;
            }))
            ->willReturn($profile);

        $result = $this->service->createProfile($userId);

        $this->assertInstanceOf(UserProfile::class, $result);
        $this->assertEquals($userId, $result->getUserId());
    }

    public function testGetUserProfileFound(): void
    {
        $userId = 'user_123';
        $profile = new UserProfile($userId);

        $this->repository->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn($profile);

        $result = $this->service->getUserProfile($userId);

        $this->assertSame($profile, $result);
    }

    public function testGetUserProfileNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('findByUserId')
            ->with('unknown')
            ->willReturn(null);

        $result = $this->service->getUserProfile('unknown');

        $this->assertNull($result);
    }

    public function testGetProfileByUserId(): void
    {
        $userId = 'user_123';
        $profile = new UserProfile($userId);

        $this->repository->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn($profile);

        $result = $this->service->getProfileByUserId($userId);

        $this->assertSame($profile, $result);
    }

    public function testUpdateProfileFound(): void
    {
        $userId = 'user_123';
        $profile = new UserProfile($userId);
        $profile->setDisplayName('Old Name');

        $data = [
            'displayName' => 'New Name',
            'bio' => 'Hello world',
            'avatar' => 'http://example.com/avatar.jpg',
        ];

        $this->repository->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn($profile);
        $this->repository->expects($this->once())
            ->method('save')
            ->with($profile)
            ->willReturn($profile);

        $result = $this->service->updateProfile($userId, $data);

        $this->assertSame($profile, $result);
        $this->assertEquals('New Name', $profile->getDisplayName());
        $this->assertEquals('Hello world', $profile->getBio());
        $this->assertEquals('http://example.com/avatar.jpg', $profile->getAvatar());
    }

    public function testUpdateProfileNotFoundCreates(): void
    {
        $userId = 'user_456';
        $this->repository->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn(null);
        $this->repository->expects($this->exactly(2))
            ->method('save')
            ->willReturnArgument(0);

        $result = $this->service->updateProfile($userId, ['displayName' => 'New']);

        $this->assertInstanceOf(UserProfile::class, $result);
        $this->assertEquals('New', $result->getDisplayName());
    }

    public function testUpdateProfilePartialData(): void
    {
        $userId = 'user_123';
        $profile = new UserProfile($userId);
        $profile->setDisplayName('Old');
        $profile->setBio('Old bio');

        $this->repository->expects($this->once())->method('findByUserId')->with($userId)->willReturn($profile);
        $this->repository->expects($this->once())->method('save')->willReturn($profile);

        $this->service->updateProfile($userId, ['bio' => 'New bio']);

        $this->assertEquals('Old', $profile->getDisplayName()); // unchanged
        $this->assertEquals('New bio', $profile->getBio());
    }

    public function testUpdateProfileEmailAndPhone(): void
    {
        $userId = 'user_123';
        $profile = new UserProfile($userId);

        $data = ['email' => 'new@example.com', 'phone' => '+84912345678'];

        $this->repository->expects($this->once())->method('findByUserId')->with($userId)->willReturn($profile);
        $this->repository->expects($this->once())->method('save')->willReturn($profile);

        $this->service->updateProfile($userId, $data);

        $this->assertEquals('new@example.com', $profile->getEmail());
        $this->assertEquals('+84912345678', $profile->getPhone());
    }

    public function testUpdateProfileAddressFields(): void
    {
        $userId = 'user_123';
        $profile = new UserProfile($userId);

        $data = ['address' => '123 Street', 'city' => 'Hanoi', 'country' => 'Vietnam'];

        $this->repository->expects($this->once())->method('findByUserId')->with($userId)->willReturn($profile);
        $this->repository->expects($this->once())->method('save')->willReturn($profile);

        $this->service->updateProfile($userId, $data);

        $this->assertEquals('123 Street', $profile->getAddress());
        $this->assertEquals('Hanoi', $profile->getCity());
        $this->assertEquals('Vietnam', $profile->getCountry());
    }
}
