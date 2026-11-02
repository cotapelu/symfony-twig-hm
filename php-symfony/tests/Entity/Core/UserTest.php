<?php declare(strict_types=1);

namespace App\Tests\Entity\Core;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Core\Entity\User;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UserTest extends TestCase
{
    public function testConstructorAutoGeneratesIdAndTimestamps(): void
    {
        $user = new User();
        $this->assertNotEmpty($user->getId());
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $user->getId());
        $this->assertInstanceOf(\DateTime::class, $user->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $user->getUpdatedAt());
        $this->assertSame('active', $user->getStatus());
    }

    public function testSettersAndGetters(): void
    {
        $user = new User();

        $email = 'test@example.com';
        $user->setEmail($email);
        $this->assertSame($email, $user->getEmail());

        $hash = password_hash('password', PASSWORD_DEFAULT);
        $user->setPasswordHash($hash);
        $this->assertSame($hash, $user->getPasswordHash());

        $user->setStatus('inactive');
        $this->assertSame('inactive', $user->getStatus());
        $this->assertFalse($user->isActive());
        $this->assertFalse($user->isSuspended());
        $this->assertFalse($user->isDeleted());

        $user->setStatus('suspended');
        $this->assertTrue($user->isSuspended());

        $user->setStatus('deleted');
        $this->assertTrue($user->isDeleted());

        // updateTimestamp should be called on preUpdate, but we can't test that easily. However we can test toArray.
        $user->setEmail('new@example.com');
        // Assuming updateTimestamp modifies updatedAt, we can't directly call it as it's a lifecycle callback. We'll skip.
    }

    public function testToArray(): void
    {
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setStatus('active');

        $arr = $user->toArray();
        $this->assertArrayHasKey('id', $arr);
        $this->assertSame($user->getId(), $arr['id']);
        $this->assertArrayHasKey('email', $arr);
        $this->assertSame('user@example.com', $arr['email']);
        $this->assertArrayHasKey('status', $arr);
        $this->assertSame('active', $arr['status']);
        $this->assertArrayHasKey('createdAt', $arr);
        $this->assertArrayHasKey('updatedAt', $arr);
    }
}
