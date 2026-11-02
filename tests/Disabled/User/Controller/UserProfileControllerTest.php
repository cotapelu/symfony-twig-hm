<?php declare(strict_types=1);

namespace App\Tests\Unit\User\Controller;

use App\User\Controller\UserProfileController;
use App\User\Entity\UserProfile;
use App\User\Service\UserProfileService;
use PHPUnit\Framework\TestCase;

class UserProfileControllerTest extends TestCase
{
    private UserProfileController $controller;
    private UserProfileService $service;

    protected function setUp(): void
    {
        $this->service = $this->createMock(UserProfileService::class);
        $this->controller = new UserProfileController($this->service);
    }

    public function testCreateProfileSuccess(): void
    {
        $profile = new UserProfile('user_123');
        $profile->setId(1);
        $this->service->expects($this->once())
            ->method('createProfile')
            ->with('user_123')
            ->willReturn($profile);

        $result = $this->controller->createProfile(['user_id' => 'user_123']);

        $this->assertEquals(201, $result['status']);
        $this->assertInstanceOf(UserProfile::class, $result['data']);
    }

    public function testCreateProfileMissingUserId(): void
    {
        $result = $this->controller->createProfile([]);

        $this->assertEquals(400, $result['status']);
        $this->assertEquals('User ID is required', $result['error']);
    }

    public function testGetProfileFound(): void
    {
        $profile = new UserProfile('user_123');
        $profile->setId(1);
        $this->service->expects($this->once())
            ->method('getUserProfile')
            ->with('user_123')
            ->willReturn($profile);

        $result = $this->controller->getProfile(['user_id' => 'user_123']);

        $this->assertEquals(200, $result['status']);
        $this->assertSame($profile, $result['data']);
    }

    public function testGetProfileNotFound(): void
    {
        $this->service->expects($this->once())
            ->method('getUserProfile')
            ->with('nonexistent')
            ->willReturn(null);

        $result = $this->controller->getProfile(['user_id' => 'nonexistent']);

        $this->assertEquals(404, $result['status']);
        $this->assertEquals('Profile not found', $result['error']);
    }

    public function testGetProfileMissingUserId(): void
    {
        $result = $this->controller->getProfile([]);

        $this->assertEquals(400, $result['status']);
        $this->assertEquals('User ID is required', $result['error']);
    }

    public function testUpdateProfileSuccess(): void
    {
        $profile = new UserProfile('user_123');
        $profile->setId(1);
        $profile->setDisplayName('John');
        $this->service->expects($this->once())
            ->method('updateProfile')
            ->with('user_123', ['displayName' => 'John'])
            ->willReturn($profile);

        $result = $this->controller->updateProfile([
            'user_id' => 'user_123',
            'body' => ['displayName' => 'John']
        ]);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('John', $result['data']->getDisplayName());
    }

    public function testUpdateProfileMissingUserId(): void
    {
        $result = $this->controller->updateProfile([
            'body' => ['displayName' => 'John']
        ]);

        $this->assertEquals(400, $result['status']);
        $this->assertEquals('User ID is required', $result['error']);
    }

    public function testDeleteProfileSuccess(): void
    {
        $this->service->expects($this->once())
            ->method('deleteProfile')
            ->with('user_123')
            ->willReturn(true);

        $result = $this->controller->deleteProfile(['user_id' => 'user_123']);

        $this->assertEquals(204, $result['status']);
    }

    public function testDeleteProfileNotFound(): void
    {
        $this->service->expects($this->once())
            ->method('deleteProfile')
            ->with('nonexistent')
            ->willReturn(false);

        $result = $this->controller->deleteProfile(['user_id' => 'nonexistent']);

        $this->assertEquals(404, $result['status']);
        $this->assertEquals('Profile not found', $result['error']);
    }

    public function testDeleteProfileMissingUserId(): void
    {
        $result = $this->controller->deleteProfile([]);

        $this->assertEquals(400, $result['status']);
        $this->assertEquals('User ID is required', $result['error']);
    }
}
