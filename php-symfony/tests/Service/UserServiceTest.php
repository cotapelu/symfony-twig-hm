<?php

declare(strict_types=1);

namespace App\Tests\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

// Require the classes directly to bypass autoload issues
require_once __DIR__ . '/../../modules/User/Repository/UserProfileRepositoryInterface.php';
require_once __DIR__ . '/../../modules/User/Entity/UserProfile.php';
require_once __DIR__ . '/../../modules/User/Service/UserProfileService.php';

use App\User\Service\UserProfileService;
use App\User\Repository\UserProfileRepositoryInterface;
use App\User\Entity\UserProfile;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for UserProfileService
 * 
 * Quality First:
 * - Functions <= 20 lines, single responsibility
 * - Cyclomatic complexity <= 10
 * - 100% error handling coverage
 * - Input validation on all public APIs
 * - No hardcoded secrets/configs
 * - Pure functions where possible
 * - Dependency injection for external services
 */
#[AllowMockObjectsWithoutExpectations]
class UserServiceTest extends TestCase
{
    private UserProfileService $userProfileService;
    private UserProfileRepositoryInterface $userProfileRepository;

    protected function setUp(): void
    {
        $this->userProfileRepository = $this->createMock(UserProfileRepositoryInterface::class);
        $this->userProfileService = new UserProfileService($this->userProfileRepository);
    }

    public function testUpdateUserProfileWithValidData(): void
    {
        $profile = $this->createMock(UserProfile::class);
        $profile->method('getUserId')->willReturn('user_123');

        $this->userProfileRepository->method('findByUserId')->willReturn($profile);
        $this->userProfileRepository->expects($this->once())->method('save');

        $profileData = [
            'displayName' => 'Updated Name',
            'bio' => 'Updated bio',
        ];

        $result = $this->userProfileService->updateProfile('user_123', $profileData);

        $this->assertNotNull($result);
    }

    public function testUpdateUserProfileWithMissingFields(): void
    {
        $profile = $this->createMock(UserProfile::class);
        $profile->method('getUserId')->willReturn('user_123');

        $this->userProfileRepository->method('findByUserId')->willReturn($profile);
        $this->userProfileRepository->expects($this->once())->method('save');

        $profileData = [];

        $result = $this->userProfileService->updateProfile('user_123', $profileData);

        $this->assertNotNull($result);
    }

    public function testGetUserProfile(): void
    {
        $profile = $this->createMock(UserProfile::class);
        $this->userProfileRepository->method('findByUserId')->willReturn($profile);

        $result = $this->userProfileService->getUserProfile('user_123');

        $this->assertSame($profile, $result);
    }

    public function testGetNonExistentUserProfile(): void
    {
        $this->userProfileRepository->method('findByUserId')->willReturn(null);

        $result = $this->userProfileService->getUserProfile('nonexistent_user');

        $this->assertNull($result);
    }
}