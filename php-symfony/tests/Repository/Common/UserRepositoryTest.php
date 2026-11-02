<?php declare(strict_types=1);

namespace App\Tests\Repository\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Repository\Common\UserRepository;
use App\Entity\Common\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UserRepositoryTest extends TestCase
{
    private UserRepository&MockObject $repo;

    protected function setUp(): void
    {
        $this->repo = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find', 'findOneBy', 'findBy', 'count'])
            ->getMock();
    }

    public function testFindById(): void
    {
        $id = 'user-123';
        $expected = $this->createMock(User::class);

        $this->repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($expected);

        $result = $this->repo->findById($id);
        $this->assertSame($expected, $result);
    }

    public function testFindByEmail(): void
    {
        $email = 'test@example.com';
        $expected = $this->createMock(User::class);

        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn($expected);

        $result = $this->repo->findByEmail($email);
        $this->assertSame($expected, $result);
    }

    public function testFindByPhone(): void
    {
        $phone = '+84123456789';
        $expected = $this->createMock(User::class);

        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['phone' => $phone])
            ->willReturn($expected);

        $result = $this->repo->findByPhone($phone);
        $this->assertSame($expected, $result);
    }

    public function testFindActiveUsers(): void
    {
        $expected = [$this->createMock(User::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'ACTIVE'])
            ->willReturn($expected);

        $result = $this->repo->findActiveUsers();
        $this->assertSame($expected, $result);
    }

    public function testFindInactiveUsers(): void
    {
        $expected = [$this->createMock(User::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'INACTIVE'])
            ->willReturn($expected);

        $result = $this->repo->findInactiveUsers();
        $this->assertSame($expected, $result);
    }

    public function testFindSuspendedUsers(): void
    {
        $expected = [$this->createMock(User::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'SUSPENDED'])
            ->willReturn($expected);

        $result = $this->repo->findSuspendedUsers();
        $this->assertSame($expected, $result);
    }

    public function testCountActiveUsers(): void
    {
        $this->repo->expects($this->once())
            ->method('count')
            ->with(['status' => 'ACTIVE'])
            ->willReturn(10);

        $result = $this->repo->countActiveUsers();
        $this->assertSame(10, $result);
    }

    public function testCountInactiveUsers(): void
    {
        $this->repo->expects($this->once())
            ->method('count')
            ->with(['status' => 'INACTIVE'])
            ->willReturn(5);

        $result = $this->repo->countInactiveUsers();
        $this->assertSame(5, $result);
    }

    public function testCountSuspendedUsers(): void
    {
        $this->repo->expects($this->once())
            ->method('count')
            ->with(['status' => 'SUSPENDED'])
            ->willReturn(2);

        $result = $this->repo->countSuspendedUsers();
        $this->assertSame(2, $result);
    }
}
